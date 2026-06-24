FROM python:3.12-slim

# dart-sass binary
RUN apt-get update && apt-get install -y curl unzip && \
    SASS_VERSION=1.77.8 && \
    curl -sL "https://github.com/sass/dart-sass/releases/download/${SASS_VERSION}/dart-sass-${SASS_VERSION}-linux-x64.tar.gz" \
    | tar -xz -C /usr/local && \
    ln -s /usr/local/dart-sass/sass /usr/local/bin/sass && \
    apt-get remove -y curl unzip && apt-get autoremove -y && rm -rf /var/lib/apt/lists/*

RUN pip install --no-cache-dir watchdog websockets

WORKDIR /var/www

CMD ["python", "docker/watcher/bonsai_watcher.py"]
