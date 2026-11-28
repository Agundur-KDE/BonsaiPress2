"""Exercise shell cwd and Git context across real CLI project switches."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[1]


class ShellSwitchTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='bonsai shell ')
        self.addCleanup(self.tmp.cleanup)
        self.root = Path(self.tmp.name) / 'app'
        self.root.mkdir()
        shutil.copy2(ROOT / 'bonsai', self.root / 'bonsai')
        shutil.copytree(ROOT / 'shell', self.root / 'shell')
        self.home = Path(self.tmp.name) / 'home'
        config = self.home / '.config/bonsai'
        config.mkdir(parents=True)
        (config / 'projects.conf').write_text('old unused\nnew unused\n')
        (config / 'active').write_text('old\n')
        self.env = dict(os.environ, HOME=str(self.home), SHELL='/bin/bash',
                        GIT_AUTHOR_NAME='Test', GIT_AUTHOR_EMAIL='test@example.invalid',
                        GIT_COMMITTER_NAME='Test', GIT_COMMITTER_EMAIL='test@example.invalid')
        for name in ('old', 'new'):
            client = self.root / 'clients' / name
            (client / 'shared').mkdir(parents=True)
            (client / 'shared/file').write_text(name)
            self.git(client, 'init', '-q', '-b', name)
            self.git(client, 'add', '.')
            self.git(client, 'commit', '-qm', name)
        (self.root / 'clients/old/only-old').mkdir()
        (self.root / 'current').symlink_to('clients/old')
        bindir = self.home / '.local/bin'
        bindir.mkdir(parents=True)
        docker = bindir / 'docker'
        docker.write_text('#!/bin/sh\nexit 0\n')
        docker.chmod(0o755)
        self.env['PATH'] = str(bindir) + os.pathsep + self.env['PATH']

    def git(self, cwd, *args):
        return subprocess.check_output(['git', '-C', str(cwd), *args],
                                       env=self.env, stderr=subprocess.PIPE, text=True)

    def shell(self, script, shell='bash'):
        args = ['bash', '--noprofile', '--norc'] if shell == 'bash' else ['zsh', '-f']
        result = subprocess.run([*args, '-c', script],
                                cwd=self.root, env=self.env, capture_output=True, text=True)
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        return result.stdout

    def test_switch_updates_real_git_context_and_preserves_subdirectory(self):
        self.shell('''
            eval "$(./bonsai shell-init)"
            cd current/shared
            bonsai switch new || exit
            [[ $PWD == "$_BONSAI_ROOT/current/shared" ]] || exit 11
            [[ $(git branch --show-current) == new ]] || exit 12
            [[ $(cat file) == new ]] || exit 13
        ''')

    def test_missing_subdirectory_falls_back_to_current(self):
        self.shell('''
            eval "$(./bonsai shell-init)"
            cd current/only-old
            bonsai switch new || exit
            [[ $PWD == "$_BONSAI_ROOT/current" ]] || exit 11
            [[ $(git branch --show-current) == new ]] || exit 12
        ''')

    def test_existing_install_gets_notice_and_activation_repairs_cwd(self):
        self.shell('''
            unset BONSAI_SHELL_ROOT
            cli="$PWD/bonsai"
            cd current/shared
            "$cli" switch new > "$HOME/notice" || exit
            [[ $(git branch --show-current) == old ]] || exit 11
            grep -q 'shell still points to the previous project' "$HOME/notice" || exit 12
            eval "$("$cli" shell-init)"
            [[ $(git branch --show-current) == new ]] || exit 13
            [[ $(cat file) == new ]] || exit 14
        ''')

    def test_outside_current_stays_put(self):
        self.shell('''
            eval "$(./bonsai shell-init)"
            before=$PWD
            bonsai switch new || exit
            [[ $PWD == "$before" ]] || exit 11
        ''')

    def test_direct_cli_switch_refreshes_before_existing_git_prompt(self):
        self.shell('''
            PROMPT_COMMAND=('seen=$(git branch --show-current)' ':')
            eval "$(./bonsai shell-init)"
            eval "$(./bonsai shell-init)"
            [[ ${#PROMPT_COMMAND[@]} == 3 ]] || exit 11
            cd current
            "$_BONSAI_CLI" switch new || exit
            [[ $(git branch --show-current) == old ]] || exit 12
            for hook in "${PROMPT_COMMAND[@]}"; do eval "$hook"; done
            [[ $seen == new ]] || exit 13
        ''')

    def test_failed_switch_preserves_status_and_current_repo(self):
        self.shell('''
            eval "$(./bonsai shell-init)"
            cd current
            bonsai switch unknown
            result=$?
            [[ $result == 1 ]] || exit 11
            [[ $(git branch --show-current) == old ]] || exit 12
            false
            _bonsai_refresh_cwd
            [[ $? == 1 ]] || exit 13
        ''')

    @unittest.skipUnless(shutil.which('zsh'), 'Zsh is not installed')
    def test_zsh_switch_and_prompt_hook(self):
        self.shell('''
            precmd_functions=('existing_prompt')
            existing_prompt() { seen=$(git branch --show-current); }
            eval "$(./bonsai shell-init)"
            eval "$(./bonsai shell-init)"
            [[ ${#precmd_functions[@]} == 2 ]] || exit 11
            cd current/shared
            bonsai switch new || exit
            [[ $(git branch --show-current) == new ]] || exit 12
            "$_BONSAI_CLI" switch old || exit
            for hook in "${precmd_functions[@]}"; do "$hook"; done
            [[ $seen == old ]] || exit 13
            [[ $PWD == "$_BONSAI_ROOT/current/shared" ]] || exit 14
        ''', shell='zsh')

    def test_install_is_idempotent_and_loads_integration(self):
        (self.home / '.bashrc').write_text('# existing config\n')
        self.shell('''
            ./bonsai install || exit
            ./bonsai install || exit
            source "$HOME/.bashrc"
            [[ $(type -t bonsai) == function ]] || exit 11
            cd current
            bonsai switch new || exit
            [[ $(git branch --show-current) == new ]] || exit 12
        ''')
        rc = (self.home / '.bashrc').read_text()
        self.assertTrue(rc.startswith('# existing config\n'))
        self.assertEqual(rc.count('shell-init'), 1)


if __name__ == '__main__':
    unittest.main()
