(function () {
  // Filters submit themselves when changed.
  document.querySelectorAll('form[data-autosubmit]').forEach(function (f) {
    var t;
    f.addEventListener('change', function () { f.submit(); });
    f.querySelectorAll('input[type=search]').forEach(function (i) {
      i.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { f.submit(); }, 600); });
      // keep the cursor at the end after reload
      if (i === document.activeElement || i.autofocus) { var v = i.value; i.value = ''; i.value = v; }
    });
  });
  // Ask before deleting.
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); });
  });
  // Type-to-find for long client drop-downs.
  document.querySelectorAll('input.pick-filter').forEach(function (inp) {
    var sel = document.getElementById(inp.dataset.for);
    if (!sel) return;
    var all = Array.prototype.slice.call(sel.options).map(function (o) { return { v: o.value, t: o.text, s: o.selected }; });
    inp.addEventListener('input', function () {
      var q = inp.value.toLowerCase().trim(), cur = sel.value, first = null;
      sel.innerHTML = '';
      all.forEach(function (o) {
        if (o.v === '' || o.v === '0' || !q || o.t.toLowerCase().indexOf(q) > -1 || o.v === cur) {
          var op = new Option(o.t, o.v, false, o.v === cur);
          sel.add(op);
          if (!first && q && o.v !== '' && o.v !== '0' && o.t.toLowerCase().indexOf(q) > -1) first = op;
        }
      });
      if (first && (cur === '' || cur === '0')) first.selected = true;
    });
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); sel.focus(); } });
  });
  // Filter a list of checkboxes.
  document.querySelectorAll('input.list-filter').forEach(function (inp) {
    var box = document.getElementById(inp.dataset.for);
    inp.addEventListener('input', function () {
      var q = inp.value.toLowerCase();
      box.querySelectorAll('label').forEach(function (l) { l.style.display = l.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none'; });
    });
  });
  // Select all (visible) checkboxes.
  document.querySelectorAll('input[data-checkall]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      document.querySelectorAll('input[name="' + cb.dataset.checkall + '"]').forEach(function (c) {
        if (c.closest('label').style.display !== 'none') c.checked = cb.checked;
      });
    });
  });
  // Status drop-down colour follows its value.
  document.querySelectorAll('select.pillsel').forEach(function (s) {
    s.addEventListener('change', function () { s.className = 'pillsel st-' + s.value.toLowerCase().replace(/[^a-z]+/g, '-').replace(/^-|-$/g, ''); });
  });
})();
