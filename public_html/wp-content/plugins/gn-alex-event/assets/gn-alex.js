(function () {
  var params = new URLSearchParams(window.location.search);
  var warning = document.getElementById('gn-url-warning');
  if (params.has('ticket') || params.has('ticket_code') || params.has('code') || params.has('nonce')) {
    window.history.replaceState(null, '', window.location.pathname);
    if (warning) warning.hidden = false;
  }
  var login = document.getElementById('gn-login');
  var lookup = document.getElementById('gn-lookup');
  var resolved = document.getElementById('gn-resolved');
  var redeem = document.getElementById('gn-redeem');
  var overrideForm = document.getElementById('gn-override');
  var queue = document.getElementById('gn-queue');
  var rotate = document.getElementById('gn-rotate');
  var revoke = document.getElementById('gn-revoke');
  var out = document.getElementById('gn-result');
  var token = '';
  var csrf = '';
  var sessionId = 0;
  var role = '';
  var voucherId = 0;
  var proofName = '';
  var proofPhone = '';
  function idem() {
    var key = window.sessionStorage.getItem('gn_alex_idem');
    if (!key) {
      key = 'idem-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
      window.sessionStorage.setItem('gn_alex_idem', key);
    }
    return key;
  }
  function say(text) { if (out) out.textContent = text; }
  function headers() {
    return {
      'content-type': 'application/json',
      'accept': 'application/json',
      'authorization': 'Bearer ' + token,
      'x-gn-csrf': csrf
    };
  }
  function post(url, payload) {
    return fetch(url, { method: 'POST', headers: headers(), body: JSON.stringify(payload) }).then(function (res) { return res.json(); });
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
        sessionId = json.session_id;
        role = json.role || '';
        login.hidden = true;
        if (lookup) lookup.hidden = false;
        if (overrideForm) overrideForm.hidden = role !== 'quanly';
        if (rotate) rotate.hidden = false;
        if (revoke) revoke.hidden = false;
        if (queue) loadQueue();
        say('');
      }).catch(function () { say('offline'); });
    });
  }
  if (lookup) {
    lookup.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = new FormData(lookup);
      proofName = data.get('proof_name');
      proofPhone = data.get('proof_phone');
      post(lookup.action, { proof_name: proofName, proof_phone: proofPhone, campaign: data.get('campaign') }).then(function (json) {
        if (!json.ok) { say(json.error || 'denied'); if (resolved) resolved.hidden = true; return; }
        voucherId = json.voucher_id;
        document.getElementById('gn-resolved-name').textContent = json.display_name;
        document.getElementById('gn-resolved-mask').textContent = json.phone_mask;
        document.getElementById('gn-resolved-remaining').textContent = String(json.remaining);
        resolved.hidden = false;
        say('');
      });
    });
  }
  if (redeem) {
    redeem.addEventListener('submit', function (event) {
      event.preventDefault();
      var item = event.submitter ? event.submitter.value : 'beer';
      post(redeem.action, {
        proof_name: proofName,
        proof_phone: proofPhone,
        campaign: 'alex-dj-20261108',
        confirm: 1,
        item: item,
        idempotency_key: idem()
      }).then(function (json) {
        say(json.ok ? (item + ' ' + json.remaining_after) : (json.error || 'denied'));
        if (json.ok && !json.replay) window.sessionStorage.removeItem('gn_alex_idem');
      }).catch(function () { say('offline — retry keeps the same key'); });
    });
  }
  if (overrideForm) {
    overrideForm.addEventListener('submit', function (event) {
      event.preventDefault();
      var data = new FormData(overrideForm);
      post(overrideForm.action, { voucher_id: voucherId, delta: 1, reason: data.get('reason') }).then(function (json) {
        say(json.ok ? 'pending ' + json.override_id : (json.error || 'denied'));
      });
    });
  }
  if (rotate) {
    rotate.addEventListener('click', function () {
      post('/gn-alex/v1/staff/rotate', {}).then(function (json) {
        if (!json.ok) { say(json.error || 'denied'); return; }
        token = json.token;
        csrf = json.csrf;
        sessionId = json.session_id;
        say('rotated');
      });
    });
  }
  if (revoke) {
    revoke.addEventListener('click', function () {
      post('/gn-alex/v1/staff/revoke', { session_id: sessionId }).then(function () {
        token = '';
        window.location.reload();
      });
    });
  }
  function loadQueue() {
    post('/gn-alex/v1/staff/partner-queue', {}).then(function (json) {
      if (!json.ok) { say(json.error || 'denied'); return; }
      queue.hidden = false;
      queue.textContent = '';
      (json.applications || []).forEach(function (row) {
        var card = document.createElement('article');
        card.textContent = row.role + ' ' + row.status + ' ' + row.attribution + ' ' + row.phone_mask;
        if (role === 'quanly' && row.status === 'pending') {
          var review = document.createElement('button');
          review.type = 'button';
          review.textContent = 'Review';
          review.addEventListener('click', function () {
            post('/gn-alex/v1/admin/partner/approve', { partner_id: row.id }).then(function (res) { say(res.ok ? 'reviewed' : (res.error || 'denied')); loadQueue(); });
          });
          card.appendChild(review);
        }
        if (role === 'onboarding' && row.status === 'reviewed') {
          var grant = document.createElement('button');
          grant.type = 'button';
          grant.textContent = 'Grant';
          grant.addEventListener('click', function () {
            post('/gn-alex/v1/admin/partner/grant', { partner_id: row.id }).then(function (res) { say(res.ok ? 'granted' : (res.error || 'denied')); loadQueue(); });
          });
          card.appendChild(grant);
        }
        queue.appendChild(card);
      });
    });
  }
})();
