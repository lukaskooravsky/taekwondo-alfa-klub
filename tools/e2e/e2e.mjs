// End-to-end test konceptov cez DevTools protokol (skutočné kliky myšou a písanie).
// node e2e.mjs <base> <cookies> <jpgPath>
import { spawn } from 'node:child_process';
import { readFileSync } from 'node:fs';

const [BASE, cookieFile, jpg] = process.argv.slice(2);
const chrome = spawn('C:/Program Files/Google/Chrome/Application/chrome.exe', [
  '--headless=new', '--disable-gpu', '--no-first-run', '--remote-debugging-port=9335',
  '--user-data-dir=' + process.env.TEMP + '/e2e-' + Date.now(), '--window-size=1280,1000', 'about:blank']);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let list;
for (let i = 0; i < 50; i++) { try { list = await (await fetch('http://127.0.0.1:9335/json/list')).json(); break; } catch { await sleep(200); } }
const ws = new WebSocket(list.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r));
let id = 0; const pending = new Map(); const errors = []; let loads = 0;
ws.addEventListener('message', (ev) => {
  const m = JSON.parse(ev.data);
  if (m.id && pending.has(m.id)) { pending.get(m.id)(m); pending.delete(m.id); }
  if (m.method === 'Runtime.exceptionThrown') errors.push(m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text);
  if (m.method === 'Page.loadEventFired') loads++;
  if (m.method === 'Page.javascriptDialogOpening') send('Page.handleJavaScriptDialog', { accept: true });
});
const send = (method, params = {}) => new Promise((r) => { const i = ++id; pending.set(i, r); ws.send(JSON.stringify({ id: i, method, params })); });
const js = async (expr) => { const r = await send('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true }); if (r.result?.exceptionDetails) throw new Error(expr.slice(0, 80) + ' → ' + r.result.exceptionDetails.exception?.description); return r.result?.result?.value; };
await send('Runtime.enable'); await send('Network.enable'); await send('Page.enable'); await send('DOM.enable');
const cookies = readFileSync(cookieFile, 'utf8').split('\n').filter(Boolean).map((l) => { const [n, ...v] = l.trim().split('='); return { name: n, value: v.join('=') }; });
const setCookies = async () => { for (const c of cookies) await send('Network.setCookie', { ...c, url: BASE + '/', secure: BASE.startsWith('https') }); };
await setCookies();

async function go(path) { const before = loads; await send('Page.navigate', { url: BASE + path }); for (let i = 0; i < 60 && loads === before; i++) await sleep(100); await sleep(900); }
async function waitLoad(before) { for (let i = 0; i < 100 && loads === before; i++) await sleep(100); await sleep(900); }
async function click(selector, nth = 0) {
  const pt = await js(`(() => { const el = document.querySelectorAll(${JSON.stringify(selector)})[${nth}]; if (!el) return null; el.scrollIntoView({block:'center'}); const r = el.getBoundingClientRect(); return {x: r.left + r.width/2, y: r.top + r.height/2}; })()`);
  if (!pt) throw new Error('nenájdené: ' + selector);
  const top = await js(`(() => { const el = document.elementFromPoint(${pt.x}, ${pt.y}); const t = document.querySelectorAll(${JSON.stringify(selector)})[${nth}]; return t === el || t.contains(el); })()`);
  if (!top) throw new Error('prekryté: ' + selector);
  for (const type of ['mousePressed', 'mouseReleased']) await send('Input.dispatchMouseEvent', { type, x: pt.x, y: pt.y, button: 'left', clickCount: 1 });
}
async function type(selector, text) { await click(selector); await send('Input.insertText', { text }); }
async function setValue(selector, value) { await js(`(() => { const el = document.querySelector(${JSON.stringify(selector)}); el.value = ${JSON.stringify(value)}; el.dispatchEvent(new Event('input', {bubbles:true})); })()`); }
const flash = () => js(`(document.querySelector('.flash') || {}).textContent || ''`);
const publicHas = async (path, needle) => { const r = await fetch(BASE + path); return { status: r.status, has: (await r.text()).includes(needle) }; };

const results = [];
const check = (name, ok, info = '') => { results.push((ok ? 'OK   ' : 'FAIL ') + name + (info ? ' — ' + info : '')); };
const T = 'E2E koncept ' + Date.now().toString(36);
try {
  // 1) nová aktualita → Uložiť ako koncept
  await go('/admin/post-edit.php');
  await type('input[name=title]', T);
  await type('.a-rich-inline .ql-editor', 'Úvod konceptu & test');
  await type('textarea[name=body] + .a-rich .ql-editor', 'Telo konceptu');
  let b = loads; await click('.a-actions button:not([name])'); await waitLoad(b);
  check('nová → koncept: hláška', (await flash()).includes('koncept'), await flash());
  const row = await js(`(() => { const li = Array.from(document.querySelectorAll('.a-item')).find(l => l.textContent.includes(${JSON.stringify(T)})); return li ? { badge: !!li.querySelector('.a-badge'), href: li.querySelector('a.a-btn').getAttribute('href') } : null; })()`);
  check('zoznam: štítok Koncept', row && row.badge);
  const pid = row.href.split('id=')[1];
  let pub = await publicHas('/aktuality.php', T); check('koncept nie je v zozname na webe', !pub.has);
  pub = await publicHas('/aktualita.php?id=' + pid, T); check('koncept cez priamu adresu → 404 pre návštevníka', pub.status === 404, 'HTTP ' + pub.status);
  pub = await publicHas('/', T); check('koncept nie je na úvode', !pub.has);
  await go('/aktualita.php?id=' + pid);
  check('tréner vidí koncept s pásikom', await js(`!!document.querySelector('.draft-flag') && document.body.textContent.includes(${JSON.stringify(T)})`));
  check('úvod bez dvojitého escapovania', await js(`document.querySelector('.article-lead').textContent.includes('Úvod konceptu & test')`));

  // 2) filter „Len koncepty“
  await go('/admin/posts.php');
  await js(`(() => { const s = document.querySelector('select[name=zverejnenie]'); s.value = 'draft'; s.dispatchEvent(new Event('input', {bubbles:true})); })()`);
  await sleep(300);
  check('filter Len koncepty', await js(`Array.from(document.querySelectorAll('.a-item:not([hidden])')).every(li => li.querySelector('.a-badge'))`) && await js(`document.querySelectorAll('.a-item:not([hidden])').length >= 1`));

  // 3) Enter v nadpise → uloží, stav ostane koncept
  await go('/admin/post-edit.php?id=' + pid);
  await click('input[name=title]');
  b = loads; await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13, text: '\r' }); await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'Enter', code: 'Enter', windowsVirtualKeyCode: 13 }); await waitLoad(b);
  check('Enter v nadpise nezverejní', (await flash()).includes('koncept'), await flash());

  // 4) Zverejniť s novou fotkou (zmenšovanie v prehliadači nesmie stratiť tlačidlo)
  await go('/admin/post-edit.php?id=' + pid);
  const node = await send('DOM.getDocument'); const q = await send('DOM.querySelector', { nodeId: node.result.root.nodeId, selector: 'input[name=image]' });
  await send('DOM.setFileInputFiles', { nodeId: q.result.nodeId, files: [jpg] });
  b = loads; await click('button[name=publish]'); await waitLoad(b);
  check('Zverejniť (s fotkou) → zverejnené', (await flash()).includes('zverejnené'), await flash());
  pub = await publicHas('/aktuality.php', T); check('zverejnená je na webe', pub.has);
  pub = await publicHas('/aktualita.php?id=' + pid, 'uploads/posts/'); check('zverejnená má fotku', pub.status === 200 && pub.has);

  // 5) Skryť z webu
  await go('/admin/post-edit.php?id=' + pid);
  b = loads; await click('button[name=draft]'); await waitLoad(b);
  check('Skryť z webu → koncept', (await flash()).includes('koncept'), await flash());
  pub = await publicHas('/aktuality.php', T); check('skrytá nie je na webe', !pub.has);

  // 6) Náhľad funguje
  await go('/admin/post-edit.php?id=' + pid);
  await click('[data-post-preview]'); await sleep(2000);
  check('náhľad sa otvorí', await js(`!!document.querySelector('.a-preview-modal')`));
  await click('.a-preview-modal .a-btn'); await sleep(300);

  // 7) album: nový = koncept, nahrať fotku, zverejniť
  await go('/admin/albums.php');
  await click('details.a-upload summary');
  await type('details.a-upload input[name=title]', T + ' album');
  b = loads; await click('details.a-upload .a-btn-primary:not(summary)'); await waitLoad(b);
  check('nový album → koncept', (await flash()).includes('koncept'), await flash());
  const aid = await js(`new URLSearchParams(location.search).get('id')`);
  const n2 = await send('DOM.getDocument'); const q2 = await send('DOM.querySelector', { nodeId: n2.result.root.nodeId, selector: '[data-album-upload] input[type=file]' });
  b = loads; await send('DOM.setFileInputFiles', { nodeId: q2.result.nodeId, files: [jpg] }); await waitLoad(b); await sleep(1500);
  check('fotka v albume', await js(`document.querySelectorAll('.a-photos img, .a-photo img').length >= 1`));
  pub = await publicHas('/galeria.php', T + ' album'); check('album-koncept s fotkou nie je na webe', !pub.has);
  pub = await publicHas('/galeria.php?album=' + aid, 'x'); check('album-koncept priamo → 404', pub.status === 404, 'HTTP ' + pub.status);
  b = loads; await click('button[name=publish]'); await waitLoad(b);
  check('Zverejniť album', (await flash()).includes('zverejnené'), await flash());
  pub = await publicHas('/galeria.php', T + ' album'); check('zverejnený album je na webe', pub.has);

  // upratanie testovacích dát
  await go('/admin/album.php?id=' + aid);
  await js(`(() => { const f = Array.from(document.querySelectorAll('form[data-confirm]')).find(f => f.querySelector('[name=action][value=delete-album]')); f.removeAttribute('data-confirm'); f.submit(); })()`); await sleep(1500);
  await go('/admin/posts.php');
  await js(`(() => { const li = Array.from(document.querySelectorAll('.a-item')).find(l => l.textContent.includes(${JSON.stringify(T)})); const f = li.querySelector('form'); f.removeAttribute('data-confirm'); f.submit(); })()`); await sleep(1500);
  pub = await publicHas('/aktuality.php', T); check('testovacie dáta zmazané', !pub.has);
} catch (e) { results.push('FAIL výnimka: ' + e.message); }
console.log(results.join('\n'));
console.log('JS chyby: ' + (errors.length ? errors.join(' | ') : 'žiadne'));
ws.close(); chrome.kill(); process.exit(0);
