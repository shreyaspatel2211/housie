<!DOCTYPE html>
<html>

<head>
    <title>Reset Password</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body style="font-family: Arial; padding: 30px; background-color: #f0f0f0;">
    <div style="max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 10px;">
        <h2>Reset Your Password</h2>
        <form id="resetForm">
            <input type="hidden" id="user_id" name="user_id" value="{{ request('user_id') }}">
            <input type="hidden" id="ts" name="ts" value="{{ now()->timestamp }}"> <!-- Add timestamp here -->
            <div>
                <label>New Password:</label><br>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-top: 10px;">
                <label>Confirm Password:</label><br>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                    style="width: 100%; padding: 8px;">
            </div>
            <button type="submit" style="margin-top: 20px; padding: 10px 20px;">Submit</button>
        </form>
        <p id="message" style="margin-top: 15px;"></p>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const payload = {
                user_id: document.getElementById('user_id').value,
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value,
                ts: document.getElementById('ts').value, // Include the timestamp here
            };

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const res = await fetch('/api/forgotten-update-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            document.getElementById('message').innerText = data.message;
        });
    </script>
</body>

</html>
