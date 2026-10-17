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

  document.querySelectorAll("select[data-gns-group]").forEach(function (sel) {
    var label = sel.closest("label");
    var hint = label ? label.querySelector("[data-gns-hint]") : null;
    if (!hint) return;
    var update = function () {
      var opt = sel.options[sel.selectedIndex];
      hint.textContent = (opt && opt.getAttribute("data-hint")) || "";
    };
    sel.addEventListener("change", update);
    var form = sel.form;
    if (form) form.addEventListener("reset", function () { setTimeout(update, 0); });
    update();
  });

  document.querySelectorAll("form[data-gn-request]").forEach(function (form) {
    var group = form.querySelector("select[data-gns-group]");
    var sync = function () {
      var value = group ? group.value : "";
      Array.prototype.forEach.call(form.querySelectorAll("[data-gn-extra]"), function (el) {
        el.hidden = el.getAttribute("data-gn-extra") !== value;
      });
    };
    if (group) group.addEventListener("change", sync);
    sync();
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var btn = form.querySelector('[type="submit"]');
      var err = note(form, "[data-gn-error]");
      var wrap = form.closest(".gn-request-wrap") || form.parentNode;
      var done = wrap.querySelector("[data-gn-done]");
      if (btn) btn.disabled = true;
      err.textContent = "";
      fetch(form.action, { method: "POST", body: new FormData(form), credentials: "same-origin" })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data && data.success) {
            var msg = done ? done.querySelector("[data-gn-done-message]") : null;
            var text = messageOf(data, "Đã nhận. Nhà hàng sẽ gọi lại xác nhận trong giờ mở cửa.");
            if (msg) msg.textContent = text;
            form.hidden = true;
            if (done) {
              done.hidden = false;
              if (done.focus) done.focus();
            }
            return;
          }
          if (btn) btn.disabled = false;
          err.textContent = messageOf(data, "Không gửi được. Gọi 0979 232 233.");
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          err.textContent = "Không gửi được. Gọi 0979 232 233.";
        });
    });
  });

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
