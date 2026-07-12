import asyncio
import filecmp
import os
import subprocess
import re
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
from pathlib import Path
import websockets

IGNORED_PATTERNS = re.compile(
    r"""
    (^|/)
    (\..+
     |.*~
     |.*\.(swp|swo|swx)
     |\.#.*
    )
    ($|/)
    """,
    re.VERBOSE
)

SASS_INPUT  = "current/config/sass/main.scss"
SASS_OUTPUT = "current/static/_resources/css/main.css"
SASS_LOAD_PATHS = [
    "cms/bootstrap/scss",
    "current/config/sass",
]

# sass_create_map (current/config/ecms_config.php) — wenn an, kompiliert der
# Watcher zusätzlich eine Sourcemap-Variante für :8080 zum Debuggen, aber
# NIEMALS nach SASS_OUTPUT: main.css ist die einzige Datei, die bonsai
# static/deploy anfasst, und muss immer 1:1 dem entsprechen, was live geht.
SASS_CREATE_MAP = os.environ.get("SASS_CREATE_MAP", "false").lower() == "true"
SASS_DEV_OUTPUT = "current/static/_resources/css/main.dev.css"

def is_ignored(path: str) -> bool:
    return bool(IGNORED_PATTERNS.search(str(Path(path))))

connected = set()

async def handler(websocket):
    connected.add(websocket)
    print(f"[{websocket.remote_address}] verbunden", flush=True)
    try:
        async for _ in websocket:
            pass
    except websockets.ConnectionClosed:
        pass
    finally:
        connected.discard(websocket)
        print(f"[{websocket.remote_address}] getrennt", flush=True)

async def send_reload():
    if connected:
        print(f"Sende 'reload' an {len(connected)} Browser...", flush=True)
        await asyncio.gather(*(ws.send("reload") for ws in connected))

def _sass(extra_flags: list[str], output: str, load_path_args: list[str]) -> bool:
    result = subprocess.run(
        ["sass"] + extra_flags
        + ["--silence-deprecation=mixed-decls", "--silence-deprecation=abs-percent"]
        + load_path_args + [f"{SASS_INPUT}:{output}"],
        capture_output=True, text=True
    )
    if result.returncode != 0:
        print(f"sass: fehler\n{result.stderr}", flush=True)
        return False
    return True

def run_sass():
    load_path_args = []
    for p in SASS_LOAD_PATHS:
        load_path_args += ["--load-path", p]

    if SASS_CREATE_MAP:
        # Dev-Variante mit Sourcemap für :8080 — eigener Pfad, main.css bleibt
        # unberührt. bonsai static/deploy kennen main.dev.css nicht und löschen
        # sie vor jedem Export, damit sie nie auf den Server kommt. Direkt auf
        # den finalen Namen kompiliert (kein .new-Staging): der mtime dieser
        # Datei hat keine Cache-Busting-Konsequenz außerhalb von :8080, ständige
        # Änderung ist hier erwünscht statt riskant, und die Sourcemap-Datei
        # referenziert so immer den richtigen Dateinamen.
        if _sass([], SASS_DEV_OUTPUT, load_path_args):
            print("sass: ok", flush=True)
        return

    # Ohne den Switch kompiliert der Watcher direkt nach main.css, mit
    # denselben Flags wie `bonsai static` (sonst erzeugen identische
    # SCSS-Quellen trotzdem unterschiedliche Bytes und der Content-Diff-
    # Schutz hier wie in `bonsai static` bumpt grundlos den mtime).
    #
    # In main.css.watcher.new bauen und nur übernehmen wenn sich der Inhalt
    # wirklich geändert hat — main.css versioniert den Cache-Buster für JEDE
    # Seite. Eigener Staging-Name (nicht main.css.new): `bonsai static` baut
    # unabhängig in main.css.new — geteilter Name hieß, dass ein Watcher-Lauf
    # mitten in einem `bonsai deploy`-Hash-Scan die Datei neu anlegen konnte,
    # nachdem deploy sie schon aufgeräumt hatte (TOCTOU-Race).
    new_output = f"{SASS_OUTPUT}.watcher.new"
    if not _sass(["--no-source-map", "--style=compressed"], new_output, load_path_args):
        return

    if os.path.exists(SASS_OUTPUT) and filecmp.cmp(new_output, SASS_OUTPUT, shallow=False):
        os.remove(new_output)
        print("sass: ok (unverändert)", flush=True)
    else:
        os.replace(new_output, SASS_OUTPUT)
        print("sass: ok", flush=True)

class ReloadOnChangeHandler(FileSystemEventHandler):
    def __init__(self, loop):
        self.loop = loop

    def _handle(self, path: str):
        if is_ignored(path):
            return
        print(f"Änderung: {path}", flush=True)
        if path.endswith(".scss"):
            run_sass()
        self.loop.call_soon_threadsafe(asyncio.create_task, send_reload())

    def on_modified(self, event):
        if not event.is_directory:
            self._handle(event.src_path)

    def on_created(self, event):
        if not event.is_directory:
            self._handle(event.src_path)

    def on_deleted(self, event):
        if not event.is_directory:
            self._handle(event.src_path)

    def on_moved(self, event):
        if event.is_directory:
            return
        if not is_ignored(event.src_path) and not is_ignored(getattr(event, "dest_path", "")):
            self._handle(event.dest_path)

async def main():
    print("WebSocket-Server läuft auf ws://0.0.0.0:8001", flush=True)
    loop = asyncio.get_running_loop()

    # Einmal Sass beim Start kompilieren
    run_sass()

    server = await websockets.serve(handler, "0.0.0.0", 8001)

    watch_path = Path("current").resolve()
    observer = Observer()
    observer.schedule(ReloadOnChangeHandler(loop), path=str(watch_path), recursive=True)
    observer.start()
    print(f"Überwache: {watch_path}", flush=True)

    await server.wait_closed()

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("Stopping bonsai_watcher.py", flush=True)
