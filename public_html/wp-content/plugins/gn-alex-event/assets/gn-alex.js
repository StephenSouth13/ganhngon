(function () {
  var params = new URLSearchParams(window.location.search);
  if (params.has('ticket') || params.has('ticket_code') || params.has('code') || params.has('nonce')) {
    window.history.replaceState(null, '', window.location.pathname);
  }
  var login = document.getElementById('gn-login');
  var redeem = document.getElementById('gn-redeem');
  var out = document.getElementById('gn-result');
  var token = '';
  var csrf = '';
  function idem() {
    var key = window.sessionStorage.getItem('gn_alex_idem');
    if (!key) {
      key = 'idem-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
      window.sessionStorage.setItem('gn_alex_idem', key);
    }
    return key;
  }
  function say(text) {
    if (out) out.textContent = text;
  }
  if (login) {
    login.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = new FormData(login);
      fetch(login.action, {
        method: 'POST',
        headers: { 'content-type': 'application/json', 'accept': 'application/json' },
        body: JSON.stringify({ staff_id: data.get('staff_id'), pin: data.get('pin') })
      }).then(function (res) { return res.json(); }).then(function (json) {
        if (!json.ok) { say(json.error || 'denied'); return; }
        token = json.token;
        csrf = json.csrf;
        login.hidden = true;
        redeem.hidden = false;
        say('');
      }).catch(function () { say('offline'); });
    });
  }
  if (redeem) {
    redeem.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = new FormData(redeem);
      var item = event.submitter ? event.submitter.value : 'beer';
      var payload = {
        proof_name: data.get('proof_name'),
        proof_phone: data.get('proof_phone'),
        campaign: data.get('campaign'),
        confirm: 1,
        item: item,
        idempotency_key: idem()
      };
      fetch(redeem.action, {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'accept': 'application/json',
          'authorization': 'Bearer ' + token,
          'x-gn-csrf': csrf
        },
        body: JSON.stringify(payload)
      }).then(function (res) { return res.json(); }).then(function (json) {
        say(json.ok ? (item + ' ' + json.remaining_after) : (json.error || 'denied'));
        if (json.ok && !json.replay) window.sessionStorage.removeItem('gn_alex_idem');
      }).catch(function () {
        say('offline — retry keeps the same key');
      });
    });
  }
})();
