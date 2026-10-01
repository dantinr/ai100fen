import assert from 'node:assert/strict';
import http from 'node:http';
import https from 'node:https';

// Check raw headers, including duplicates, without printing cookies or tokens.
const host = 'ai100.aicsi.cn';
const expectedRobots = 'User-agent: *\nAllow: /\n';
const paths = ['/', '/series/build-a-website', '/questions', '/robots.txt'];
const agents = ['AI100fen-Crawl-Check', 'Googlebot', 'Bingbot', 'GPTBot', 'ClaudeBot'];

function request(protocol, path, agent, method = 'GET') {
    return new Promise((resolve, reject) => {
        const client = protocol === 'https:' ? https : http;
        const req = client.request({protocol, hostname: host, path, method,
            headers: {'User-Agent': agent}}, (response) => {
            let body = '';
            response.setEncoding('utf8');
            response.on('data', (chunk) => { body += chunk; });
            response.on('error', reject);
            response.on('end', () => resolve({
                status: response.statusCode, headers: response.headers,
                rawHeaders: response.rawHeaders, body,
            }));
        });
        req.setTimeout(15000, () => req.destroy(new Error('HTTP verification timed out')));
        req.on('error', reject);
        req.end();
    });
}

function verifyTag(response) {
    const values = [];
    for (let i = 0; i < response.rawHeaders.length; i += 2) {
        if (response.rawHeaders[i].toLowerCase() === 'x-robots-tag') {
            values.push(response.rawHeaders[i + 1]);
        }
    }
    assert.deepEqual(values, ['noindex, nofollow'], 'Expect exactly one noindex header');
}

for (const path of paths) {
    for (const agent of agents) {
        const response = await request('https:', path, agent);
        assert.equal(response.status, 200, `${path}: ${agent}`);
        verifyTag(response);
        assert.equal(response.headers['x-content-type-options'], 'nosniff');
        assert.equal(response.headers['x-frame-options'], 'SAMEORIGIN');
        if (path === '/robots.txt') {
            assert.equal(response.body.replaceAll('\r\n', '\n'), expectedRobots);
            assert.match(response.headers['content-type'], /^text\/plain/);
        } else {
            assert.match(response.headers['content-type'], /^text\/html/);
            assert.match(response.body, /<meta name="robots" content="noindex, nofollow">/);
        }
    }
    const head = await request('https:', path, agents[0], 'HEAD');
    assert.equal(head.status, 200);
    verifyTag(head);
    const redirect = await request('http:', path, agents[0]);
    assert.equal(redirect.status, 301);
    assert.equal(redirect.headers.location, `https://${host}${path}`);
    verifyTag(redirect);
    console.log(`${path}: HTTPS GET/HEAD 200; 5 user agents; one noindex header; HTTP 301 to HTTPS`);
}

for (const [path, expected] of [['/.env', 403], ['/.git/config', 403],
    ['/admin', 404], ['/missing-crawl-check', 404], ['/me', 302]]) {
    const response = await request('https:', path, agents[0]);
    assert.equal(response.status, expected, `Security status changed: ${path}`);
    verifyTag(response);
    console.log(`${path}: ${expected}; noindex present`);
}

const favicon = await request('https:', '/favicon.ico', agents[0], 'HEAD');
assert.equal(favicon.status, 200);
verifyTag(favicon);
console.log('Static resource HEAD 200; noindex present. Crawl checks passed.');
