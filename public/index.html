<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #1e1e2f, #2d2d44);
            color: #d1d1d6;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: rgba(30, 30, 47, 0.95);
            border: 1px solid #3a3a52;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.7);
        }
        h1 {
            color: #b0b0b3;
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        p {
            color: #88898f;
            font-size: 1.1em;
            margin-bottom: 25px;
        }
        ul#steps {
            list-style: none;
            margin: 0 auto;
            max-width: 350px;
        }
        ul#steps li {
            font-size: 1em;
            margin-bottom: 15px;
            padding: 12px;
            background: #25253a;
            border-left: 4px solid #4a4a6a;
            border-radius: 5px;
            position: relative;
            transition: background 0.3s ease, border-color 0.3s ease;
        }
        ul#steps li:hover {
            background: #2d2d44;
            border-color: #5a5a7a;
        }
        ul#steps li.done {
            background: #2d2d44;
            border-color: #6a6a8a;
        }
        ul#steps li .check {
            position: absolute;
            right: 15px;
            color: #6a6a8a;
            font-size: 1.2em;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        ul#steps li.done .check {
            opacity: 1;
        }
        .success {
            background: linear-gradient(135deg, #2d2d44, #3a3a52);
        }
        .success h1 {
            color: #a0a0a6;
        }
        .success p {
            color: #9a9aa6;
        }
        @media (max-width: 480px) {
            .container { padding: 20px; margin: 20px; }
            h1 { font-size: 1.5em; }
        }
    </style>
</head>
<body>
    <div class="container" id="container">
        <h1>Verification Portal</h1>
        <p>Processing your verification request...</p>
        <ul id="steps">
            <li id="step1">Validating device...<span class="check">✓</span></li>
            <li id="step2">Authenticating identity...<span class="check">✓</span></li>
            <li id="step3">Checking security...<span class="check">✓</span></li>
            <li id="step4">Finalizing access...<span class="check">✓</span></li>
        </ul>
    </div>
    <script>
        const userId = new URLSearchParams(window.location.search).get('user');
        if (!userId || !/^\d+$/.test(userId)) {
            document.getElementById('container').innerHTML = `
                <h1>Error</h1>
                <p>Invalid or missing user ID. Please use the verification link from Discord.</p>
            `;
        } else {
            fetch('/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    setTimeout(() => document.getElementById('step1').classList.add('done'), 500);
                    setTimeout(() => document.getElementById('step2').classList.add('done'), 1500);
                    setTimeout(() => document.getElementById('step3').classList.add('done'), 2500);
                    setTimeout(() => document.getElementById('step4').classList.add('done'), 3500);
                    setTimeout(() => {
                        document.getElementById('container').classList.add('success');
                        document.getElementById('container').innerHTML = `
                            <h1>Verification Complete</h1>
                            <p>You are now verified. Return to Discord.</p>
                        `;
                    }, 4500);
                } else if (result.status === 'already_verified') {
                    document.getElementById('container').classList.add('success');
                    document.getElementById('container').innerHTML = `
                        <h1>Already Verified</h1>
                        <p>You’re set. Head back to Discord.</p>
                    `;
                } else {
                    document.getElementById('container').innerHTML = `
                        <h1>Error</h1>
                        <p>${result.message || 'Verification failed. Try again.'}</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('container').innerHTML = `
                    <h1>Error</h1>
                    <p>Verification failed. Please try again.</p>
                `;
            });
        }
    </script>
</body>
</html>
