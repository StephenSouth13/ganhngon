(function(){
  // Smooth scroll for in-page anchors
  document.querySelectorAll('a[href^="#"]').forEach(function(a){
    a.addEventListener('click', function(e){
      var id = a.getAttribute('href');
      if (id.length < 2) return;
      var t = document.querySelector(id);
      if (t) { e.preventDefault(); t.scrollIntoView({behavior:'smooth'}); }
    });
  });
})();

// Language switcher toggle
function gnToggleLang(btn){
  var sw = btn.closest('.gn-lang-switch');
  if (!sw) return;
  var open = sw.classList.toggle('open');
  btn.setAttribute('aria-expanded', open ? 'true' : 'false');
}
// Close on outside click
document.addEventListener('click', function(e){
  if (!e.target.closest('.gn-lang-switch')){
    document.querySelectorAll('.gn-lang-switch.open').forEach(function(s){
      s.classList.remove('open');
      var b = s.querySelector('.gn-lang-current'); if(b) b.setAttribute('aria-expanded','false');
    });
  }
});
// Close on Escape
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape'){
    document.querySelectorAll('.gn-lang-switch.open').forEach(function(s){ s.classList.remove('open'); });
  }
});

// WeChat modal
function gnWechatPopup(e){ if(e) e.preventDefault(); var m=document.getElementById('gn-wechat-modal'); if(m) m.hidden=false; }

// Mobile menu
(function(){
  var nb = document.querySelector('.gn-nav-toggle');
  if (!nb) return;
  nb.addEventListener('click', function(){
    var h = document.querySelector('.gn-header');
    if (!h) return;
    var open = h.classList.toggle('is-open');
    nb.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
})();

// Same-day booking forms post to the commerce endpoint instead of a brochure page.
document.querySelectorAll('form[data-gn-book]').forEach(function(f){
  f.addEventListener('submit', function(e){
    e.preventDefault();
    var out = f.querySelector('.gn-book-out');
    var btn = f.querySelector('[type=submit]');
    if (btn) btn.disabled = true;
    fetch(f.action, {method:'POST', body:new FormData(f), credentials:'same-origin'})
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (btn) btn.disabled = false;
        if (!out) return;
        out.hidden = false;
        if (j && j.success && j.data && j.data.message) {
          out.textContent = j.data.message;
        } else {
          out.textContent = (j && j.data && j.data.message) ? j.data.message : '0979 232 233';
        }
      })
      .catch(function(){
        if (btn) btn.disabled = false;
        if (out) { out.hidden = false; out.textContent = '0979 232 233'; }
      });
  });
});
