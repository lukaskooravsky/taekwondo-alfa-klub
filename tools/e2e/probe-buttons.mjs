// Ovládanie Chrome cez DevTools protokol: prihlásený admin, úprava existujúcej aktuality, čo prekrýva tlačidlá.
import { spawn } from 'node:child_process';
import { readFileSync } from 'node:fs';

const [url, cookieFile, width] = process.argv.slice(2);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--remote-debugging-port=9333',
  '--user-data-dir=' + process.env.TEMP + '/cdp-prof-' + Date.now(), `--window-size=${width || 1280},1000`, 'about:blank']);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let ver;
for (let i = 0; i < 50; i++) { try { ver = await (await fetch('http://127.0.0.1:9333/json/list')).json(); break; } catch { await sleep(200); } }
const page = ver.find((t) => t.type === 'page');
const ws = new WebSocket(page.webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r));
let id = 0; const pending = new Map(); const logs = [];
ws.addEventListener('message', (ev) => {
  const m = JSON.parse(ev.data);
  if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
  if (m.method === 'Runtime.exceptionThrown') logs.push('EXC ' + JSON.stringify(m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text));
  if (m.method === 'Runtime.consoleAPICalled') logs.push('CONSOLE ' + m.params.type + ' ' + m.params.args.map((a) => a.value ?? a.description).join(' '));
  if (m.method === 'Log.entryAdded') logs.push('LOG ' + m.params.entry.level + ' ' + m.params.entry.text);
});
const send = (method, params = {}) => new Promise((r) => { const i = ++id; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
await send('Runtime.enable'); await send('Log.enable'); await send('Network.enable'); await send('Page.enable');
for (const line of readFileSync(cookieFile, 'utf8').split('\n').filter(Boolean)) {
  const [name, ...v] = line.trim().split('=');
  await send('Network.setCookie', { name, value: v.join('='), url: 'http://localhost:8000/' });
}
await send('Page.navigate', { url });
await sleep(4000);
const evalJs = async (expr) => (await send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true })).result?.result?.value;
console.log('TITLE', await evalJs('document.title'));
console.log('PROBE', JSON.stringify(await evalJs(`(() => {
  const out = {};
  document.querySelectorAll('.a-actions button, .a-actions a').forEach((b) => {
    b.scrollIntoView({ block: 'center' });
    const r = b.getBoundingClientRect();
    const top = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
    out[b.textContent.trim()] = { rect: [Math.round(r.left), Math.round(r.top), Math.round(r.width), Math.round(r.height)], hidden: b.hidden,
      topEl: top ? top.tagName + '.' + top.className + (top === b || b.contains(top) ? ' (OK)' : ' (PREKRYTÉ)') : null };
  });
  const form = document.querySelector('form.a-form');
  out.valid = form.checkValidity();
  out.invalid = Array.from(form.elements).filter((e) => e.willValidate && !e.checkValidity()).map((e) => e.tagName + '[' + e.type + '] ' + (e.name || e.className) + ': ' + e.validationMessage);
  return out;
})()`)));
const detail = await send('Runtime.evaluate', { returnByValue: true, expression: `(() => Array.from(document.querySelectorAll('.a-rich, .a-field, .a-drop, .a-actions, form > *')).map((b) => {
  const r = b.getBoundingClientRect();
  return b.tagName + '.' + b.className + ' top=' + Math.round(r.top) + ' h=' + Math.round(r.height) + ' pos=' + getComputedStyle(b).position;
}).join('\\n'))()` });
console.log('DETAIL\n' + (detail.result?.result?.value ?? JSON.stringify(detail.result)));
const chain = await send('Runtime.evaluate', { returnByValue: true, expression: `(() => {
  const b = document.querySelector('.a-actions button'); b.scrollIntoView({ block: 'center' });
  const r = b.getBoundingClientRect(); let el = document.elementFromPoint(r.left + 5, r.top + 5); const out = [];
  while (el) { const rr = el.getBoundingClientRect(); out.push(el.tagName + '.' + el.className + ' [' + Math.round(rr.top) + ',' + Math.round(rr.bottom) + '] pos=' + getComputedStyle(el).position + ' z=' + getComputedStyle(el).zIndex); el = el.parentElement; }
  out.push('editors: ' + Array.from(document.querySelectorAll('.ql-editor')).map(e => { const q = e.getBoundingClientRect(); return '[' + Math.round(q.top) + ',' + Math.round(q.bottom) + '] ' + e.className; }).join(' | '));
  out.push('btn [' + Math.round(r.top) + ',' + Math.round(r.bottom) + ']');
  return out.join('\\n');
})()` });
console.log('CHAIN\n' + chain.result?.result?.value);
const net = await send('Runtime.evaluate', { returnByValue: true, expression: `performance.getEntriesByType('resource').map(e => e.name + ' ' + (e.responseStatus || '')).join('\\n')` });
console.log('RES\n' + net.result?.result?.value);
console.log(logs.join('\n'));
ws.close(); chrome.kill();
process.exit(0);
