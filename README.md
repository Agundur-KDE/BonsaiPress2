# BonsaiPress

The lean multi-client CMS. No database. Just files, Git, and speed.

## What it is

BonsaiPress is a PHP CMS that runs entirely without a database. Content lives in
files, structure lives in `site_structure.xml`, and deployment uploads only what
changed. One Docker command gets you started; one more deploys to production.

- **No database** — no MySQL, no migrations, no runtime dependencies
- **Static export** — generates pure HTML, blazing fast at runtime
- **Multi-client** — one CMS engine, unlimited client projects, switch in seconds
- **Hash-diff deploy** — only changed files are uploaded via FTPS

## Requirements

- [Docker](https://www.docker.com/products/docker-desktop)
- Git

That's it.

## Quick start

```bash
git clone https://github.com/Agundur-KDE/BonsaiPress2.git
cd BonsaiPress2
./bonsai install    # makes 'bonsai' available system-wide (once)
bonsai start        # Docker up — opens demo on :8080 automatically
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
│   │   └── master.scss          ← compiled by watcher
│   └── ecms_config.php          ← FTP, domain, CSS
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

## Deploy

Add FTP credentials to `current/config/ecms_config.php`, then:

```bash
bonsai static    # build
bonsai deploy    # upload changed files only
```

Uses explicit FTPS (port 21, cURL). No PHP ftp extension needed.

## Running tests

```bash
composer install
./vendor/bin/phpunit
```

## Credits

Based on [ecms3](https://sebastiany.net) by sebastiany.net.  
Rewritten and extended as BonsaiPress by [Agundur KDE](https://github.com/Agundur-KDE).

## License

MIT
