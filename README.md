# BonsaiPress

[![BonsaiPress — The CMS your AI assistant actually understands](https://www.agundur.de/_resources/pictures/BonsaiPress_LLM.png)](https://www.agundur.de/projects/bonsaipress.html)

**The CMS your AI assistant actually understands.**

No database. No admin UI. Just files, Git, and a shell — exactly how AI works best.

If BonsaiPress helps you build or maintain a website, [support continued development through GitHub Sponsors](https://github.com/sponsors/Agundur-KDE).

## Why AI loves BonsaiPress

Most CMS systems hide their state behind admin panels, databases, and plugin systems. AI assistants can't see any of that. BonsaiPress is different:

- **Shell-native** — every operation runs via `bonsai` CLI. No clicking through menus.
- **Flat files** — content is XML + HTML. Your AI reads, writes, and understands it directly.
- **No hidden state** — no database, no plugin magic. What you see is what gets deployed.
- **Static output** — pure HTML, maximum AI crawler visibility (GEO-optimized by design)
- **Claude Code Skill** — structured instructions that make Claude instantly proficient with BonsaiPress ([get it here](https://www.agundur.de/claude-skills.html))

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

## Install

> Everything runs inside Docker — PHP, Apache, Composer dependencies, the Sass watcher. You don't need PHP, Composer, or Apache installed on your machine, only **Docker** and **Git**.

### Step by step

1. **Install Docker** — [Docker Desktop](https://www.docker.com/products/docker-desktop) (Mac/Windows) or Docker Engine + Compose plugin (Linux). Make sure it's actually running (`docker info` should not error).
2. **Install Git** if you don't have it already.
3. **Clone the repo:**
   ```bash
   git clone https://github.com/Agundur-KDE/BonsaiPress2.git
   cd BonsaiPress2
   ```
4. **Register the `bonsai` CLI on your PATH:**
   ```bash
   ./bonsai install
   ```
   This symlinks `bonsai` into `~/.local/bin` or `~/bin` — whichever is already on your `PATH`. If neither is, the command tells you and prints the manual symlink command to run instead.
5. **Start the stack:**
   ```bash
   bonsai start
   ```
   First run pulls the `bonsaipress` and `bonsaipress-watcher` images from `ghcr.io/agundur-kde` (a few hundred MB) and boots three containers: CMS (`:8080`), Preview (`:8081`), Watcher (`:8001`).
6. **Open [http://localhost:8080](http://localhost:8080)** — you should see the demo project.
7. **Create your own project** (optional, once you're past the demo):
   ```bash
   bonsai new myclient
   ```
   Fill in `current/config/bonsai_config.php` with FTP credentials before deploying.

### Troubleshooting

**`docker: command not found`**
Docker isn't installed, or your shell doesn't see it yet. Install Docker Desktop / Engine, then open a new terminal.

**`Docker läuft nicht. Bitte Docker Desktop starten.`**
Docker is installed but the daemon isn't running. Start Docker Desktop (or on Linux: `sudo systemctl start docker`), then re-run `bonsai start`.

**`bonsai: command not found` after `./bonsai install`**
Your `PATH` doesn't include `~/.local/bin` or `~/bin`, so the installer couldn't place the symlink and printed a manual `ln -s` command instead — scroll up and run it, or add `~/.local/bin` to your `PATH` and re-run `./bonsai install`. Until then, keep invoking it as `./bonsai <command>` from the repo root.

**Port 8080 / 8081 / 8001 already in use**
Something else on your machine is bound to one of those ports. Stop it, or edit the port mappings in `compose.yml` (e.g. `"8080:80"` → `"9090:80"`).

**Permission denied on files under `clients/` or `current/`**
`bonsai` writes your host UID/GID into `.env` on every run so the container matches your user. If you ever ran the containers with plain `docker compose` (bypassing the `bonsai` wrapper), files may have been created as `root`. Fix with:
```bash
sudo chown -R $(id -u):$(id -g) clients/ current/
```
then always drive the stack via `bonsai ...`, not raw `docker compose`.

**Image pull fails / `unauthorized` from `ghcr.io`**
The images are public — a failed pull is usually a network issue or Docker Hub/GHCR rate limiting. Retry with `bonsai start`; if it persists, check `docker login ghcr.io` isn't caching bad credentials (`docker logout ghcr.io` and retry).

**Changes to content don't show up**
Static pages need an explicit rebuild — the CMS container doesn't auto-render:
```bash
bonsai static     # regenerate static HTML
```
Then check the **preview** on `:8081`, not `:8080` (`:8080` serves the live CMS, `:8081` serves the static build).

**`bonsai deploy` fails with an FTP/connection error**
Check `current/config/bonsai_config.php` for correct host/user/password. BonsaiPress uses explicit FTPS on port 21 — make sure your hosting firewall allows it and that you're not behind a proxy blocking outbound FTP.

**Still stuck?**
Run `bonsai status` for a quick health check, or [open an issue](https://github.com/Agundur-KDE/BonsaiPress2/issues) with the output of `docker compose -f compose.yml ps` and any error text.

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
