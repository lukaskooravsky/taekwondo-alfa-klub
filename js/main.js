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

  /* ---- Demo formuláre (zatiaľ len ukážka) ---- */
  document.querySelectorAll("form[data-demo]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const btn = form.querySelector("button[type=submit]");
      if (btn) { btn.disabled = true; btn.textContent = "Odosielam…"; }
      setTimeout(function () {
        // pekné potvrdenie namiesto systémového alertu
        const title = form.getAttribute("data-demo") === "prihlaska"
          ? "Prihláška odoslaná!"
          : "Správa odoslaná!";
        const ok = document.createElement("div");
        ok.className = "form-success";
        ok.setAttribute("role", "status");
        ok.innerHTML =
          '<span class="fs-check" aria-hidden="true">✓</span>' +
          '<h3>' + title + '</h3>' +
          '<p>Ďakujeme! Čoskoro sa ti ozveme.</p>' +
          '<p class="fs-demo">Toto je náhľad (demo) — formulár sa naostro napojí pri spustení webu.</p>';
        form.replaceWith(ok);
      }, 700);
    });
  });

  /* ---- Rok v pätičke ---- */
  document.querySelectorAll("[data-year]").forEach(function (el) {
    el.textContent = new Date().getFullYear();
  });
})();
