// Gánh Ngon theme behaviour: mobile drawer, reveal on scroll, the menu explorer
document.documentElement.classList.add("js");
// (search + filters over Workspace › Nhà hàng › Thực đơn),
// the reservation form (Workspace › Biểu mẫu, page key "dat-ban") and a
// custom dropdown. Other forms are plain HTML forms that the platform's site
// runtime captures as CRM leads.
(function () {
  "use strict";

  var HOTLINE = "0979 232 233";

  function esc(v) {
    return String(v == null ? "" : v)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // Which site is this? The live domain identifies the restaurant; the editor
  // preview passes ?license=. A local file preview has no platform behind it.
  var params = new URLSearchParams(location.search);
  var previewLicense = params.get("license");
  var isLocal =
    location.protocol === "file:" || ["localhost", "127.0.0.1", ""].indexOf(location.hostname) >= 0;
  var onPreviewHost = location.pathname.indexOf("/store/preview/") >= 0;
  function siteQuery() {
    if (previewLicense) return "licenseId=" + encodeURIComponent(previewLicense);
    if (isLocal || onPreviewHost) return null;
    return "hostname=" + encodeURIComponent(location.hostname);
  }

  // ------------------------------------------------------------ drawer --
  var drawer = document.querySelector("[data-drawer]");
  var toggle = document.querySelector("[data-nav-toggle]");
  function setDrawer(open) {
    if (!drawer || !toggle) return;
    drawer.hidden = !open;
    toggle.setAttribute("aria-expanded", String(open));
    document.documentElement.style.overflow = open ? "hidden" : "";
    if (open) {
      var first = drawer.querySelector("a, button");
      if (first) first.focus();
    } else {
      toggle.focus();
    }
  }
  if (drawer && toggle) {
    toggle.addEventListener("click", function () {
      setDrawer(drawer.hidden);
    });
    drawer.addEventListener("click", function (e) {
      if (e.target === drawer || e.target.closest("[data-nav-close]")) setDrawer(false);
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !drawer.hidden) setDrawer(false);
    });
  }

  // ------------------------------------------------------------ reveal --
  var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  var io =
    !reduce && "IntersectionObserver" in window
      ? new IntersectionObserver(
          function (entries) {
            entries.forEach(function (entry) {
              if (entry.isIntersecting) {
                entry.target.classList.add("is-in");
                io.unobserve(entry.target);
              }
            });
          },
          { rootMargin: "0px 0px -8% 0px" },
        )
      : null;
  function reveal(nodes) {
    if (!io) return;
    Array.prototype.forEach.call(nodes, function (el) {
      el.classList.add("reveal");
      io.observe(el);
    });
  }
  reveal(
    document.querySelectorAll(
      ".section-head, .kitchen-card, .space, .who article, .pot article, .package, .ent, .menu-section, .split-copy, .gallery figure, .news-card, .bestseller-copy",
    ),
  );

  // ---------------------------------------------------- custom dropdown --
  function enhanceSelect(select) {
    if (select.getAttribute("data-enhanced")) return;
    select.setAttribute("data-enhanced", "1");
    var options = Array.prototype.filter.call(select.options, function (o) {
      return o.value !== "";
    });
    var placeholder = select.options[0] && select.options[0].value === "" ? select.options[0].text : "";
    var wrap = document.createElement("div");
    wrap.className = "fancy";
    var listId = "fancy-" + Math.random().toString(36).slice(2, 9);
    wrap.innerHTML =
      '<button type="button" class="fancy-btn" aria-haspopup="listbox" aria-expanded="false" aria-controls="' +
      listId +
      '"><span class="fancy-value"></span><svg class="fancy-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>' +
      '<ul class="fancy-list" role="listbox" id="' +
      listId +
      '" tabindex="-1" hidden>' +
      options
        .map(function (o, i) {
          return (
            '<li role="option" id="' +
            listId +
            "-" +
            i +
            '" data-value="' +
            esc(o.value) +
            '" aria-selected="false">' +
            esc(o.text) +
            "</li>"
          );
        })
        .join("") +
      "</ul>";
    select.parentNode.insertBefore(wrap, select.nextSibling);
    select.classList.add("fancy-native");
    select.setAttribute("tabindex", "-1");
    var btn = wrap.querySelector(".fancy-btn");
    var list = wrap.querySelector(".fancy-list");
    var items = Array.prototype.slice.call(list.querySelectorAll("li"));
    var active = -1;
    function paint() {
      var chosen = select.value;
      var label = placeholder;
      items.forEach(function (li) {
        var on = li.getAttribute("data-value") === chosen;
        li.setAttribute("aria-selected", String(on));
        if (on) label = li.textContent;
      });
      wrap.querySelector(".fancy-value").textContent = label;
      btn.classList.toggle("is-placeholder", !chosen);
    }
    function setActive(i) {
      active = Math.max(0, Math.min(items.length - 1, i));
      items.forEach(function (li, j) {
        li.classList.toggle("is-active", j === active);
      });
      list.setAttribute("aria-activedescendant", items[active] ? items[active].id : "");
      if (items[active]) items[active].scrollIntoView({ block: "nearest" });
    }
    function open() {
      list.hidden = false;
      btn.setAttribute("aria-expanded", "true");
      wrap.classList.add("is-open");
      var idx = items.findIndex(function (li) {
        return li.getAttribute("data-value") === select.value;
      });
      setActive(idx >= 0 ? idx : 0);
      list.focus();
    }
    function close(focusBtn) {
      list.hidden = true;
      btn.setAttribute("aria-expanded", "false");
      wrap.classList.remove("is-open");
      if (focusBtn) btn.focus();
    }
    function choose(i) {
      if (!items[i]) return;
      select.value = items[i].getAttribute("data-value");
      select.dispatchEvent(new Event("change", { bubbles: true }));
      paint();
      close(true);
    }
    btn.addEventListener("click", function () {
      if (list.hidden) open();
      else close(true);
    });
    btn.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown" || e.key === "ArrowUp") {
        e.preventDefault();
        open();
      }
    });
    list.addEventListener("keydown", function (e) {
      if (e.key === "ArrowDown") {
        e.preventDefault();
        setActive(active + 1);
      } else if (e.key === "ArrowUp") {
        e.preventDefault();
        setActive(active - 1);
      } else if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        choose(active);
      } else if (e.key === "Escape" || e.key === "Tab") {
        close(e.key === "Escape");
      }
    });
    items.forEach(function (li, i) {
      li.addEventListener("click", function () {
        choose(i);
      });
      li.addEventListener("mousemove", function () {
        if (active !== i) setActive(i);
      });
    });
    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target) && !list.hidden) close(false);
    });
    select.addEventListener("invalid", function () {
      wrap.classList.add("is-invalid");
    });
    select.addEventListener("change", function () {
      wrap.classList.remove("is-invalid");
      paint();
    });
    paint();
  }
  Array.prototype.forEach.call(document.querySelectorAll("select[data-fancy]"), enhanceSelect);

  // ----------------------------------------------------- menu explorer --
  // Search + group + food/drink filters over the menu cards. The page ships with the
  // menu as of the last build; on the live site it is replaced with Workspace › Nhà hàng
  // › Thực đơn (prices, "hết món hôm nay", photos) using the same card markup.
  function norm(s) {
    return String(s == null ? "" : s)
      .toLowerCase()
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "")
      .replace(/đ/g, "d");
  }
  function slugOf(s) {
    return norm(s).replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
  }
  var money = new Intl.NumberFormat("vi-VN");
  function initMenuExplorer(box) {
    var form = box.querySelector("[data-menu-search]");
    var catGrid = box.querySelector("[data-cat-grid]");
    var grid = box.querySelector("[data-dish-grid]");
    var results = box.querySelector(".menu-results");
    var title = box.querySelector("[data-results-title]");
    var count = box.querySelector("[data-results-count]");
    var clear = box.querySelector("[data-results-clear]");
    var empty = box.querySelector("[data-results-empty]");
    if (!form || !grid) return;

    function apply(scroll) {
      var q = norm(form.q.value.trim());
      var cat = form.cat.value;
      var kind = form.kind.value;
      var shown = 0;
      Array.prototype.forEach.call(grid.querySelectorAll(".dish-card"), function (card) {
        var ok =
          (!cat || card.getAttribute("data-cat") === cat) &&
          (!kind || card.getAttribute("data-kind") === kind) &&
          (!q || norm(card.textContent).indexOf(q) >= 0);
        card.hidden = !ok;
        if (ok) shown++;
      });
      Array.prototype.forEach.call(catGrid.querySelectorAll(".cat-card"), function (b) {
        b.setAttribute("aria-pressed", String(b.getAttribute("data-cat") === cat));
        b.hidden = Boolean(kind) && b.getAttribute("data-kind") !== kind;
      });
      title.textContent = q
        ? "Kết quả cho “" + form.q.value.trim() + "”"
        : cat
          ? form.cat.options[form.cat.selectedIndex].text
          : kind
            ? kind === "drink"
              ? "Đồ uống"
              : "Đồ ăn"
            : "Tất cả món";
      count.textContent = shown + " món";
      empty.hidden = shown > 0;
      clear.hidden = !(q || cat || kind);
      if (scroll) results.scrollIntoView({ behavior: "smooth", block: "start" });
    }

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      apply(true);
    });
    form.q.addEventListener("input", function () {
      apply(false);
    });
    form.cat.addEventListener("change", function () {
      apply(false);
    });
    form.kind.addEventListener("change", function () {
      apply(false);
    });
    catGrid.addEventListener("click", function (e) {
      var b = e.target.closest(".cat-card");
      if (!b) return;
      var id = b.getAttribute("data-cat");
      form.cat.value = form.cat.value === id ? "" : id;
      apply(true);
    });
    clear.addEventListener("click", function () {
      form.reset();
      apply(false);
    });
    // thuc-don.html?nhom=<group> (the home page's kitchen cards) opens on that group.
    var pre = new URLSearchParams(location.search).get("nhom");
    if (pre && form.cat.querySelector('option[value="' + pre.replace(/[^a-z0-9-]/g, "") + '"]')) {
      form.cat.value = pre;
      apply(false);
    }
    return;
  }

  var explorer = document.querySelector("[data-menu-explorer]");
  if (explorer) initMenuExplorer(explorer);

  // Local advisor — same intents as the live site. Answers are restaurant
  // facts only. It does not call an outside model and does not claim a table
  // is free. The platform's own chat widget, when the restaurant turns it on,
  // is a separate channel.
  var ADVISOR = {
    vi: {
      hello: "Gánh Ngon ở 113 Trần Hưng Đạo, Dương Đông, Phú Quốc. Món đặc trưng là lẩu gà sầu riêng. Bàn thường nhận trong ngày, mở cửa 10:00–23:00. Gọi hoặc Zalo 0979 232 233. Giá báo tại nhà hàng.",
      dish: "Món đặc trưng là lẩu gà sầu riêng: sầu riêng Phú Quốc hoà vào nước hầm gà, không phải món tráng miệng. Nên báo trước khoảng 1 tiếng. Bàn thường vẫn nhận trong ngày. Giá không niêm yết trên web.",
      price: "Nhà hàng không niêm yết giá trên web. Gọi 0979 232 233, cho biết ngày, giờ, số khách và có muốn lẩu gà sầu riêng hay không.",
      hours: "Mở cửa hằng ngày 10:00 đến 23:00, kể cả lễ. Bàn thường nhận trong ngày.",
      map: "113 Trần Hưng Đạo, Dương Đông, Phú Quốc. Mở Google Maps từ trang Liên hệ. Gọi 0979 232 233 nếu cần chỉ đường.",
      contact: "Điện thoại và Zalo: 0979 232 233. Email: contact@ganhngon.com. Facebook: facebook.com/ganhngon.",
      book: "Bàn thường đặt và đến trong ngày được. Giữ bàn tại trang Đặt bàn hoặc gọi 0979 232 233. Nhà hàng không xác nhận còn bàn trước khi nhân viên gọi lại.",
      vip: "Nhà hàng có 9 phòng VIP: 3 phòng tầng dưới, 6 phòng tầng trên. Nên báo trước khoảng 24 giờ. Cần ngồi hôm nay thì chọn bàn thường.",
      group: "Đoàn đông và tiệc riêng nên báo trước 48–72 giờ. Nhóm nhỏ vẫn nhận bàn thường trong ngày.",
      menu: "Trang thực đơn liệt kê các món đang phục vụ. Giá không hiện trên web.",
      fallback: "Em giúp được việc đến trong hôm nay: bàn thường, đường đến 113 Trần Hưng Đạo, và món lẩu gà sầu riêng. Gọi 0979 232 233. Giá báo tại nhà hàng.",
    },
    en: {
      hello: "Gánh Ngon is at 113 Tran Hung Dao, Duong Dong, Phu Quoc. The signature dish is durian chicken hotpot. A regular table can be booked today. Open daily 10:00–23:00. Call 0979 232 233. Prices are quoted at the restaurant.",
      dish: "The signature dish is durian chicken hotpot: Phu Quoc durian blended into chicken broth, not a dessert. Tell the kitchen about an hour ahead. A regular table can still be requested the same day. The price is not listed on the website.",
      price: "Prices are not listed on the website. Call 0979 232 233 with the date, time, party size, and whether you want durian chicken hotpot.",
      hours: "Open every day from 10:00 to 23:00, including holidays.",
      map: "113 Tran Hung Dao, Duong Dong, Phu Quoc — central Duong Dong. Call 0979 232 233 if you need directions.",
      contact: "Phone and Zalo: 0979 232 233. Email: contact@ganhngon.com. Facebook: facebook.com/ganhngon.",
      book: "A regular table can be reserved and used today. The restaurant does not confirm a free table until staff call you back.",
      vip: "The restaurant has 9 VIP rooms: 3 downstairs and 6 upstairs. Ask about a day ahead. If you need to sit today, book a regular table.",
      group: "Large groups and private events are best requested 48–72 hours ahead.",
      menu: "The menu page lists the dishes currently served. Prices are not shown.",
      fallback: "I can help with today's regular table, directions to 113 Tran Hung Dao, and durian chicken hotpot. Call 0979 232 233.",
    },
  };
  function fold(text) {
    return String(text || "")
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/đ/g, "d");
  }
  function advisorIntent(message) {
    var t = fold(message);
    var has = function (words) {
      return words.some(function (w) {
        return w && t.indexOf(w) >= 0;
      });
    };
    if (has(["gia", "price", "cost", "how much", "bao nhieu"])) return "price";
    if (has(["sau rieng", "durian", "hotpot", "lau ga"])) return "dish";
    if (has(["gio mo", "opening", "open", "hours", "may gio"])) return "hours";
    if (has(["map", "dia chi", "address", "where", "o dau", "chi duong"])) return "map";
    if (has(["zalo", "whatsapp", "phone", "hotline", "goi", "call", "wechat"])) return "contact";
    if (has(["vip", "phong rieng", "private room"])) return "vip";
    if (has(["doan", "group", "tiec", "20 khach"])) return "group";
    if (has(["dat ban", "book", "reserv", "hom nay", "today", "ban thuong"])) return "book";
    if (has(["menu", "thuc don", "mon an"])) return "menu";
    if (has(["hello", "hi", "chao", "xin chao"])) return "hello";
    return "fallback";
  }
  var advisorRoot = document.querySelector("[data-advisor]");
  if (advisorRoot) {
    var logEl = advisorRoot.querySelector("[data-advisor-log]");
    var form = advisorRoot.querySelector("[data-advisor-form]");
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var input = form.querySelector("input");
      var text = input.value.trim();
      if (!text) return;
      var lang = document.documentElement.lang === "en" ? "en" : "vi";
      var pack = ADVISOR[lang] || ADVISOR.vi;
      logEl.insertAdjacentHTML(
        "beforeend",
        '<p class="advisor-bubble is-user">' + esc(text) + "</p>" +
          '<p class="advisor-bubble">' + esc(pack[advisorIntent(text)] || pack.fallback) + "</p>",
      );
      input.value = "";
      logEl.scrollTop = logEl.scrollHeight;
    });
  }

  document.querySelectorAll("[data-wechat-copy]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var id = "ganhngon";
      var pop = document.querySelector("[data-wechat-pop]");
      var done = function () {
        if (pop) pop.hidden = false;
      };
      if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(id).then(done, done);
      else done();
    });
  });
  var wechatPop = document.querySelector("[data-wechat-pop]");
  if (wechatPop) {
    wechatPop.querySelector("[data-wechat-close]").addEventListener("click", function () {
      wechatPop.hidden = true;
    });
  }

  var verify = document.querySelector("[data-verify-form]");
  if (verify) {
    var preset = new URLSearchParams(location.search).get("ma") || "";
    var codeInput = verify.querySelector("[data-verify-code]");
    var message = verify.querySelector("[data-verify-message]");
    if (preset && codeInput) codeInput.value = preset;
    var sync = function () {
      if (message && codeInput) message.value = "Xin xác minh mã đối tác: " + codeInput.value;
    };
    if (codeInput) codeInput.addEventListener("input", sync);
    sync();
  }

})();
