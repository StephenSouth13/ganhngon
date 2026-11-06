(function () {
  window.gnToggleLang = function (btn) {
    var wrap = btn.closest(".gn-lang-switch");
    if (!wrap) return;
    var open = wrap.classList.toggle("is-open");
    btn.setAttribute("aria-expanded", open ? "true" : "false");
  };
  document.addEventListener("click", function (e) {
    Array.prototype.forEach.call(document.querySelectorAll(".gn-lang-switch.is-open"), function (wrap) {
      if (!wrap.contains(e.target)) {
        wrap.classList.remove("is-open");
        var btn = wrap.querySelector(".gn-lang-current");
        if (btn) btn.setAttribute("aria-expanded", "false");
      }
    });
  });

  document.querySelectorAll("form[data-gn-book]").forEach(function (f) {
    f.addEventListener("submit", function (e) {
      e.preventDefault();
      var out = f.querySelector(".gn-book-out");
      var btn = f.querySelector('[type="submit"]');
      if (btn) btn.disabled = true;
      fetch(f.action, { method: "POST", body: new FormData(f), credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          if (btn) btn.disabled = false;
          if (!out) return;
          out.hidden = false;
          var msg = j && j.data && j.data.message ? j.data.message : "0979 232 233";
          out.textContent = msg;
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          if (out) {
            out.hidden = false;
            out.textContent = "0979 232 233";
          }
        });
    });
  });
})();
