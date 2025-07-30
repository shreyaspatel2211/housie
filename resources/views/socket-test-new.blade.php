<!DOCTYPE html>
<html>
<head>
    <title>Laravel WebSocket Test</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/7.2.0/pusher.min.js"></script>
</head>
<body>
    <h2>Laravel WebSocket Listener</h2>
    <div id="output"></div>

    <script>
        // Setup Pusher with your credentials (if using Laravel WebSockets, app_key must match)
        const pusher = new Pusher('local', {
            wsHost: 'housie.vikartrtechnologies.com',
            wsPort: 6001,
            wssPort: 6001,
            forceTLS: false, 
            encrypted: false,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
        });

        // Replace 23 with your actual game ID
        const channel = pusher.subscribe('game.24');

        channel.bind('App\\Events\\NumberGenerated', function(data) {
            console.log("Received:", data);
            document.getElementById('output').innerHTML += `<p>Number: ${data.number} (Game: ${data.gameId})</p>`;
        });
    </script>
</body>
</html>
