/* Admin ALFA — potvrdzovanie mazania, kontrola hesla, zmenšovanie a nahrávanie fotiek. */
(function () {
  "use strict";

  /* ---- Pravidlá hesla pri písaní (rovnaké ako auth_password_error na serveri) ---- */
  document.querySelectorAll("input[data-pw-check]").forEach(function (input) {
    const min = parseInt(input.getAttribute("minlength"), 10) || 8;
    const rules = [
      ["aspoň " + min + " znakov", function (v) { return Array.from(v).length >= min; }],
      ["veľké písmeno", function (v) { return /\p{Lu}/u.test(v); }],
      ["malé písmeno", function (v) { return /\p{Ll}/u.test(v); }],
      ["číslica", function (v) { return /\p{Nd}/u.test(v); }],
      ["špeciálny znak (napr. ! ? # * - _)", function (v) { return /[^\p{L}\p{N}\s]/u.test(v); }]
    ];
    const list = document.createElement("ul");
    list.className = "a-pw-rules";
    const items = rules.map(function (r) {
      const li = document.createElement("li");
      li.textContent = r[0];
      list.appendChild(li);
      return li;
    });
    input.insertAdjacentElement("afterend", list);
    function update() {
      rules.forEach(function (r, i) { items[i].classList.toggle("is-ok", r[1](input.value)); });
    }
    input.addEventListener("input", update);
    update();
  });

  /* ---- Mazanie na dve kliknutia (bez blokujúceho window.confirm) ---- */
  document.querySelectorAll("form[data-confirm]").forEach(function (form) {
    const btn = form.querySelector("button");
    const label = btn.textContent;
    let armed = false;
    form.addEventListener("submit", function (e) {
      if (armed) return;
      e.preventDefault();
      armed = true;
      btn.textContent = form.dataset.confirm;
      btn.classList.add("a-armed");
      setTimeout(function () {
        armed = false;
        btn.textContent = label;
        btn.classList.remove("a-armed");
      }, 4000);
    });
  });
  /* ---- Zmenšenie fotky v prehliadači (veľká 1600 px + náhľad 480 px, WebP) ---- */
  const FULL_MAX = 1600, THUMB_MAX = 480, QUALITY = 0.82;
  const canDecode = "createImageBitmap" in window;
  const canSetFiles = "DataTransfer" in window;

  function decode(file) {
    return createImageBitmap(file, { imageOrientation: "from-image" }).catch(function () {
      return createImageBitmap(file); // staršie prehliadače bez options
    });
  }
  function toBlob(canvas, type) {
    return new Promise(function (resolve) { canvas.toBlob(resolve, type, QUALITY); });
  }
  async function draw(bmp, max, name) {
    const scale = Math.min(1, max / Math.max(bmp.width, bmp.height));
    const canvas = document.createElement("canvas");
    canvas.width = Math.max(1, Math.round(bmp.width * scale));
    canvas.height = Math.max(1, Math.round(bmp.height * scale));
    canvas.getContext("2d").drawImage(bmp, 0, 0, canvas.width, canvas.height);
    let blob = await toBlob(canvas, "image/webp");
    if (!blob || blob.type !== "image/webp") blob = await toBlob(canvas, "image/jpeg");
    if (!blob) throw new Error("Fotku sa nepodarilo spracovať.");
    return new File([blob], name + (blob.type === "image/webp" ? ".webp" : ".jpg"), { type: blob.type });
  }
  async function makePair(file) {
    const bmp = await decode(file);
    try {
      return { full: await draw(bmp, FULL_MAX, "full"), thumb: await draw(bmp, THUMB_MAX, "thumb") };
    } finally {
      if (bmp.close) bmp.close();
    }
  }
  /*
   * Fotky sa posielajú ako TEXTOVÉ polia formulára {full, thumb} v base64 — PHP ich číta do pamäte a nepotrebuje
   * dočasný priečinok hostingu (na Webglobe je nezapisovateľný: súbor vo formulári ani JSON nad ~16 kB tam neprejde).
   */
  const PHOTO_MAX = 5 * 1024 * 1024; // bajtov jednej fotky (UPLOAD_MAX_BYTES)
  function blobToB64(blob) {
    return new Promise(function (resolve, reject) {
      const r = new FileReader();
      r.onload = function () { resolve(String(r.result).split(",")[1] || ""); };
      r.onerror = function () { reject(new Error("Fotku sa nepodarilo načítať.")); };
      r.readAsDataURL(blob);
    });
  }
  async function photoPayload(file) {
    let pair = null;
    if (canDecode) { try { pair = await makePair(file); } catch (e) { pair = null; } }
    if (pair) {
      if (pair.full.size > PHOTO_MAX) throw new Error("Fotka je po zmenšení stále príliš veľká — skús inú.");
      return { full: await blobToB64(pair.full), thumb: await blobToB64(pair.thumb), preview: pair.thumb, size: pair.full.size };
    }
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
      throw new Error("Fotku „" + file.name + "“ tento prehliadač nevie spracovať (napr. HEIC) — ulož ju ako JPG a skús znova.");
    }
    if (file.size > PHOTO_MAX) throw new Error("Fotka je príliš veľká a prehliadač ju nevie zmenšiť — ulož ju menšiu (do 5 MB).");
    return { full: await blobToB64(file), preview: file, size: file.size };   // zmenší server
  }
  async function postPhoto(url, data) {
    const fd = new FormData();
    Object.keys(data).forEach(function (k) { if (data[k] != null) fd.append(k, data[k]); });   // reťazce = textové polia, nie súbory
    let res;
    try {
      res = await fetch(url, { method: "POST", body: fd, credentials: "same-origin" });
    } catch (e) {
      throw new Error("Chyba pripojenia.");
    }
    let out = null;
    try { out = await res.json(); } catch (e) { out = null; }
    if (!res.ok || !out || !out.ok) throw new Error((out && out.error) || "Nahrávanie zlyhalo (" + res.status + ").");
    return out;
  }

  function setFiles(input, files) {
    const dt = new DataTransfer();
    files.forEach(function (f) { dt.items.add(f); });
    input.files = dt.files;
  }

  document.querySelectorAll("input[type=file][data-resize]").forEach(function (input) {
    if (!canDecode || !canSetFiles) return; // bez podpory pošle originál, zmenší server
    const form = input.form;
    const fullIn = form.querySelector("input[name=image_full]");
    const thumbIn = form.querySelector("input[name=image_thumb]");
    const status = form.querySelector("[data-resize-status]");
    const preview = form.querySelector("[data-resize-preview]");
    const origName = input.name;
    let busy = null;
    let previewUrl = null;

    function showPreview(blob) {
      if (previewUrl) URL.revokeObjectURL(previewUrl);
      previewUrl = blob ? URL.createObjectURL(blob) : null;
      preview.hidden = !blob;
      if (blob) preview.src = previewUrl;
    }

    let refIn = form.querySelector("input[name=image_ref]");
    if (!refIn) {
      refIn = document.createElement("input");
      refIn.type = "hidden";
      refIn.name = "image_ref";
      form.appendChild(refIn);
    }
    const csrfVal = (form.querySelector("input[name=csrf]") || {}).value || "";
    input.addEventListener("change", function () {
      setFiles(fullIn, []);
      setFiles(thumbIn, []);
      refIn.value = "";
      input.name = origName; // pri novom výbere znova posielať originál, kým nie je nahratý
      showPreview(null);
      const file = input.files[0];
      if (!file) { status.textContent = ""; return; }
      status.textContent = "Nahrávam fotku…";
      status.classList.remove("a-err");
      busy = (async function () {
        const p = await photoPayload(file);
        const out = await postPhoto("api/post-image.php", { csrf: csrfVal, full: p.full, thumb: p.thumb });
        refIn.value = out.ref;
        input.removeAttribute("name");   // výber ostane viditeľný, súbor sa už neposiela znova
        showPreview(p.preview);
        status.textContent = "Fotka nahratá (" + Math.round(p.size / 1024) + " KB) — ulož aktualitu.";
      })().catch(function (e) {
        input.name = origName;           // záloha: klasické odoslanie s formulárom (na bežných hostingoch funguje)
        status.textContent = e.message || "Fotku sa nepodarilo nahrať.";
        status.classList.add("a-err");
      }).finally(function () { busy = null; });
    });

    form.addEventListener("submit", function (e) {
      if (!busy) return;
      e.preventDefault();
      status.textContent = "Chvíľu počkaj, fotka sa ešte pripravuje…";
      const submitter = e.submitter || null; // Zverejniť / Do konceptov sa nesmie stratiť
      busy.then(function () {
        if (form.requestSubmit) form.requestSubmit(submitter);
        else {                                            // Safari < 16 nemá requestSubmit
          if (submitter && submitter.name) {
            const h = document.createElement("input");
            h.type = "hidden"; h.name = submitter.name; h.value = submitter.value;
            form.appendChild(h);
          }
          form.submit();
        }
      });
    });
  });
  /* ---- Hromadné nahrávanie do albumu (po jednej fotke, s priebehom) ---- */
  document.querySelectorAll("[data-album-upload]").forEach(function (box) {
    const input = box.querySelector("input[type=file]");
    const status = box.querySelector("[data-upload-status]");
    const failsEl = box.querySelector("[data-upload-fails]");
    const csrf = box.querySelector("input[name=csrf]").value;

    input.addEventListener("change", function () {
      const files = Array.from(input.files);
      input.value = "";
      if (files.length) run(files);
    });

    async function uploadOne(file) {
      const p = await photoPayload(file);
      await postPhoto(box.dataset.endpoint, { csrf: csrf, album: box.dataset.album, full: p.full, thumb: p.thumb });
    }

    function warnLeave(e) { e.preventDefault(); e.returnValue = ""; }

    async function run(files) {
      failsEl.innerHTML = "";
      input.disabled = true;
      window.addEventListener("beforeunload", warnLeave); // prehliadač sa spýta pred odchodom zo stránky
      const failed = [];
      for (let i = 0; i < files.length; i++) {
        status.textContent = "Nahrávam " + (i + 1) + " / " + files.length + "…";
        try { await uploadOne(files[i]); }
        catch (err) { failed.push({ file: files[i], error: err.message }); }
      }
      window.removeEventListener("beforeunload", warnLeave);
      input.disabled = false;
      if (!failed.length) {
        status.textContent = "Hotovo — nahraných " + files.length + ".";
        location.reload();
        return;
      }
      status.textContent = "Nahraných " + (files.length - failed.length) + " z " + files.length + ". Tieto sa nepodarili:";
      failed.forEach(function (f) {
        const li = document.createElement("li");
        li.textContent = f.file.name + " — " + f.error;
        failsEl.appendChild(li);
      });
      const li = document.createElement("li");
      const retry = document.createElement("button");
      retry.type = "button";
      retry.className = "a-btn";
      retry.textContent = "Skúsiť znova neúspešné";
      retry.addEventListener("click", function () { run(failed.map(function (f) { return f.file; })); });
      const reload = document.createElement("button");
      reload.type = "button";
      reload.className = "a-btn";
      reload.textContent = "Zobraziť nahrané";
      reload.style.marginLeft = ".5rem";
      reload.addEventListener("click", function () { location.reload(); });
      li.appendChild(retry);
      li.appendChild(reload);
      failsEl.appendChild(li);
    }
  });



  /* ---------- vyhľadávanie a filter v zoznamoch (aktuality, albumy) — hneď pri písaní ---------- */
  const fold = function (s) { return String(s).normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/\s+/g, " ").trim(); };
  document.querySelectorAll("form[data-filter]").forEach(function (form) {
    const list = document.querySelector("[data-filter-list]");
    if (!list) return;
    const items = Array.from(list.children);
    const count = form.querySelector("[data-filter-count]");
    const empty = document.querySelector("[data-filter-empty]");
    const reset = form.querySelector(".a-filter-reset");
    const submit = form.querySelector("[data-filter-submit]");
    if (submit) submit.hidden = true; // filtruje sa hneď
    let timer = null;
    function apply() {
      const q = form.elements.q.value, year = form.elements.rok.value, flag = form.elements.stav.value, sort = form.elements.zoradit.value;
      const pub = form.elements.zverejnenie ? form.elements.zverejnenie.value : "";
      const words = fold(q).split(" ").filter(Boolean);
      let shown = 0;
      items.forEach(function (li) {
        const ok = words.every(function (w) { return li.dataset.fold.indexOf(w) >= 0; }) &&
          (!year || li.dataset.year === year) && (!flag || li.dataset.flag === flag) && (!pub || li.dataset.status === pub);
        li.hidden = !ok;
        if (ok) shown++;
      });
      const sorted = items.slice().sort(function (a, b) {
        if (sort === "old") return a.dataset.date < b.dataset.date ? -1 : a.dataset.date > b.dataset.date ? 1 : 0;
        if (sort === "az") return a.dataset.title.localeCompare(b.dataset.title, "sk");
        return a.dataset.date < b.dataset.date ? 1 : a.dataset.date > b.dataset.date ? -1 : 0;
      });
      sorted.forEach(function (li) { list.appendChild(li); });
      count.textContent = "Zobrazené " + shown + " z " + items.length;
      if (empty) empty.hidden = shown > 0;
      const active = q.trim() !== "" || year || flag || pub || sort !== "new";
      if (reset) reset.hidden = !active;
      // filter ostane v adrese (späť, obnovenie stránky, záložka)
      const params = new URLSearchParams();
      if (q.trim()) params.set("q", q.trim());
      if (year) params.set("rok", year);
      if (flag) params.set("stav", flag);
      if (pub) params.set("zverejnenie", pub);
      if (sort !== "new") params.set("zoradit", sort);
      const qs = params.toString();
      history.replaceState(null, "", location.pathname + (qs ? "?" + qs : ""));
    }
    form.addEventListener("input", function (e) {
      clearTimeout(timer);
      timer = setTimeout(apply, e.target.name === "q" ? 120 : 0);
    });
    form.addEventListener("submit", function (e) { e.preventDefault(); apply(); });
    if (reset) reset.addEventListener("click", function (e) {
      e.preventDefault();
      form.reset();
      form.elements.q.value = "";
      ["rok", "stav", "zverejnenie"].forEach(function (n) { if (form.elements[n]) form.elements[n].value = ""; });
      form.elements.zoradit.value = "new";
      apply();
      form.elements.q.focus();
    });
    if (empty) {
      const emptyLink = empty.querySelector("a");
      if (emptyLink && reset) emptyLink.addEventListener("click", function (e) { e.preventDefault(); reset.click(); });
    }
    // "/" skočí do vyhľadávania (ako na GitHube)
    document.addEventListener("keydown", function (e) {
      if (e.key === "/" && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) && !document.activeElement.isContentEditable) {
        e.preventDefault();
        form.elements.q.focus();
      }
    });
  });

  /* ---------- neuložené zmeny: pred odchodom zo stránky sa prehliadač opýta ---------- */
  document.querySelectorAll("form[data-unsaved]").forEach(function (form) {
    let dirty = form.dataset.unsaved === "dirty";   // po chybe vo formulári sú zmeny stále neuložené
    form.addEventListener("input", function () { dirty = true; });
    form.addEventListener("change", function () { dirty = true; });
    form.addEventListener("rich-change", function () { dirty = true; }); // zmena v editore (Quill)
    form.addEventListener("submit", function () { dirty = false; });
    window.addEventListener("beforeunload", function (e) {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = "";
    });
  });

  /* ---------- pretiahnutie fotiek myšou (album, titulná fotka aktuality) ---------- */
  const IMAGE_EXT = /\.(jpe?g|png|webp|gif|avif|bmp|heic|heif|tiff?)$/i;
  function isImageFile(f) { return /^image\//.test(f.type) || IMAGE_EXT.test(f.name); }
  const NOT_IMAGE = "Toto nie je fotka. Pretiahni súbor JPG, PNG alebo WebP.";
  function dropZone(el, onFiles, onReject) {
    let depth = 0;
    el.addEventListener("dragenter", function (e) {
      if (!e.dataTransfer || Array.from(e.dataTransfer.types).indexOf("Files") < 0) return;
      e.preventDefault();
      depth++;
      el.classList.add("is-drag");
    });
    el.addEventListener("dragover", function (e) {
      if (!e.dataTransfer || Array.from(e.dataTransfer.types).indexOf("Files") < 0) return;
      e.preventDefault();
      e.dataTransfer.dropEffect = "copy";
    });
    el.addEventListener("dragleave", function () {
      if (--depth <= 0) { depth = 0; el.classList.remove("is-drag"); }
    });
    el.addEventListener("drop", function (e) {
      depth = 0;
      el.classList.remove("is-drag");
      if (!e.dataTransfer || !e.dataTransfer.files.length) return;
      e.preventDefault();
      const all = Array.from(e.dataTransfer.files);
      const files = all.filter(isImageFile);
      if (files.length) onFiles(files);
      if (files.length < all.length && onReject) onReject(all.length - files.length);
    });
  }
  document.querySelectorAll("[data-album-upload]").forEach(function (box) {
    const input = box.querySelector("input[type=file]");
    const status = box.querySelector("[data-upload-status]");
    dropZone(box, function (files) {
      if (!canSetFiles) return;
      setFiles(input, files);
      input.dispatchEvent(new Event("change"));
    }, function (n) { if (status) status.textContent = (n === 1 ? "1 súbor nie je fotka" : n + " súbory nie sú fotky") + " — preskočené."; });
  });
  document.querySelectorAll("input[type=file][data-resize]").forEach(function (input) {
    const zone = input.closest(".a-field");
    if (!zone || !canSetFiles) return;
    zone.classList.add("a-drop");
    const status = zone.querySelector("[data-resize-status]");
    dropZone(zone, function (files) {
      setFiles(input, [files[0]]);
      input.dispatchEvent(new Event("change"));
    }, function () { if (status) status.textContent = NOT_IMAGE; });
  });

  /* ---------- formátovací editor (Quill) pre textarea[data-rich="full|inline"] ---------- */
  // Povolené písma, veľkosti, farby a médiá musia sedieť s RICH_* v inc/rich.php (server ostatné zahodí).
  const RICH_COLORS = ["#ffffff", "#f3efe6", "#c3bdb1", "#948e83", "#e6bd6a", "#c8a04e", "#ffd54f", "#ff9f43",
    "#ff8a65", "#ef5350", "#f48fb1", "#ba68c8", "#4fc3f7", "#80cbc4", "#66bb6a", "#aed581"];
  const RICH_BACKGROUNDS = ["#5c4a1f", "#3a2e14", "#4a1f1f", "#1f3d2a", "#1a3247", "#3b2449", "#2a2a31", "#4d3b10"];
  // emoji po kategóriách [ikona záložky, názov, zoznam]
  const EMOJI = [
    ["🥋", "Taekwondo", "🥋 🙇 🙇‍♂️ 🙇‍♀️ 🦵 🦶 👊 🤜 🤛 ✊ ✋ 🖐️ 🤚 💪 🤼 🤼‍♂️ 🤼‍♀️ 🤸 🧘 🎽 🥊 🛡️ ⚔️ 🐉 🐯 🦅 🌀 💥 ⚡ 🔥 🎯 ⏱️ 🏅 🥇 🥈 🥉 🏆 🎖️ ⚪ 🟡 🟢 🔵 🔴 ⚫ 🇰🇷 🇸🇰 🧒 👧 👦 📣 📅 🏟️"],
    ["⚽", "Šport", "🥋 🥊 🤸 🤼 🏋️ 🧘 🏃 🤾 ⛹️ 🚴 🏊 🧗 🤺 🥅 ⚽ 🏀 🏐 🎾 🏓 🏸 ⛸️ 🎿 🏂 🛹 🥏 🎯 ⏱️ 🧭 🦵 🦶 💪 🧠 🥤 🍌"],
    ["🏆", "Úspech", "🏆 🥇 🥈 🥉 🏅 🎖️ 🎗️ 🏵️ 👑 💯 🔝 ✅ ☑️ ✔️ 🎯 📈 🚀 ⭐ 🌟 ✨ 💫 🔥 ⚡ 💥 🎉 🎊 🥳 🎈 🎁 🍾 🥂"],
    ["👏", "Ľudia a gestá", "👏 🙌 👍 👎 👊 🤜 🤛 ✊ ✌️ 🤞 🤟 🤘 👌 🤙 👋 🙏 🤝 💁 🙋 🙆 🙅 🤷 👨‍👩‍👧‍👦 👪 🧒 👦 👧 🧑 👨 👩 👨‍🏫 👩‍🏫 👮"],
    ["😀", "Tváre", "😀 😃 😄 😁 😆 😅 😂 🤣 😊 🙂 😉 😍 🥰 😘 🤩 😎 🤗 🤔 🤨 😐 😮 😯 😲 🥺 😢 😭 😤 😡 🤯 😳 🥵 🥶 😴 🤒 🤕 😇 🤠 🥳 😬 🙃 😜 🤪"],
    ["❤️", "Srdcia", "❤️ 🧡 💛 💚 💙 💜 🤎 🖤 🤍 💕 💞 💓 💗 💖 💘 💝 💌 💯"],
    ["📅", "Čas a miesto", "📅 🗓️ 📆 ⏰ ⏱️ ⏳ ⌛ 🕐 🕒 🕔 🕕 🕖 🕗 📍 📌 🗺️ 🧭 🏫 🏟️ 🏠 🏢 🏛️ 🏰 ⛺ 🏖️ 🏔️ 🌍 🌎 🌏"],
    ["📣", "Oznamy", "📣 📢 🔔 🔕 📯 📝 ✏️ 🖊️ 📋 📎 📞 📱 ✉️ 📧 📨 💬 🗨️ 💭 ℹ️ ⚠️ ❗ ❕ ❓ ❔ ‼️ ⁉️ 🆕 🆓 🆗 🔜 🔗 📸 📷 🎥 📹 🎬 🎤 🎵 🎶 📰 📖 📚 🎓 💡 💰 💶 🎟️ 🎫"],
    ["☀️", "Príroda a počasie", "☀️ 🌤️ ⛅ 🌥️ 🌦️ 🌧️ ⛈️ 🌩️ ❄️ ☃️ ⛄ 🌨️ 💨 🌪️ 🌈 ☔ 🌊 🔥 🌙 ⭐ 🌸 🌼 🌻 🌷 🍀 🌿 🌲 🍂 🍁 🐉 🐯 🦅 🐻 🐺 🦁"],
    ["🍕", "Jedlo", "🍕 🍔 🌭 🍟 🥪 🌮 🍝 🍜 🍣 🥗 🍎 🍌 🍉 🍇 🍓 🍒 🥝 🥑 🥕 🥨 🍞 🧀 🥚 🍰 🎂 🧁 🍩 🍪 🍫 🍬 🍭 🍦 ☕ 🍵 🧃 🥤 💧"],
    ["🚌", "Doprava", "🚌 🚍 🚎 🚐 🚗 🚕 🚙 🚓 🚑 🚒 🚲 🛴 🚂 🚆 🚇 🚊 🚉 ✈️ 🛫 🛬 🚢 ⛴️ 🚀 🛣️ 🚦 🅿️ ⛽"],
    ["➡️", "Symboly", "➡️ ⬅️ ⬆️ ⬇️ ↗️ ↘️ ↔️ 🔄 🔁 ▶️ ⏸️ ⏹️ ⏺️ 🔴 🟠 🟡 🟢 🔵 🟣 ⚫ ⚪ 🟥 🟧 🟨 🟩 🟦 🟪 ⬛ ⬜ 🔶 🔷 🔸 🔹 ✳️ ❇️ ➕ ➖ ✖️ ➗ 🔢 #️⃣ 1️⃣ 2️⃣ 3️⃣ 4️⃣ 5️⃣ 🔟 ☯️ ☮️ ⚖️ ♻️ ⛔ 🚫 🔞"],
    ["🇸🇰", "Vlajky", "🇸🇰 🇨🇿 🇰🇷 🇰🇵 🇪🇺 🇦🇹 🇭🇺 🇵🇱 🇩🇪 🇬🇧 🇮🇪 🇫🇷 🇮🇹 🇪🇸 🇵🇹 🇳🇱 🇧🇪 🇨🇭 🇸🇮 🇭🇷 🇷🇸 🇧🇬 🇷🇴 🇬🇷 🇹🇷 🇺🇦 🇸🇪 🇳🇴 🇫🇮 🇩🇰 🇺🇸 🇨🇦 🇯🇵 🇨🇳 🏳️ 🏴 🏁 🚩 🏳️‍🌈"]
  ].map(function (c) { return { icon: c[0], name: c[1], list: c[2].split(" ") }; });
  const ICONS = {
    undo: '<svg viewBox="0 0 18 18"><path class="ql-stroke" d="M6 4 3 7l3 3"/><path class="ql-stroke" d="M3 7h7.5a4 4 0 0 1 0 8H8"/></svg>',
    redo: '<svg viewBox="0 0 18 18"><path class="ql-stroke" d="m12 4 3 3-3 3"/><path class="ql-stroke" d="M15 7H7.5a4 4 0 0 0 0 8H10"/></svg>',
    divider: '<svg viewBox="0 0 18 18"><line class="ql-stroke" x1="2" x2="16" y1="9" y2="9"/><line class="ql-stroke ql-thin" x1="5" x2="13" y1="4" y2="4"/><line class="ql-stroke ql-thin" x1="5" x2="13" y1="14" y2="14"/></svg>',
    emoji: '<svg viewBox="0 0 18 18"><circle class="ql-stroke" cx="9" cy="9" r="7"/><path class="ql-stroke" d="M6 10.5a3.5 3.5 0 0 0 6 0"/><circle class="ql-fill" cx="6.6" cy="7" r="1"/><circle class="ql-fill" cx="11.4" cy="7" r="1"/></svg>',
    preview: '<svg viewBox="0 0 18 18"><path class="ql-stroke" d="M1.5 9S4.5 3.5 9 3.5 16.5 9 16.5 9 13.5 14.5 9 14.5 1.5 9 1.5 9Z"/><circle class="ql-stroke" cx="9" cy="9" r="2.5"/></svg>',
    fullscreen: '<svg viewBox="0 0 18 18"><path class="ql-stroke" d="M2 6V2h4M12 2h4v4M16 12v4h-4M6 16H2v-4"/></svg>'
  };
  const TITLES = { undo: "Späť (Ctrl+Z)", redo: "Znova (Ctrl+Y)", bold: "Tučné (Ctrl+B)", italic: "Kurzíva (Ctrl+I)",
    underline: "Podčiarknuté (Ctrl+U)", strike: "Prečiarknuté", link: "Odkaz", image: "Vložiť fotky (dajú sa aj pretiahnuť myšou alebo vložiť Ctrl+V)",
    video: "Vložiť video z YouTube", divider: "Oddeľovacia čiara", emoji: "Emoji", blockquote: "Citát / zvýraznený rámček",
    clean: "Zrušiť formátovanie", preview: "Náhľad, ako to bude vyzerať na webe", fullscreen: "Celá obrazovka (Esc zavrie)",
    "list-ordered": "Číslovaný zoznam", "list-bullet": "Odrážky" };
  const API = { image: "api/inline-image.php", preview: "api/preview.php" };

  /** Okno s náhľadom stránky so štýlmi webu (base = koreň webu, aby sedeli cesty k CSS, fontom aj fotkám). */
  function previewModal(content, onClose) {
    const dlg = document.createElement("div");
    dlg.className = "a-preview-modal";
    dlg.setAttribute("role", "dialog");
    dlg.setAttribute("aria-modal", "true");
    dlg.setAttribute("aria-label", "Náhľad");
    dlg.innerHTML = '<div class="a-preview-bar"><strong>Náhľad — takto to uvidia návštevníci</strong>' +
      '<button type="button" class="a-btn">Zavrieť (Esc)</button></div><iframe title="Náhľad"></iframe>';
    dlg.querySelector("iframe").srcdoc = '<!DOCTYPE html><html lang="sk"><head><meta charset="utf-8">' +
      '<base href="' + new URL("../", location.href).href + '" target="_blank">' +
      '<meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="' + (document.body.dataset.siteFonts || "css/fonts.css") + '">' +
      '<link rel="stylesheet" href="' + (document.body.dataset.siteCss || "css/style.css") + '"><style>.reveal{opacity:1!important;transform:none!important}' +
      '.a-pv-label{font:600 .8rem/1 var(--font-display);letter-spacing:.14em;text-transform:uppercase;color:var(--gold);margin:2rem 0 1rem}' +
      '</style></head><body>' + content + "</body></html>";
    function close() {
      dlg.remove();
      document.documentElement.classList.remove("a-noscroll");
      document.removeEventListener("keydown", onKey, true);
      if (onClose) onClose();
    }
    function onKey(e) { if (e.key === "Escape") { e.stopPropagation(); close(); } }
    dlg.querySelector("button").addEventListener("click", close);
    document.addEventListener("keydown", onKey, true);
    document.documentElement.classList.add("a-noscroll");
    document.body.appendChild(dlg);
    dlg.querySelector("button").focus();
  }

  async function previewFetch(fields) {
    const fd = new FormData();
    Object.keys(fields).forEach(function (k) { fd.append(k, fields[k]); });
    const res = await fetch(API.preview, { method: "POST", body: fd, credentials: "same-origin" });
    let data = null;
    try { data = await res.json(); } catch (e) { data = null; }
    if (!res.ok || !data || !data.ok) throw new Error((data && data.error) || "Náhľad zlyhal.");
    return data;
  }

  function formError(form, msg) {
    let box = form.querySelector(".a-preview-err");
    if (!box) {
      box = document.createElement("div");
      box.className = "flash flash-error a-preview-err";
      box.setAttribute("role", "alert");
      form.prepend(box);
    }
    box.textContent = msg;
  }

  /* náhľad celej aktuality (karta v zozname + detail) pred uložením */
  document.querySelectorAll("[data-post-preview]").forEach(function (btn) {
    const form = btn.form;
    btn.hidden = false;
    btn.addEventListener("click", async function () {
      const val = function (n) { const el = form.elements[n]; return el && el.value !== undefined ? el.value : ""; };
      btn.disabled = true;
      let data;
      try {
        data = await previewFetch({ csrf: val("csrf"), mode: "post", title: val("title"), date: val("date"),
          excerpt: val("excerpt"), body: val("body") });
      } catch (e) {
        formError(form, e.message);
        return;
      } finally {
        btn.disabled = false;
      }
      // fotka: novo vybraná (náhľad z prehliadača), inak súčasná, ak sa nemaže
      const fresh = form.querySelector("img[data-resize-preview]:not([hidden])");
      const current = form.querySelector("img.a-preview:not([data-resize-preview])");
      const removing = form.elements.remove_image && form.elements.remove_image.checked;
      const src = fresh ? fresh.src : (current && !removing ? current.src : "");
      const wrap = document.createElement("div");
      wrap.innerHTML = '<div class="container"><p class="a-pv-label">V zozname aktualít</p><div class="posts" style="max-width:420px">' +
        data.card + '</div><p class="a-pv-label">Celý článok</p></div>' + data.article;
      if (src) {
        wrap.querySelectorAll(".post-img").forEach(function (i) { i.setAttribute("src", src); });
        const art = wrap.querySelector(".article");
        if (art && !art.querySelector(".article-img")) {
          const fig = document.createElement("figure");
          fig.className = "article-img";
          const img = document.createElement("img");
          img.alt = "";
          img.setAttribute("src", src);
          fig.appendChild(img);
          art.prepend(fig);
        }
      }
      previewModal(wrap.innerHTML, function () { btn.focus(); });
    });
  });

  const richBoxes = document.querySelectorAll("textarea[data-rich]");
  if (richBoxes.length && window.Quill) {
    const Font = Quill.import("formats/font");
    Font.whitelist = ["saira", "georgia", "arial", "courier"];
    Quill.register(Font, true);
    const BlockEmbed = Quill.import("blots/block/embed");
    class Divider extends BlockEmbed {}
    Divider.blotName = "divider";
    Divider.tagName = "hr";
    Quill.register(Divider, true);
    const icons = Quill.import("ui/icons");
    Object.keys(ICONS).forEach(function (k) { icons[k] = ICONS[k]; });
  }

  // fotky sa ukladajú ako uploads/…, v admine (/admin/) sa musia zobraziť cez ../uploads/…
  const toEditor = function (html) { return html.replace(/(<img[^>]+src=")uploads\//g, "$1../uploads/"); };
  const toStored = function (html) { return html.replace(/(<img[^>]+src=")(?:\.\.\/)+uploads\//g, "$1uploads/"); };
  const INLINE_IMG = /^(?:https?:\/\/[^/]+)?(?:\.\.)?\/?uploads\/(inline\/i_[a-z0-9]{6,32}\.(?:jpg|png|webp))$/;
  function youtubeId(url) {
    const m = String(url).trim().match(/^https?:\/\/(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([A-Za-z0-9_-]{11})(?:[?&#/].*)?$/);
    return m ? m[1] : null;
  }

  richBoxes.forEach(function (ta) {
    if (!window.Quill) return; // bez editora ostane obyčajná textarea (server ju berie ako prostý text)
    const inline = ta.dataset.rich === "inline";
    const max = parseInt(ta.dataset.max || "0", 10);
    const form = ta.form;
    const csrfIn = form && form.querySelector("input[name=csrf]");
    const fontSize = [{ font: [false, "saira", "georgia", "arial", "courier"] }, { size: ["small", false, "large", "huge"] }];
    const colors = [{ color: RICH_COLORS }, { background: RICH_BACKGROUNDS }];
    const toolbar = inline
      ? [["undo", "redo"], fontSize, ["bold", "italic", "underline"], colors, ["link", "emoji", "clean"]]
      : [["undo", "redo"], [{ header: [false, 2, 3] }], fontSize, ["bold", "italic", "underline", "strike"], colors,
        [{ align: [] }, { list: "ordered" }, { list: "bullet" }, "blockquote"],
        ["link", "image", "video", "divider", "emoji"], ["clean"], ["preview", "fullscreen"]];
    const formats = ["font", "size", "bold", "italic", "underline", "color", "background", "link"]
      .concat(inline ? [] : ["strike", "header", "align", "list", "blockquote", "image", "video", "divider"]);

    const box = document.createElement("div");
    box.className = "a-rich" + (inline ? " a-rich-inline" : "");
    const host = document.createElement("div");
    box.appendChild(host);
    const statusBar = document.createElement("div");
    statusBar.className = "a-rich-status";
    const statusMsg = document.createElement("span");
    statusMsg.className = "a-rich-msg";
    statusMsg.setAttribute("role", "status");
    const statusCount = document.createElement("span");
    statusBar.appendChild(statusMsg);
    statusBar.appendChild(statusCount);
    box.appendChild(statusBar);
    ta.hidden = true;
    ta.parentNode.insertBefore(box, ta.nextSibling);

    let msgTimer = null;
    function say(text, isErr) {
      statusMsg.textContent = text;
      statusMsg.classList.toggle("a-err", !!isErr);
      clearTimeout(msgTimer);
      if (!isErr && text) msgTimer = setTimeout(function () { statusMsg.textContent = ""; }, 4000);
    }

    let quill;
    const handlers = {
      undo: function () { quill.history.undo(); },
      redo: function () { quill.history.redo(); },
      emoji: function () { picker.hidden = !picker.hidden; },
      divider: function () {
        const r = quill.getSelection(true);
        quill.insertEmbed(r.index, "divider", true, "user");
        quill.setSelection(r.index + 1, 0, "user");
      },
      image: function () {
        const fi = document.createElement("input");
        fi.type = "file";
        fi.accept = "image/*";
        fi.multiple = true;
        fi.addEventListener("change", function () { uploadImages(quill.getSelection(true), Array.from(fi.files)); });
        fi.click();
      },
      video: function () { openVideo(); },
      preview: function () { openPreview(); },
      fullscreen: function () { setFull(!box.classList.contains("is-full")); }
    };
    quill = new Quill(host, {
      theme: "snow",
      formats: formats,
      placeholder: inline ? "1–2 vety, ktoré sa zobrazia v zozname aktualít…" : "Sem napíš text… Fotky môžeš rovno pretiahnuť myšou.",
      modules: {
        toolbar: { container: toolbar, handlers: handlers },
        history: { delay: 800, maxStack: 200, userOnly: true },
        uploader: inline ? { mimetypes: [] } : {
          mimetypes: ["image/png", "image/jpeg", "image/webp", "image/gif", "image/avif", "image/bmp", "image/heic", "image/heif", "image/tiff", ""],
          handler: function (range, files) { uploadImages(range, files); }
        }
      }
    });
    // vložené (Ctrl+V) fotky a videá: povolené sú len vlastné fotky z editora a YouTube — iné by server pri uložení zahodil
    const Delta = Quill.import("delta");
    quill.clipboard.addMatcher("IMG", function (node, delta) {
      const m = (node.getAttribute("src") || "").match(INLINE_IMG);
      if (m && !inline) return new Delta().insert({ image: "../uploads/" + m[1] });
      say("Fotky z iných stránok sa nedajú vložiť — ulož si fotku do počítača a vlož ju tlačidlom 🖼 alebo pretiahnutím.", true);
      return new Delta();
    });
    quill.clipboard.addMatcher("IFRAME", function (node) {
      const id = youtubeId(node.getAttribute("src") || "");
      if (id && !inline) return new Delta().insert({ video: "https://www.youtube-nocookie.com/embed/" + id });
      say("Vložiť sa dá len video z YouTube (tlačidlo 🎞).", true);
      return new Delta();
    });
    if (ta.value.trim() !== "") quill.clipboard.dangerouslyPasteHTML(toEditor(ta.value));
    quill.history.clear();

    box.querySelectorAll(".ql-toolbar button, .ql-toolbar .ql-picker").forEach(function (b) {
      const name = (b.className.match(/ql-(\w+)/) || [])[1];
      const key = b.value ? name + "-" + b.value : name;
      if (TITLES[key]) { b.title = TITLES[key]; b.setAttribute("aria-label", TITLES[key]); }
    });
    const toolbarEl = box.querySelector(".ql-toolbar");
    toolbarEl.setAttribute("role", "toolbar");

    /* výber emoji */
    const picker = document.createElement("div");
    picker.className = "a-emoji";
    picker.hidden = true;
    const tabs = document.createElement("div");
    tabs.className = "a-emoji-tabs";
    tabs.setAttribute("role", "tablist");
    const grid = document.createElement("div");
    grid.className = "a-emoji-grid";
    const caption = document.createElement("div");
    caption.className = "a-emoji-name";
    picker.appendChild(tabs);
    picker.appendChild(caption);
    picker.appendChild(grid);
    const keep = function (e) { e.preventDefault(); }; // nestratiť výber v editore
    function showCategory(i) {
      tabs.querySelectorAll("button").forEach(function (t, j) { t.setAttribute("aria-selected", i === j ? "true" : "false"); });
      caption.textContent = EMOJI[i].name;
      grid.textContent = "";
      EMOJI[i].list.forEach(function (em) {
        const b = document.createElement("button");
        b.type = "button";
        b.textContent = em;
        b.addEventListener("mousedown", keep);
        b.addEventListener("click", function () {
          const range = quill.getSelection(true);
          quill.insertText(range.index, em, "user");
          quill.setSelection(range.index + em.length, 0, "user"); // okno ostane otvorené — dá sa vložiť viac emoji
        });
        grid.appendChild(b);
      });
    }
    EMOJI.forEach(function (c, i) {
      const t = document.createElement("button");
      t.type = "button";
      t.textContent = c.icon;
      t.title = c.name;
      t.setAttribute("role", "tab");
      t.setAttribute("aria-label", c.name);
      t.addEventListener("mousedown", keep);
      t.addEventListener("click", function () { showCategory(i); });
      tabs.appendChild(t);
    });
    showCategory(0);
    toolbarEl.after(picker);
    const emojiBtn = box.querySelector(".ql-emoji");
    document.addEventListener("click", function (e) {
      if (!picker.hidden && !picker.contains(e.target) && !(emojiBtn && emojiBtn.contains(e.target))) picker.hidden = true;
    });

    /* fotky v texte: tlačidlo, Ctrl+V aj pretiahnutie (Quill uploader) */
    async function uploadImages(range, files) {
      const all = Array.from(files);
      files = all.filter(isImageFile);
      if (!files.length) { if (all.length) say(NOT_IMAGE, true); return; }
      if (!csrfIn) return;
      let index = range ? range.index : quill.getLength();
      for (let i = 0; i < files.length; i++) {
        say("Nahrávam fotku " + (i + 1) + " z " + files.length + "…");
        let data = null;
        try {
          const p = await photoPayload(files[i]);
          data = await postPhoto(API.image, { csrf: csrfIn.value, full: p.full, thumb: p.thumb });
        } catch (e) {
          say(e.message || "Chyba pripojenia.", true);
          continue;
        }
        quill.insertEmbed(index, "image", "../" + data.src, "user");
        index++;
        quill.setSelection(index, 0, "user");
      }
      say(files.length === 1 ? "Fotka vložená." : "Vložené fotky: " + files.length + ".");
    }

    /* video z YouTube — malé okno priamo pod lištou */
    const videoBox = document.createElement("div");
    videoBox.className = "a-rich-pop";
    videoBox.hidden = true;
    videoBox.innerHTML = '<label>Odkaz na video z YouTube<input type="text" inputmode="url" autocomplete="off" placeholder="https://www.youtube.com/watch?v=…"></label>' +
      '<div class="a-rich-pop-actions"><button type="button" class="a-btn a-btn-primary">Vložiť</button><button type="button" class="a-btn">Zrušiť</button></div>';
    if (!inline) toolbarEl.after(videoBox);
    const videoInput = videoBox.querySelector("input");
    const [videoOk, videoCancel] = videoBox.querySelectorAll("button");
    let videoRange = null;
    function openVideo() {
      videoRange = quill.getSelection(true);
      videoBox.hidden = false;
      videoInput.value = "";
      videoInput.focus();
    }
    function insertVideo() {
      const id = youtubeId(videoInput.value);
      if (!id) { say("Toto nie je odkaz na YouTube video.", true); videoInput.focus(); return; }
      const at = videoRange ? videoRange.index : quill.getLength();
      quill.insertEmbed(at, "video", "https://www.youtube-nocookie.com/embed/" + id, "user");
      quill.setSelection(at + 1, 0, "user");
      videoBox.hidden = true;
      videoInput.value = "";
      say("Video vložené.");
    }
    videoOk.addEventListener("click", insertVideo);
    function closeVideo() { videoBox.hidden = true; videoInput.value = ""; quill.focus(); }
    videoCancel.addEventListener("click", closeVideo);
    videoInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") { e.preventDefault(); insertVideo(); }
      if (e.key === "Escape") closeVideo();
    });

    /* náhľad — server vráti presne to, čo uloží; zobrazí sa so štýlmi webu */
    async function openPreview() {
      say("Pripravujem náhľad…");
      let data;
      try {
        data = await previewFetch({ csrf: csrfIn ? csrfIn.value : "", mode: inline ? "inline" : "full", html: ta.value });
      } catch (e) {
        say(e.message || "Náhľad zlyhal.", true);
        return;
      }
      say("");
      const wasFull = box.classList.contains("is-full");
      if (wasFull) setFull(false);
      previewModal('<section><div class="container article" style="padding-top:2rem">' +
        (inline ? '<p class="article-lead">' + data.html + "</p>" : '<div class="prose"><div class="rich">' + data.html + "</div></div>") +
        "</div></section>", function () { if (wasFull) setFull(true); else quill.focus(); });
    }

    /* celá obrazovka */
    function setFull(on) {
      box.classList.toggle("is-full", on);
      document.documentElement.classList.toggle("a-noscroll", on);
      const b = box.querySelector(".ql-fullscreen");
      if (b) b.classList.toggle("ql-active", on);
      quill.focus();
    }
    box.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && box.classList.contains("is-full") && videoBox.hidden) setFull(false);
    });

    /* synchronizácia do textarea + počítadlo */
    function sync() {
      const text = quill.getText().trim();
      const hasMedia = !!quill.root.querySelector("img, iframe, hr");
      ta.value = text === "" && !hasMedia ? "" : toStored(quill.root.innerHTML);
      const chars = Array.from(text).length;
      const words = text === "" ? 0 : text.split(/\s+/).length;
      statusCount.textContent = max ? chars + " / " + max + " znakov" : words + " slov · " + chars + " znakov";
      statusCount.classList.toggle("a-err", max > 0 && chars > max);
    }
    quill.on("text-change", function (delta, old, source) {
      sync();
      if (source === "user" && form) form.dispatchEvent(new Event("rich-change"));
    });
    sync();

    if (form && !form.querySelector("input[name=rich]")) {
      const flag = document.createElement("input");
      flag.type = "hidden";
      flag.name = "rich";
      flag.value = "1";
      form.appendChild(flag);
    }
    if (form) form.addEventListener("submit", function () { sync(); setFull(false); });
  });
})();
