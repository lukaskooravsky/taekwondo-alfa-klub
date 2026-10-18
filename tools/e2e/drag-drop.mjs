// Drag & drop súborov cez DevTools protokol: node cdp-dnd.mjs <url> <cookies> <selector> <file1;file2> <checkJs>
import { spawn } from 'node:child_process';
import { readFileSync } from 'node:fs';

const [url, cookieFile, selector, files, check] = process.argv.slice(2);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--remote-debugging-port=9334',
  '--user-data-dir=' + process.env.TEMP + '/cdp-dnd-' + Date.now(), '--window-size=1280,1000', 'about:blank']);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let list;
for (let i = 0; i < 50; i++) { try { list = await (await fetch('http://127.0.0.1:9334/json/list')).json(); break; } catch { await sleep(200); } }
const ws = new WebSocket(list.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r));
let id = 0; const pending = new Map(); const logs = [];
ws.addEventListener('message', (ev) => {
  const m = JSON.parse(ev.data);
  if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
  if (m.method === 'Runtime.exceptionThrown') logs.push('EXC ' + (m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text));
});
const send = (method, params = {}) => new Promise((r) => { const i = ++id; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
const evalJs = async (expr) => (await send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true })).result?.result?.value;
await send('Runtime.enable'); await send('Network.enable'); await send('Page.enable');
for (const line of readFileSync(cookieFile, 'utf8').split('\n').filter(Boolean)) {
  const [name, ...v] = line.trim().split('=');
  await send('Network.setCookie', { name, value: v.join('='), url: new URL(url).origin + '/', secure: url.startsWith('https') });
}
await send('Page.navigate', { url });
await sleep(3500);
const pt = await evalJs(`(() => { const el = document.querySelector(${JSON.stringify(selector)}); el.scrollIntoView({block:'center'}); const r = el.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + Math.min(r.height / 2, 40) }; })()`);
const data = { items: [], files: files.split(';'), dragOperationsMask: 1 };
for (const type of ['dragEnter', 'dragOver', 'drop']) {
  await send('Input.dispatchDragEvent', { type, x: pt.x, y: pt.y, data });
  await sleep(150);
}
await sleep(6000);
console.log('RESULT', JSON.stringify(await evalJs(check)));
console.log(logs.join('\n'));
ws.close(); chrome.kill(); process.exit(0);
