"""CLI regressions using real Git submodules and a harmless Docker stub.

Run with: python3 -m unittest discover -s tests -p 'test_bonsai_*.py' -v
"""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


CLI = Path(__file__).resolve().parents[1] / 'bonsai'


class BootstrapVersionTest(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='bonsai-bootstrap-')
        self.addCleanup(self.tmp.cleanup)
        self.base = Path(self.tmp.name)
        self.root = self.base / 'app'
        self.root.mkdir()
        self.env = dict(os.environ, GIT_ALLOW_PROTOCOL='file',
                        GIT_AUTHOR_NAME='Test', GIT_AUTHOR_EMAIL='test@example.invalid',
                        GIT_COMMITTER_NAME='Test', GIT_COMMITTER_EMAIL='test@example.invalid',
                        HOME=str(self.base / 'home'))
        origin = self.base / 'origin'
        origin.mkdir()
        self.git(origin, 'init', '-q')
        (origin / 'scss').mkdir()
        marker = origin / 'scss' / 'version.scss'
        for version in ('v4.6.0', 'v4.6.2'):
            marker.write_text(version)
            self.git(origin, 'add', '.')
            self.git(origin, 'commit', '-qm', version)
            self.git(origin, 'tag', version)
        self.git(origin, 'checkout', '-q', 'v4.6.0')
        self.git(self.root, 'init', '-q')
        self.git(self.root, 'submodule', 'add', '-q', str(origin), 'cms/bootstrap')
        self.git(self.root, 'commit', '-qam', 'pin old Bootstrap')
        self.bootstrap = self.root / 'cms/bootstrap'
        shutil.copy2(CLI, self.root / 'bonsai')
        (self.root / 'current/config/sass').mkdir(parents=True)
        (self.root / 'current/config/sass/main.scss').write_text('// fixture')
        (self.root / 'current/config/bootstrap-version.txt').write_text('"v4.6.2"\n')
        (self.root / 'current/config/ecms_config.php').write_text('')
        bindir = self.base / 'bin'
        bindir.mkdir()
        docker = bindir / 'docker'
        docker.write_text('#!/bin/bash\n'
                          'printf "%s\\n" "$*" >> "$DOCKER_LOG"\n'
                          'if [[ "$*" == *"ps --status running"* ]]; then echo cms; fi\n')
        docker.chmod(0o755)
        self.env.update(PATH=str(bindir) + os.pathsep + self.env['PATH'],
                        DOCKER_LOG=str(self.base / 'docker.log'))

    def git(self, cwd, *args):
        return subprocess.check_output(['git', '-C', str(cwd), *args],
                                       env=self.env, stderr=subprocess.PIPE, text=True).strip()

    def cli(self, *args):
        return subprocess.run(['bash', str(self.root / 'bonsai'), *args],
                              cwd=self.root, env=self.env, capture_output=True, text=True)

    def version(self):
        return self.git(self.bootstrap, 'describe', '--tags', '--exact-match')

    def test_start_restores_project_version_after_recursive_submodule_update(self):
        self.git(self.bootstrap, 'checkout', '-q', 'v4.6.2')
        for _ in range(2):
            result = self.cli('start')
            self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
            self.assertEqual(self.version(), 'v4.6.2')

    def test_static_selects_project_version_before_compiling(self):
        result = self.cli('static')
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertEqual(self.version(), 'v4.6.2')
        self.assertIn('exec watcher sass', (self.base / 'docker.log').read_text())

    def test_missing_tag_is_fetched_from_origin(self):
        self.git(self.bootstrap, 'tag', '-d', 'v4.6.2')
        result = self.cli('start')
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertEqual(self.version(), 'v4.6.2')

    def test_unavailable_version_aborts_before_css_build(self):
        (self.root / 'current/config/bootstrap-version.txt').write_text('v99.0.0')
        result = self.cli('static')
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('refusing to use another version', result.stdout + result.stderr)
        self.assertFalse((self.base / 'docker.log').exists())

    def test_start_preserves_local_bootstrap_edits(self):
        marker = self.bootstrap / 'scss/version.scss'
        marker.write_text('local changes')
        result = self.cli('start')
        self.assertNotEqual(result.returncode, 0)
        self.assertEqual(marker.read_text(), 'local changes')
        self.assertEqual(self.version(), 'v4.6.0')

    def test_static_rejects_local_edits_even_on_correct_version(self):
        self.git(self.bootstrap, 'checkout', '-q', 'v4.6.2')
        (self.bootstrap / 'scss/version.scss').write_text('local changes')
        result = self.cli('static')
        self.assertNotEqual(result.returncode, 0)
        self.assertFalse((self.base / 'docker.log').exists())

    def test_standalone_start_needs_no_host_bootstrap_checkout(self):
        (self.root / '.gitmodules').unlink()
        # Move outside the app, simulating release archives with baked assets.
        self.bootstrap.rename(self.base / 'unused-bootstrap')
        result = self.cli('start')
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)


if __name__ == '__main__':
    unittest.main()
