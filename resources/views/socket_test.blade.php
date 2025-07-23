<!DOCTYPE html>
<html>
<head>
    <title>Laravel WebSocket Test</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/7.2.0/pusher.min.js"></script>
</head>
<body>
    <h2>Laravel WebSocket Test - Listening to channel "game.23"</h2>
    <div id="output"></div>

    <script>
        // Setup Pusher (compatible with Beyondcode Laravel Websockets)
        Pusher.logToConsole = true;

        const pusher = new Pusher('local', {
            wsHost: 'housie.vikartrtechnologies.com',
            wsPort: 6001,
            wssPort: 6001,
            forceTLS: false, // true if using HTTPS
            encrypted: false,
            enabledTransports: ['ws', 'wss'],
            disableStats: true,
        });

        const channel = pusher.subscribe('game.23');

        // When subscription succeeds, trigger backend
        channel.bind('pusher:subscription_succeeded', function() {
            console.log('Subscribed to channel game.23');

            // 🔥 Trigger backend to start pushing
            fetch('/trigger-autopush/23')
                .then(response => response.json())
                .then(data => {
                    console.log('Auto push triggered:', data);
                });
        });

        channel.bind('NumberGenerated', function(data) {
            document.getElementById('output').innerHTML += '<p>Received: ' + JSON.stringify(data) + '</p>';
            console.log('Event:', data);
        });
    </script>
</body>
</html>
