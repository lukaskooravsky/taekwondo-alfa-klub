/* ALFA Taekwondo — interakcie (menu, animácie, galéria, formuláre) */
(function () {
  "use strict";

  /* ---- Mobilné menu ---- */
  const toggle = document.querySelector(".nav-toggle");
  const links = document.querySelector(".nav-links");
  if (toggle && links) {
    function setMenu(open) {
      links.classList.toggle("show", open);
      toggle.classList.toggle("open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    }
    toggle.setAttribute("aria-controls", "nav-links");
    links.id = "nav-links";
    toggle.addEventListener("click", function () { setMenu(!links.classList.contains("show")); });
    links.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () { setMenu(false); });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && links.classList.contains("show")) { setMenu(false); toggle.focus(); }
    });
  }

  /* ---- Aktívny odkaz v navigácii ---- */
  const here = location.pathname.split("/").pop() || "index.php";
  const navKey = here === "aktualita.php" ? "aktuality.php" : here;
  document.querySelectorAll(".nav-links a").forEach(function (a) {
    if (a.getAttribute("href") === navKey) {
      a.classList.add("active");
      a.setAttribute("aria-current", "page");
    }
  });

  /* ---- Scroll reveal ---- */
  const revs = document.querySelectorAll(".reveal");
  if ("IntersectionObserver" in window && revs.length) {
    const io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    revs.forEach(function (el) { io.observe(el); });
  } else {
    revs.forEach(function (el) { el.classList.add("in"); });
  }

  /* ---- Lightbox galérie ---- */
  const gallery = document.querySelector(".gallery");
  if (gallery) {
    const box = document.createElement("div");
    box.className = "lightbox";
    box.setAttribute("role", "dialog");
    box.setAttribute("aria-modal", "true");
    box.setAttribute("aria-label", "Fotka");
    box.innerHTML = '<button class="close" aria-label="Zavrieť">&times;</button>' +
      '<button class="lb-nav lb-prev" aria-label="Predchádzajúca fotka">&#8249;</button><img alt="">' +
      '<button class="lb-nav lb-next" aria-label="Ďalšia fotka">&#8250;</button>';
    document.body.appendChild(box);
    const boxImg = box.querySelector("img");
    const close = box.querySelector(".close");
    // odkazy na plnú veľkosť fotky — fungujú aj bez JS a klávesnicou (Enter)
    const items = Array.from(gallery.querySelectorAll("a[data-lightbox]"));
    let current = -1, opener = null;

    function show(i) {
      current = (i + items.length) % items.length;
      const img = items[current].querySelector("img");
      boxImg.src = items[current].getAttribute("href");
      boxImg.alt = img ? img.alt : "";
    }
    function open(i) {
      opener = document.activeElement;
      show(i);
      box.classList.add("open");
      close.focus();
    }
    function hide() {
      if (!box.classList.contains("open")) return;
      box.classList.remove("open");
      if (opener) opener.focus();
    }
    items.forEach(function (a, i) {
      a.addEventListener("click", function (e) { e.preventDefault(); open(i); });
    });
    box.querySelector(".lb-prev").addEventListener("click", function () { show(current - 1); });
    box.querySelector(".lb-next").addEventListener("click", function () { show(current + 1); });
    if (items.length < 2) box.querySelectorAll(".lb-nav").forEach(function (b) { b.hidden = true; });
    close.addEventListener("click", hide);
    box.addEventListener("click", function (e) { if (e.target === box) hide(); });
    document.addEventListener("keydown", function (e) {
      if (!box.classList.contains("open")) return;
      if (e.key === "Escape") hide();
      else if (e.key === "ArrowLeft") show(current - 1);
      else if (e.key === "ArrowRight") show(current + 1);
      else if (e.key === "Tab") {                       // fokus ostane v okne
        const f = Array.from(box.querySelectorAll("button:not([hidden])"));
        const idx = f.indexOf(document.activeElement);
        e.preventDefault();
        f[(idx + (e.shiftKey ? -1 : 1) + f.length) % f.length].focus();
      }
    });
  }

  /* ---- Formuláre → e-mail (mailto:) ---- */
  const CONTACT_EMAIL = "lub.zavodny1@gmail.com";

  // ľudské názvy polí do tela e-mailu
  const FIELD_LABELS = {
    meno: "Meno a priezvisko",
    email: "E-mail",
    telefon: "Telefón",
    prekoho: "Prihlasujem",
    den: "Preferovaný deň",
    sprava: "Správa / poznámka"
  };

  document.querySelectorAll("form[data-demo]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const isPrihlaska = form.getAttribute("data-demo") === "prihlaska";

      // poskladaj predmet + telo z vyplnených polí
      const subject = isPrihlaska
        ? "Prihláška na skúšobnú hodinu — ALFA Taekwondo"
        : "Správa z webu — ALFA Taekwondo";
      const lines = [];
      form.querySelectorAll("input, textarea, select").forEach(function (el) {
        if (el.type === "checkbox" || el.type === "submit" || !el.name) return;
        const val = (el.value || "").trim();
        if (!val) return;
        const label = FIELD_LABELS[el.name] || el.name;
        lines.push(label + ": " + val);
      });
      const body = lines.join("\n");

      const mailto = "mailto:" + CONTACT_EMAIL +
        "?subject=" + encodeURIComponent(subject) +
        "&body=" + encodeURIComponent(body);
      window.location.href = mailto;

      // potvrdenie na stránke (návštevník dokončí odoslanie vo svojom maile)
      const title = isPrihlaska ? "Prihláška pripravená!" : "Správa pripravená!";
      const ok = document.createElement("div");
      ok.className = "form-success";
      ok.setAttribute("role", "status");
      ok.innerHTML =
        '<span class="fs-check" aria-hidden="true">✓</span>' +
        '<h3>' + title + '</h3>' +
        '<p>Otvorili sme ti e-mailový program s predvyplnenou správou — už len klikni <b>Odoslať</b>.</p>' +
        '<p class="fs-demo">Ak sa e-mail neotvoril, napíš nám priamo na <a href="mailto:' + CONTACT_EMAIL + '">' + CONTACT_EMAIL + '</a>.</p>';
      form.replaceWith(ok);
    });
  });

  /* ---- Rok v pätičke ---- */
  document.querySelectorAll("[data-year]").forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
