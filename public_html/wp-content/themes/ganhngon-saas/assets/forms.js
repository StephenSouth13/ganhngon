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

  function resetTurnstile(form) {
    if (!window.turnstile || !form) return;
    var widget = form.querySelector(".cf-turnstile");
    if (!widget) return;
    try { window.turnstile.reset(widget); } catch (e) {}
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

  function pad2(n) {
    n = String(n);
    return n.length < 2 ? "0" + n : n;
  }

  function vnParts(date) {
    var fmt = new Intl.DateTimeFormat("en-GB", {
      timeZone: "Asia/Ho_Chi_Minh",
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hourCycle: "h23"
    });
    var parts = { year: "1970", month: "01", day: "01", hour: "00", minute: "00", second: "00" };
    fmt.formatToParts(date).forEach(function (p) {
      if (parts.hasOwnProperty(p.type)) parts[p.type] = p.value;
    });
    if (parts.hour === "24") parts.hour = "00";
    return parts;
  }

  function ymdAdd(ymd, days) {
    var p = ymd.split("-");
    var utc = Date.UTC(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10) + days);
    var d = new Date(utc);
    return d.getUTCFullYear() + "-" + pad2(d.getUTCMonth() + 1) + "-" + pad2(d.getUTCDate());
  }

  function bookingEarliest(now) {
    var plus = new Date(now.getTime() + 30 * 60000);
    var p = vnParts(plus);
    var seconds = parseInt(p.minute, 10) * 60 + parseInt(p.second, 10);
    var mod = seconds % 900;
    if (mod !== 0) plus = new Date(plus.getTime() + (900 - mod) * 1000);
    p = vnParts(plus);
    var hm = p.hour + ":" + p.minute;
    var ymd = p.year + "-" + p.month + "-" + p.day;
    if (hm < "10:00") return { ymd: ymd, hm: "10:00" };
    if (hm > "22:00") return { ymd: ymdAdd(ymd, 1), hm: "10:00" };
    return { ymd: ymd, hm: hm };
  }

  function serviceDateOf(moment) {
    return moment.ymd;
  }

  function slotMomentKey(serviceYmd, hm) {
    return serviceYmd + " " + hm;
  }

  function syncBookingSlots(form) {
    var dateInput = form.querySelector("[data-gn-date]");
    var timeSelect = form.querySelector("[data-gn-time]");
    if (!dateInput || !timeSelect || typeof Intl === "undefined") return;
    var earliest = bookingEarliest(new Date());
    var minDate = serviceDateOf(earliest);
    var earliestKey = earliest.ymd + " " + earliest.hm;
    dateInput.min = minDate;
    if (!dateInput.value || dateInput.value < minDate) dateInput.value = minDate;
    var firstEnabled = null;
    var selectedOk = false;
    Array.prototype.forEach.call(timeSelect.options, function (opt) {
      if (!opt.value) return;
      var past = slotMomentKey(dateInput.value, opt.value) < earliestKey;
      opt.disabled = past;
      if (!past && !firstEnabled) firstEnabled = opt;
      if (!past && opt.value === timeSelect.value) selectedOk = true;
    });
    if (!selectedOk && firstEnabled) timeSelect.value = firstEnabled.value;
  }

  document.querySelectorAll("form[data-gn-request]").forEach(function (form) {
    var dateInput = form.querySelector("[data-gn-date]");
    if (dateInput) {
      dateInput.addEventListener("change", function () { syncBookingSlots(form); });
      dateInput.addEventListener("input", function () { syncBookingSlots(form); });
      syncBookingSlots(form);
    }
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
          resetTurnstile(form);
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          err.textContent = "Không gửi được. Gọi 0979 232 233.";
          resetTurnstile(form);
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
