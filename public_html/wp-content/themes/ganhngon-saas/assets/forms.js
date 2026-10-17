(function () {
  var cfg = window.GNS || {};

  function note(form, selector) {
    var el = form.querySelector(selector || "[data-form-status]");
    if (!el) {
      el = document.createElement("p");
      el.className = "form-note";
      el.setAttribute("data-form-status", "");
      el.setAttribute("role", "status");
      form.appendChild(el);
    }
    return el;
  }

  function messageOf(data, fallback) {
    if (data && data.data && data.data.message) return data.data.message;
    if (data && data.message) return data.message;
    return fallback;
  }

  document.querySelectorAll("form[data-gns-mail]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var btn = form.querySelector('[type="submit"]');
      var out = note(form);
      if (btn) btn.disabled = true;
      var body = new FormData(form);
      body.set("action", "gns_send_note");
      body.set("nonce", cfg.nonce || "");
      body.set("box", form.getAttribute("data-gns-mail") || "");
      fetch(cfg.ajax, { method: "POST", body: body, credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (btn) btn.disabled = false;
          out.textContent = messageOf(data, "0979 232 233");
          if (data && data.success) form.reset();
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          out.textContent = "Không gửi được. Gọi 0979 232 233.";
        });
    });
  });

  document.querySelectorAll("form[data-gns-partner]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var btn = form.querySelector('[type="submit"]');
      var out = note(form);
      if (btn) btn.disabled = true;
      fetch(form.action, { method: "POST", body: new FormData(form), credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (btn) btn.disabled = false;
          out.textContent = messageOf(data, "0979 232 233");
          if (data && data.success) form.reset();
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          out.textContent = "Không gửi được. Gọi 0979 232 233.";
        });
    });
  });

  document.querySelectorAll("form[data-gns-voucher]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var btn = form.querySelector('[type="submit"]');
      var out = note(form, "[data-voucher-out]");
      var face = form.querySelector('input[name="face_value"]:checked');
      if (!face) return;
      if (btn) btn.disabled = true;
      var body = new FormData();
      body.set("action", "gn_voucher_buy");
      body.set("face_value", face.value);
      body.set("buyer_name", form.buyer_name.value);
      body.set("buyer_email", form.buyer_email.value);
      body.set("buyer_phone", form.buyer_phone.value);
      fetch(cfg.ajax, { method: "POST", body: body, credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success && data.data) {
            out.textContent = "Mã " + (data.data.ma || "") + ". " + (data.data.message || "");
            if (btn) btn.hidden = true;
            return;
          }
          if (btn) btn.disabled = false;
          out.textContent = messageOf(data, "Không gửi được. Gọi 0979 232 233.");
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          out.textContent = "Không gửi được. Gọi 0979 232 233.";
        });
    });
  });
})();
