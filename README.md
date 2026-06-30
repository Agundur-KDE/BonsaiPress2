# BonsaiPress

[![BonsaiPress — The CMS your AI assistant actually understands](https://www.agundur.de/_resources/pictures/BonsaiPress_LLM.png)](https://www.agundur.de/projects/bonsaipress.html)

**The CMS your AI assistant actually understands.**

No database. No admin UI. Just files, Git, and a shell — exactly how AI works best.

## Why AI loves BonsaiPress

Most CMS systems hide their state behind admin panels, databases, and plugin systems. AI assistants can't see any of that. BonsaiPress is different:

- **Shell-native** — every operation runs via `bonsai` CLI. No clicking through menus.
- **Flat files** — content is XML + HTML. Your AI reads, writes, and understands it directly.
- **No hidden state** — no database, no plugin magic. What you see is what gets deployed.
- **Static output** — pure HTML, maximum AI crawler visibility (GEO-optimized by design)
- **Claude Code Skill** — structured instructions that make Claude instantly proficient with BonsaiPress

> Built for the shell. Built for AI.

## What it is

BonsaiPress is a PHP CMS that runs entirely without a database. Content lives in
files, structure lives in `site_structure.xml`, and deployment uploads only what
changed.

- **No database** — no MySQL, no migrations, no runtime dependencies
- **Static export** — generates pure HTML, blazing fast at runtime
- **Multi-client** — one CMS engine, unlimited client projects, switch in seconds
- **Hash-diff deploy** — only changed files are uploaded via FTPS
- **Server auto-init** — `bonsai deploy` creates `web/` and `include/` on Hetzner Managed automatically

## Requirements

- [Docker](https://www.docker.com/products/docker-desktop)
- Git

That's it.

## Quick start

```bash
git clone https://github.com/Agundur-KDE/BonsaiPress2.git
cd BonsaiPress2
./bonsai install    # makes 'bonsai' available system-wide (once)
bonsai start        # pulls images, starts Docker — demo on :8080
```

Open [http://localhost:8080](http://localhost:8080).

## Workflow

```bash
bonsai start                   # Docker up: CMS :8080, Preview :8081, Watcher :8001
bonsai stop                    # Docker down
bonsai status                  # Show status and URLs

bonsai new myclient            # New project from template, git-ready
bonsai new myclient <git-url>  # Same, with remote already configured
bonsai switch myclient         # Swap active project instantly
bonsai list                    # Show all projects

bonsai static                  # Generate static HTML into current/static/
bonsai deploy                  # Upload only changed files via FTPS
bonsai deploy -d               # Dry-run: show diff without uploading
```

## Project structure

```
BonsaiPress2/
├── bonsai              ← CLI (start, stop, deploy, switch, ...)
├── cms/                ← CMS engine (generic, client-agnostic)
├── docker/             ← Dockerfiles and watcher
├── current/            ← symlink to active client project (not in git)
└── clients/            ← client projects, each a separate git repo (not in git)

current/  (a client project)
├── config/
│   ├── de/
│   │   ├── site_structure.xml   ← page tree
│   │   ├── contenfiles/         ← page content
│   │   ├── templates/           ← HTML templates
│   │   └── page_config/         ← per-page CSS/JS
│   ├── sass/
│   │   └── main.scss            ← compiled by watcher
│   └── bonsai_config.php        ← FTP, domain, CSS (gitignored — contains passwords)
└── static/
    └── _resources/              ← CSS, JS, images (committed)
```

## New client project

```bash
# Configure the template repo once
echo "BONSAI_TEMPLATE=git@github.com:Agundur-KDE/emptyContent.git" \
  >> ~/.config/bonsai/config

bonsai new myclient git@github.com:yourorg/myclient.git
```

BonsaiPress clones the template, strips the git history, wires up your remote,
and switches to the new project automatically.

After `bonsai new`, you'll see:

```
▶ Next steps:
  1. current/config/bonsai_config.php — fill in FTP credentials and domain
     ⚠  Contains passwords — already in .gitignore, never commit!
  2. bonsai static   — generate static HTML
  3. bonsai deploy   — deploy (creates web/ and include/ on server automatically)
  4. CI/CD optional: https://github.com/Agundur-KDE/ftp-hash-deploy-action
```

## Team workflow

Project configs are stored per-machine in `~/.config/bonsai/`. When you create
a new project and push it to GitHub, a colleague registers it on their machine:

```bash
bonsai add myclient git@github.com:yourorg/myclient.git
bonsai switch myclient   # clones on first switch
```

## Deploy

Add FTP credentials to `current/config/bonsai_config.php`, then:

```bash
bonsai static    # build
bonsai deploy    # upload changed files only
```

Uses explicit FTPS (port 21, cURL). No PHP ftp extension needed.
First deploy automatically creates `web/` and `include/` on Hetzner Managed servers.

## Running tests

```bash
composer install
./vendor/bin/phpunit
```

## Support

Found a bug or have a question? [Open an issue](https://github.com/Agundur-KDE/BonsaiPress2/issues) on GitHub.

## Credits

Based on [ecms3](https://sebastiany.net) by sebastiany.net.  
Rewritten and extended as BonsaiPress by [Agundur KDE](https://github.com/Agundur-KDE).

## License

MIT
