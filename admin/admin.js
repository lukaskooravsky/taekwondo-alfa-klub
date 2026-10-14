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
})();
