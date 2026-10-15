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

    input.addEventListener("change", function () {
      setFiles(fullIn, []);
      setFiles(thumbIn, []);
      input.name = origName; // pri novom výbere znova posielať originál, kým nie je zmenšený
      showPreview(null);
      const file = input.files[0];
      if (!file) { status.textContent = ""; return; }
      status.textContent = "Pripravujem fotku…";
      busy = makePair(file).then(function (pair) {
        setFiles(fullIn, [pair.full]);
        setFiles(thumbIn, [pair.thumb]);
        // výber ostane viditeľný v poli, len sa originál neodošle (bez name sa pole neposiela)
        input.removeAttribute("name");
        showPreview(pair.thumb);
        status.textContent = "Fotka pripravená (" + Math.round(pair.full.size / 1024) + " KB).";
      }).catch(function () {
        input.name = origName;
        status.textContent = "Fotku sa nepodarilo zmenšiť v prehliadači — odošle sa pôvodná (max 5 MB).";
      }).finally(function () { busy = null; });
    });

    form.addEventListener("submit", function (e) {
      if (!busy) return;
      e.preventDefault();
      status.textContent = "Chvíľu počkaj, fotka sa ešte pripravuje…";
      busy.then(function () { form.requestSubmit ? form.requestSubmit() : form.submit(); }); // Safari < 16 nemá requestSubmit
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
      const fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("album", box.dataset.album);
      let pair = null;
      if (canDecode) {
        try { pair = await makePair(file); } catch (e) { pair = null; }
      }
      if (pair) {
        fd.append("full", pair.full);
        fd.append("thumb", pair.thumb);
      } else {
        fd.append("full", file); // zmenší server
      }
      let res;
      try {
        res = await fetch(box.dataset.endpoint, { method: "POST", body: fd, credentials: "same-origin" });
      } catch (e) {
        throw new Error("Chyba pripojenia.");
      }
      let data = null;
      try { data = await res.json(); } catch (e) { data = null; }
      if (!res.ok || !data || !data.ok) {
        throw new Error((data && data.error) || "Nahrávanie zlyhalo (" + res.status + ").");
      }
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
})();
