/* GG Associates – calculators. Each widget is a <div data-calc="name">; see gg-site plugin [gg_calculator]. */
(function () {
  var inr = function (n) {
    if (!isFinite(n)) return '—';
    return '₹ ' + Math.round(n).toLocaleString('en-IN');
  };
  var inr2 = function (n) {
    if (!isFinite(n)) return '—';
    return '₹ ' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };
  var num = function (el) { var v = parseFloat(String(el.value).replace(/,/g, '')); return isNaN(v) ? 0 : v; };
  var fmtDate = function (d) {
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  };
  var addDays = function (d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; };
  var parseDate = function (s) { if (!s) return null; var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); };
  var row = function (k, v, strong) {
    return '<div class="calc-row' + (strong ? ' calc-total' : '') + '"><span>' + k + '</span><b>' + v + '</b></div>';
  };

  var CALCS = {
    gst: {
      title: 'GST Calculator',
      sub: 'Add GST to a price, or extract GST from an inclusive amount.',
      form:
        '<label>Amount (₹)<input type="number" min="0" step="0.01" name="amt" value="10000"></label>' +
        '<label>GST rate<select name="rate">' +
        ['0.25', '1.5', '3', '5', '12', '18', '28', '40'].map(function (r) {
          return '<option value="' + r + '"' + (r === '18' ? ' selected' : '') + '>' + r + '%</option>';
        }).join('') + '</select></label>' +
        '<label>Calculation<select name="mode"><option value="add">Add GST (amount is taxable value)</option><option value="remove">Remove GST (amount is GST-inclusive)</option></select></label>' +
        '<label>Supply<select name="sup"><option value="intra">Within state (CGST + SGST)</option><option value="inter">Inter-state (IGST)</option></select></label>',
      run: function (f) {
        var a = num(f.amt), r = parseFloat(f.rate.value), base, tax;
        if (f.mode.value === 'add') { base = a; tax = a * r / 100; }
        else { base = a * 100 / (100 + r); tax = a - base; }
        var out = row('Taxable value', inr2(base));
        if (f.sup.value === 'intra') {
          out += row('CGST @ ' + r / 2 + '%', inr2(tax / 2)) + row('SGST @ ' + r / 2 + '%', inr2(tax / 2));
        } else out += row('IGST @ ' + r + '%', inr2(tax));
        return out + row('Invoice value', inr2(base + tax), true);
      }
    },

    gstinterest: {
      title: 'GST Interest on Late Payment',
      sub: 'Simple interest under section 50 of the CGST Act, on the tax paid late in cash.',
      form:
        '<label>Tax paid late (₹)<input type="number" min="0" name="tax" value="50000"></label>' +
        '<label>Interest rate (% p.a.)<input type="number" min="0" step="0.01" name="rate" value="18"></label>' +
        '<label>Due date<input type="date" name="due"></label>' +
        '<label>Date of payment<input type="date" name="paid"></label>',
      init: function (f) {
        var t = new Date(), d = new Date(t.getFullYear(), t.getMonth() - 1, 20);
        f.due.valueAsDate = new Date(Date.UTC(d.getFullYear(), d.getMonth(), 20));
        f.paid.valueAsDate = new Date(Date.UTC(t.getFullYear(), t.getMonth(), t.getDate()));
      },
      run: function (f) {
        var due = parseDate(f.due.value), paid = parseDate(f.paid.value);
        if (!due || !paid) return row('Enter both dates', '');
        var days = Math.max(0, Math.round((paid - due) / 864e5));
        var int = num(f.tax) * num(f.rate) / 100 * days / 365;
        return row('Days of delay', days) + row('Interest payable', inr(int), true);
      },
      note: 'Rate is editable. Interest runs from the day after the due date up to the date of payment.'
    },

    agm: {
      title: 'ROC Filing Due Dates (from AGM)',
      sub: 'AOC-4, MGT-7/7A and ADT-1 due dates counted from the date of the AGM.',
      form:
        '<label>Financial year ended<input type="date" name="fye"></label>' +
        '<label>Date of AGM<input type="date" name="agm"></label>' +
        '<label class="calc-check"><input type="checkbox" name="first"> This is the company\'s first AGM</label>',
      init: function (f) {
        var y = new Date().getFullYear(), m = new Date().getMonth();
        var fy = m >= 3 ? y : y - 1;
        f.fye.value = fy + '-03-31';
        f.agm.value = fy + '-09-30';
      },
      run: function (f) {
        var fye = parseDate(f.fye.value), agm = parseDate(f.agm.value);
        if (!fye || !agm) return row('Enter both dates', '');
        var last = new Date(fye.getFullYear(), fye.getMonth() + (f.first.checked ? 9 : 6) + 1, 0);
        var late = agm > last;
        return row('Last date to hold AGM (s.96)', fmtDate(last) + (late ? ' ⚠' : '')) +
          row('ADT-1 (15 days from AGM)', fmtDate(addDays(agm, 15))) +
          row('AOC-4 (30 days from AGM)', fmtDate(addDays(agm, 30)), true) +
          row('MGT-7 / MGT-7A (60 days from AGM)', fmtDate(addDays(agm, 60)), true);
      },
      note: 'One Person Companies file AOC-4 within 180 days of year-end. AGM time limit can be extended by the ROC.'
    },

    gratuity: {
      title: 'Gratuity Calculator',
      sub: '15 days\' wages for each completed year of service (wages ÷ 26 × 15).',
      form:
        '<label>Last drawn monthly wages – Basic + DA (₹)<input type="number" min="0" name="w" value="40000"></label>' +
        '<label>Years of service<input type="number" min="0" name="y" value="10"></label>' +
        '<label>Additional months<input type="number" min="0" max="11" name="m" value="7"></label>',
      run: function (f) {
        var yrs = Math.floor(num(f.y)) + (num(f.m) > 6 ? 1 : 0);
        var g = num(f.w) * 15 / 26 * yrs, cap = 2000000;
        return row('Service counted (part-year over 6 months = 1 year)', yrs + ' years') +
          row('Gratuity as per formula', inr(g)) +
          row('Gratuity payable (ceiling ₹20 lakh)', inr(Math.min(g, cap)), true);
      },
      note: 'Normally needs 5 years\' continuous service (not required on death or disablement). Fixed-term employees are eligible after 1 year under the Code on Social Security, 2020.'
    },

    pfesi: {
      title: 'PF & ESI Contribution Calculator',
      sub: 'Monthly employee and employer contributions.',
      form:
        '<label>PF wages per month – Basic + DA (₹)<input type="number" min="0" name="pf" value="18000"></label>' +
        '<label>Gross wages per month (for ESI) (₹)<input type="number" min="0" name="gross" value="20000"></label>' +
        '<label class="calc-check"><input type="checkbox" name="cap" checked> Restrict PF to ₹15,000 wage ceiling</label>',
      run: function (f) {
        var w = num(f.pf), pw = f.cap.checked ? Math.min(w, 15000) : w, ew = Math.min(w, 15000);
        var ee = pw * 0.12, eps = Math.min(ew * 0.0833, 1250), er = pw * 0.12 - eps;
        var edli = ew * 0.005, admin = pw * 0.005;
        var g = num(f.gross), esiOn = g <= 21000;
        var out = row('Employee PF (12%)', inr(ee)) +
          row('Employer – EPS (8.33%, max ₹1,250)', inr(eps)) +
          row('Employer – EPF (balance of 12%)', inr(er)) +
          row('Employer – EDLI (0.5%) + Admin (0.5%)', inr(edli + admin)) +
          row('Total PF deposit', inr(ee + eps + er + edli + admin), true);
        out += esiOn
          ? row('Employee ESI (0.75%)', inr(g * 0.0075)) + row('Employer ESI (3.25%)', inr(g * 0.0325)) +
            row('Total ESI deposit', inr(g * 0.04), true)
          : row('ESI', 'Not applicable (gross wages above ₹21,000)');
        return out;
      },
      note: 'Under the Labour Codes, allowances above 50% of total remuneration are added back to "wages". Minimum EPF admin charge rules not applied.'
    },

    deposit: {
      title: 'FD / PPF Maturity Calculator',
      sub: 'Maturity value of a fixed deposit, or of yearly PPF deposits.',
      form:
        '<label>Investment<select name="t"><option value="fd">Fixed deposit (one-time amount)</option><option value="ppf">PPF (same amount every year)</option></select></label>' +
        '<label>Amount (₹)<input type="number" min="0" name="p" value="150000"></label>' +
        '<label>Interest rate (% p.a.)<input type="number" min="0" step="0.01" name="r" value="7.1"></label>' +
        '<label>Period (years)<input type="number" min="1" name="n" value="15"></label>' +
        '<label>Compounding (FD)<select name="m"><option value="4">Quarterly</option><option value="2">Half-yearly</option><option value="12">Monthly</option><option value="1">Yearly</option></select></label>',
      run: function (f) {
        var P = num(f.p), r = num(f.r) / 100, n = Math.max(1, Math.round(num(f.n))), v, inv;
        if (f.t.value === 'fd') { var m = parseInt(f.m.value, 10); v = P * Math.pow(1 + r / m, m * n); inv = P; }
        else { v = r === 0 ? P * n : P * ((Math.pow(1 + r, n) - 1) / r) * (1 + r); inv = P * n; }
        return row('Total invested', inr(inv)) + row('Interest earned', inr(v - inv)) + row('Maturity value', inr(v), true);
      },
      note: 'PPF assumes each deposit is made at the start of the year and interest is compounded yearly. Rates are editable; check the current notified rate.'
    },

    emi: {
      title: 'Loan EMI Calculator',
      sub: 'Equated monthly instalment for term loans and project finance.',
      form:
        '<label>Loan amount (₹)<input type="number" min="0" name="p" value="2500000"></label>' +
        '<label>Interest rate (% p.a.)<input type="number" min="0" step="0.01" name="r" value="9.5"></label>' +
        '<label>Tenure (months)<input type="number" min="1" name="n" value="84"></label>',
      run: function (f) {
        var P = num(f.p), r = num(f.r) / 1200, n = Math.max(1, Math.round(num(f.n)));
        var e = r === 0 ? P / n : P * r * Math.pow(1 + r, n) / (Math.pow(1 + r, n) - 1);
        return row('Monthly EMI', inr(e), true) + row('Total interest', inr(e * n - P)) + row('Total amount payable', inr(e * n));
      }
    }
  };

  document.querySelectorAll('[data-calc]').forEach(function (box) {
    var c = CALCS[box.getAttribute('data-calc')];
    if (!c) return;
    box.classList.add('calc');
    box.innerHTML = '<h3>' + c.title + '</h3><p class="calc-sub">' + c.sub + '</p>' +
      '<form class="calc-form" onsubmit="return false">' + c.form + '</form>' +
      '<div class="calc-out" aria-live="polite"></div>' + (c.note ? '<p class="calc-note">' + c.note + '</p>' : '');
    var f = box.querySelector('form'), out = box.querySelector('.calc-out');
    if (c.init) c.init(f);
    var go = function () { out.innerHTML = c.run(f); };
    f.addEventListener('input', go); f.addEventListener('change', go); go();
  });
})();
