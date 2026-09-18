/* ALFA Taekwondo — náčrt (demo) — interakcie */
(function () {
  "use strict";

  /* ---- Mobilné menu ---- */
  const toggle = document.querySelector(".nav-toggle");
  const links = document.querySelector(".nav-links");
  if (toggle && links) {
    toggle.addEventListener("click", function () {
      const open = links.classList.toggle("show");
      toggle.classList.toggle("open", open);
      toggle.setAttribute("aria-expanded", open ? "true" : "false");
    });
    links.querySelectorAll("a").forEach(function (a) {
      a.addEventListener("click", function () {
        links.classList.remove("show");
        toggle.classList.remove("open");
      });
    });
  }

  /* ---- Aktívny odkaz v navigácii ---- */
  const here = location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".nav-links a").forEach(function (a) {
    const href = a.getAttribute("href");
    if (href === here || (here === "" && href === "index.html")) {
      a.classList.add("active");
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
    box.innerHTML = '<button class="close" aria-label="Zavrieť">&times;</button><img alt="">';
    document.body.appendChild(box);
    const boxImg = box.querySelector("img");
    const close = box.querySelector(".close");

    gallery.querySelectorAll("img").forEach(function (img) {
      img.addEventListener("click", function () {
        boxImg.src = img.getAttribute("data-full") || img.src;
        boxImg.alt = img.alt;
        box.classList.add("open");
      });
    });
    function hide() { box.classList.remove("open"); }
    close.addEventListener("click", hide);
    box.addEventListener("click", function (e) { if (e.target === box) hide(); });
    document.addEventListener("keyup", function (e) { if (e.key === "Escape") hide(); });
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
