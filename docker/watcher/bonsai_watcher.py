import asyncio
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

SASS_INPUT  = "current/config/sass/master.scss"
SASS_OUTPUT = "current/static/_resources/css/master.css"
SASS_LOAD_PATHS = [
    "cms/bootstrap/scss",
    "current/config/sass",
]

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

def run_sass():
    load_path_args = []
    for p in SASS_LOAD_PATHS:
        load_path_args += ["--load-path", p]

    result = subprocess.run(
        ["sass", "--no-source-map"] + load_path_args + [f"{SASS_INPUT}:{SASS_OUTPUT}"],
        capture_output=True, text=True
    )
    if result.returncode == 0:
        print("sass: ok", flush=True)
    else:
        print(f"sass: fehler\n{result.stderr}", flush=True)

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
