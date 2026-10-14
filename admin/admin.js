/* Admin ALFA — potvrdzovanie mazania, zmenšovanie a nahrávanie fotiek. */
(function () {
  "use strict";

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
})();
