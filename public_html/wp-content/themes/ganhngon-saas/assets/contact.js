(function () {
  var root = document.querySelector("[data-gns-dock]");
  if (!root) return;
  var toggle = root.querySelector(".gns-dock-toggle");
  var sheet = document.getElementById("gns-dock-sheet");
  var menu = root.querySelector("[data-gns-menu]");
  var chat = root.querySelector("[data-gns-chat]");
  var form = root.querySelector("[data-gns-form]");
  var log = root.querySelector("[data-gns-log]");
  var wechat = document.getElementById("gns-wechat");
  var hist = [];

  function setOpen(open) {
    sheet.hidden = !open;
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    if (!open) {
      menu.hidden = false;
      chat.hidden = true;
    }
  }

  toggle.addEventListener("click", function () {
    setOpen(sheet.hidden);
  });

  root.querySelector("[data-gns-open-chat]").addEventListener("click", function () {
    menu.hidden = true;
    chat.hidden = false;
    var input = form.querySelector("input");
    if (input) input.focus();
  });

  root.querySelector("[data-gns-back]").addEventListener("click", function () {
    chat.hidden = true;
    menu.hidden = false;
  });

  root.querySelector("[data-gns-wechat]").addEventListener("click", function () {
    setOpen(false);
    wechat.hidden = false;
  });

  document.querySelectorAll("[data-gns-wechat-close]").forEach(function (btn) {
    btn.addEventListener("click", function () { wechat.hidden = true; });
  });
  if (wechat) {
    wechat.addEventListener("click", function (e) {
      if (e.target === wechat) wechat.hidden = true;
    });
  }

  var copy = document.querySelector("[data-gns-copy]");
  if (copy) {
    copy.addEventListener("click", function () {
      var id = copy.getAttribute("data-gns-copy") || "";
      var done = function () { copy.textContent = "Đã chép"; };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(id).then(done, done);
      } else {
        done();
      }
    });
  }

  function addMsg(text, who) {
    var p = document.createElement("p");
    p.className = "gns-dock-msg" + (who === "user" ? " is-user" : "");
    p.textContent = text;
    log.appendChild(p);
    log.scrollTop = log.scrollHeight;
    return p;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var input = form.querySelector("input");
    var msg = (input.value || "").trim();
    if (!msg) return;
    addMsg(msg, "user");
    hist.push({ role: "user", content: msg });
    input.value = "";
    var pending = addMsg("Đang soạn…", "bot");
    var body = new FormData();
    body.append("action", "gn_ai_chat");
    body.append("nonce", form.getAttribute("data-nonce") || "");
    body.append("lang", form.getAttribute("data-lang") || "vi");
    body.append("message", msg);
    body.append("history", JSON.stringify(hist.slice(-8)));
    fetch(form.action, { method: "POST", body: body, credentials: "same-origin" })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var reply = j && j.data && j.data.reply ? j.data.reply : "Anh/chị gọi hoặc Zalo 0979 232 233 giúp em nhé.";
        pending.textContent = reply;
        hist.push({ role: "assistant", content: reply });
      })
      .catch(function () {
        pending.textContent = "Kết nối gián đoạn. Anh/chị vui lòng gọi hoặc Zalo 0979 232 233.";
      });
  });

  document.addEventListener("click", function (e) {
    if (!root.contains(e.target) && sheet && !sheet.hidden) setOpen(false);
  });
  document.addEventListener("keydown", function (e) {
    if (e.key !== "Escape") return;
    if (wechat && !wechat.hidden) wechat.hidden = true;
    else if (sheet && !sheet.hidden) setOpen(false);
  });
})();
