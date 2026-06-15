(function () {
    var ws, reconnectDelay = 1000;

    function connect() {
        ws = new WebSocket('ws://' + location.hostname + ':8001');

        ws.onmessage = function (e) {
            if (e.data === 'reload') {
                location.reload();
            }
        };

        ws.onclose = function () {
            setTimeout(connect, reconnectDelay);
        };
    }

    connect();
}());
