<?php
if (!defined('GGO')) { http_response_code(403); exit; }

// ---------------- clients ----------------

function client_list($me) {
    $qq = f('q'); $typ = q('type'); $show = q('show');
    $list = array();
    foreach (sorted_clients() as $c) {
        if ($show === '' && empty($c['active'])) { continue; }
        if ($typ !== '' && $c['type'] !== $typ) { continue; }
        if ($qq !== '') {
            $hay = $c['name'] . ' ' . $c['pan'] . ' ' . $c['gstin'] . ' ' . $c['file_no'] . ' ' . $c['group'] . ' ' . $c['phone'];
            if (!has_text($hay, $qq)) { continue; }
        }
        $list[] = $c;
    }
    $open = array();
    foreach (D()['works'] as $x) { if (!is_done($x['status'])) { $open[$x['client_id']] = ($open[$x['client_id']] ?? 0) + 1; } }
    render('clients', 'Clients', 'clients', array('list' => $list, 'qq' => $qq, 'typ' => $typ, 'show' => $show, 'open' => $open, 'total' => count(D()['clients'])));
}

function client_view($me, $id) {
    $c = find(D()['clients'], $id);
    if (!$c) { go('/clients'); }
    $ws = array_values(array_filter(D()['works'], function ($x) use ($id) { return (int) $x['client_id'] === $id; }));
    sort_work($ws);
    $ns = array_values(array_filter(D()['notices'], function ($x) use ($id) { return (int) $x['client_id'] === $id; }));
    sort_notices($ns);
    render('client_view', $c['name'], 'clients', array('c' => $c, 'works' => $ws, 'notices' => $ns));
}

function blank_client() {
    return array('id' => 0, 'name' => '', 'file_no' => '', 'type' => 'Individual', 'pan' => '', 'gstin' => '', 'tan' => '', 'cin' => '', 'phone' => '',
        'email' => '', 'address' => '', 'group' => '', 'manager' => 0, 'notes' => '', 'active' => true, 'created' => '');
}

function client_form($me, $id = 0) {
    $c = blank_client();
    if ($id) { $c = find(D()['clients'], $id); if (!$c) { go('/clients'); } }
    render('client_form', 'Client', 'clients', array('c' => $c));
}

function client_save($me, $id = 0) {
    $c = blank_client();
    if ($id) { $c = find(D()['clients'], $id); if (!$c) { go('/clients'); } }
    $t = $c;
    foreach (array('name', 'file_no', 'type', 'phone', 'email', 'address', 'group', 'notes') as $k) { $t[$k] = f($k); }
    foreach (array('pan', 'gstin', 'tan', 'cin') as $k) { $t[$k] = strtoupper(f($k)); }
    $t['manager'] = fid('manager');
    $t['active'] = f('active') === '1';
    $err = '';
    if ($t['name'] === '') { $err = "Enter the client's name."; }
    if ($t['pan'] !== '') {
        foreach (D()['clients'] as $o) { if ((int) $o['id'] !== (int) $c['id'] && $o['pan'] === $t['pan']) { $err = 'PAN ' . $t['pan'] . ' is already saved for ' . $o['name'] . '.'; } }
    }
    if ($err) { render('client_form', 'Client', 'clients', array('c' => $t), $err); }
    $newId = tx(function (&$d) use ($t, $id) {
        if (!$id) { $t['id'] = new_id($d); $t['created'] = stamp(); $d['clients'][] = $t; return $t['id']; }
        $d['clients'][find_idx($d['clients'], $id)] = $t;
        return $id;
    });
    go('/clients/' . $newId, 'Client saved.');
}

function client_delete($me, $id) {
    foreach (D()['works'] as $x) { if ((int) $x['client_id'] === $id) { go("/clients/$id", "This client has work entries, so it cannot be deleted. Untick 'Active client' instead."); } }
    foreach (D()['notices'] as $x) { if ((int) $x['client_id'] === $id) { go("/clients/$id", "This client has notices, so it cannot be deleted. Untick 'Active client' instead."); } }
    tx(function (&$d) use ($id) { $i = find_idx($d['clients'], $id); if ($i >= 0) { array_splice($d['clients'], $i, 1); } });
    go('/clients', 'Client deleted.');
}

const CLIENT_COLS = array('Name', 'File No', 'Type', 'PAN', 'GSTIN', 'TAN', 'CIN', 'Phone', 'Email', 'Address', 'Group', 'Notes');

function client_template($me) {
    csv_out('clients-template.csv', array(CLIENT_COLS, array('Ramesh Kumar', '101', 'Individual', 'ABCDE1234F', '', '', '', '9812345678', 'ramesh@example.com', 'Sector 7, Kurukshetra', 'Kumar family', '')));
}

function client_export($me) {
    $rows = array(array_merge(CLIENT_COLS, array('Handled by', 'Active')));
    foreach (sorted_clients() as $c) {
        $rows[] = array($c['name'], $c['file_no'], $c['type'], $c['pan'], $c['gstin'], $c['tan'], $c['cin'], $c['phone'], $c['email'], $c['address'], $c['group'], $c['notes'], uname($c['manager']), empty($c['active']) ? 'No' : 'Yes');
    }
    csv_out('clients-' . today() . '.csv', $rows);
}

function client_import_form($me) { render('client_import', 'Import clients', 'clients'); }

function client_import($me) {
    if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) { go('/clients/import', 'Choose a CSV file first.'); }
    $raw = file_get_contents($_FILES['file']['tmp_name']);
    if (strpos($raw, "\xEF\xBB\xBF") === 0) { $raw = substr($raw, 3); }
    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $raw);
    rewind($fh);
    $rows = array();
    while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) { $rows[] = $r; }
    fclose($fh);
    if (count($rows) < 2) { go('/clients/import', "Could not read that file. Save it from Excel as 'CSV UTF-8' and try again."); }
    $alias = array('name' => array('name', 'clientname', 'nameofclient', 'partyname'), 'fileno' => array('fileno', 'file', 'filenumber', 'code', 'clientcode'),
        'type' => array('type', 'status', 'constitution', 'category'), 'phone' => array('phone', 'mobile', 'mobileno', 'phoneno', 'contact'),
        'email' => array('email', 'emailid', 'mail'), 'gstin' => array('gstin', 'gst', 'gstno'), 'pan' => array('pan', 'panno'));
    $idx = array();
    foreach ($rows[0] as $i => $hd) {
        $k = str_replace(array(' ', '_', '.'), '', strtolower(trim($hd)));
        foreach ($alias as $std => $names) { if (in_array($k, $names, true)) { $k = $std; } }
        if (!isset($idx[$k])) { $idx[$k] = $i; }
    }
    if (!isset($idx['name'])) { go('/clients/import', "The first row must have a 'Name' column."); }
    $get = function ($row, $k) use ($idx) { return isset($idx[$k], $row[$idx[$k]]) ? trim($row[$idx[$k]]) : ''; };
    $res = tx(function (&$d) use ($rows, $get) {
        $added = 0; $skipped = 0;
        foreach (array_slice($rows, 1) as $row) {
            $name = $get($row, 'name');
            if ($name === '') { continue; }
            $pan = strtoupper($get($row, 'pan'));
            $dup = false;
            foreach ($d['clients'] as $c) { if (($pan !== '' && $c['pan'] === $pan) || ($pan === '' && strcasecmp($c['name'], $name) === 0)) { $dup = true; break; } }
            if ($dup) { $skipped++; continue; }
            $typ = $get($row, 'type');
            $match = $typ === '' ? 'Individual' : 'Other';
            foreach (CLIENT_TYPES as $ct) { if ($typ !== '' && (strcasecmp($ct, $typ) === 0 || stripos($ct, $typ) === 0)) { $match = $ct; break; } }
            $c = blank_client();
            $c = array_merge($c, array('id' => new_id($d), 'name' => $name, 'file_no' => $get($row, 'fileno'), 'type' => $match, 'pan' => $pan,
                'gstin' => strtoupper($get($row, 'gstin')), 'tan' => strtoupper($get($row, 'tan')), 'cin' => strtoupper($get($row, 'cin')),
                'phone' => $get($row, 'phone'), 'email' => $get($row, 'email'), 'address' => $get($row, 'address'), 'group' => $get($row, 'group'),
                'notes' => $get($row, 'notes'), 'created' => stamp()));
            $d['clients'][] = $c;
            $added++;
        }
        return array($added, $skipped);
    });
    go('/clients', "Imported {$res[0]} clients. {$res[1]} were already in the list and were skipped.");
}

// ---------------- work ----------------

function work_filter($me) {
    $fl = array('who' => q('who'), 'status' => q('status'), 'type' => q('type'), 'q' => q('q'), 'due' => q('due'), 'client' => q('client'));
    if (!qhas('who')) { $fl['who'] = is_partner($me) ? 'all' : 'me'; }
    if (!qhas('status')) { $fl['status'] = 'open'; }
    $t = today(); $out = array();
    foreach (D()['works'] as $x) {
        $as = (string) $x['assigned'];
        if ($fl['who'] === 'me' && (int) $as !== (int) $me['id']) { continue; }
        if ($fl['who'] === 'none' && (int) $as !== 0) { continue; }
        if (!in_array($fl['who'], array('all', 'me', 'none'), true) && $as !== $fl['who']) { continue; }
        if ($fl['status'] === 'open' && is_done($x['status'])) { continue; }
        if (!in_array($fl['status'], array('', 'any', 'open'), true) && $x['status'] !== $fl['status']) { continue; }
        if ($fl['type'] !== '' && $x['type'] !== $fl['type']) { continue; }
        if ($fl['client'] !== '' && (string) $x['client_id'] !== $fl['client']) { continue; }
        if ($fl['due'] === 'overdue' && ($x['due'] === '' || $x['due'] >= $t || is_done($x['status']))) { continue; }
        if ($fl['due'] === 'week' && ($x['due'] === '' || $x['due'] > day_add(7))) { continue; }
        if ($fl['due'] === 'month' && ($x['due'] === '' || $x['due'] > day_add(31))) { continue; }
        if ($fl['due'] === 'nodate' && $x['due'] !== '') { continue; }
        if ($fl['q'] !== '') {
            $c = find(D()['clients'], $x['client_id']);
            $hay = $x['period'] . ' ' . $x['remarks'] . ' ' . $x['ack_no'] . ' ' . $x['type'] . ($c ? ' ' . $c['name'] . ' ' . $c['pan'] . ' ' . $c['file_no'] . ' ' . $c['group'] : '');
            if (!has_text($hay, $fl['q'])) { continue; }
        }
        $out[] = $x;
    }
    sort_work($out);
    return array($fl, $out);
}

function work_list($me) {
    list($fl, $list) = work_filter($me);
    $total = count($list);
    render('work', 'Work tracker', 'work', array('fl' => $fl, 'list' => array_slice($list, 0, 600), 'total' => $total, 'query' => $_SERVER['QUERY_STRING'] ?? '', 'client_name' => cname((int) $fl['client'])));
}

function work_export($me) {
    list(, $list) = work_filter($me);
    $rows = array(array('Client', 'File No', 'PAN', 'Work', 'Period', 'Assigned to', 'Due date', 'Status', 'Priority', 'Ack / SRN', 'Done on', 'Fee', 'Remarks'));
    foreach ($list as $x) {
        $c = find(D()['clients'], $x['client_id']) ?: blank_client();
        $rows[] = array($c['name'], $c['file_no'], $c['pan'], $x['type'], $x['period'], uname($x['assigned']), $x['due'], $x['status'], $x['priority'], $x['ack_no'], $x['done_date'], $x['fee'], $x['remarks']);
    }
    csv_out('work-' . today() . '.csv', $rows);
}

function blank_work($me) {
    return array('id' => 0, 'client_id' => 0, 'type' => '', 'period' => '', 'assigned' => (int) $me['id'], 'due' => '', 'status' => 'Not started', 'priority' => 'Normal',
        'ack_no' => '', 'done_date' => '', 'fee' => '', 'remarks' => '', 'created_by' => 0, 'created' => '', 'updated' => '');
}

function work_form($me, $id = 0) {
    $x = blank_work($me);
    if ($id) { $x = find(D()['works'], $id); if (!$x) { go('/work'); } }
    else {
        $x['client_id'] = (int) q('client');
        $c = find(D()['clients'], $x['client_id']);
        if ($c && !empty($c['manager']) && is_partner($me)) { $x['assigned'] = (int) $c['manager']; }
        $x['type'] = q('type');
    }
    work_render($me, $x);
}

function work_render($me, $x, $err = '') {
    $comments = $x['id'] ? array_values(array_filter(D()['comments'], function ($c) use ($x) { return $c['kind'] === 'work' && (int) $c['ref_id'] === (int) $x['id']; })) : array();
    render('work_form', 'Work', 'work', array('w' => $x, 'comments' => $comments, 'back' => f('back')), $err);
}

function work_save($me, $id = 0) {
    $x = blank_work($me);
    if ($id) { $x = find(D()['works'], $id); if (!$x) { go('/work'); } }
    $t = $x;
    $t['client_id'] = fid('client_id');
    foreach (array('type', 'period', 'due', 'status', 'priority', 'ack_no', 'done_date', 'fee', 'remarks') as $k) { $t[$k] = f($k); }
    if (is_partner($me)) { $t['assigned'] = fid('assigned'); } elseif (!$id) { $t['assigned'] = (int) $me['id']; }
    if (is_done($t['status']) && $t['done_date'] === '') { $t['done_date'] = today(); }
    if (!find(D()['clients'], $t['client_id'])) { work_render($me, $t, 'Choose the client.'); }
    if ($t['type'] === '') { work_render($me, $t, 'Choose the type of work.'); }
    $t['updated'] = stamp();
    tx(function (&$d) use ($t, $id, $me) {
        if (!$id) { $t['id'] = new_id($d); $t['created_by'] = (int) $me['id']; $t['created'] = stamp(); $d['works'][] = $t; return; }
        $d['works'][find_idx($d['works'], $id)] = $t;
    });
    $to = safe_next(f('back'));
    go($to === '/' && f('back') === '' ? '/work' : $to, 'Saved: ' . cname($t['client_id']) . ' · ' . $t['type']);
}

function work_status($me, $id) {
    $st = f('status');
    if (in_array($st, WORK_STATUSES, true)) {
        tx(function (&$d) use ($id, $st) {
            $i = find_idx($d['works'], $id);
            if ($i < 0) { return; }
            $d['works'][$i]['status'] = $st;
            if (is_done($st) && $d['works'][$i]['done_date'] === '') { $d['works'][$i]['done_date'] = today(); }
            $d['works'][$i]['updated'] = stamp();
        });
    }
    go(safe_next(f('back')));
}

function work_delete($me, $id) {
    tx(function (&$d) use ($id) { $i = find_idx($d['works'], $id); if ($i >= 0) { array_splice($d['works'], $i, 1); } });
    go('/work', 'Work entry deleted.');
}

function work_bulk_form($me) {
    $typ = q('ctype');
    $cl = array_values(array_filter(sorted_clients(), function ($c) use ($typ) { return !empty($c['active']) && ($typ === '' || $c['type'] === $typ); }));
    render('work_bulk', 'Add work for many clients', 'work', array('clients' => $cl, 'ctype' => $typ));
}

function work_bulk_save($me) {
    $typ = f('type'); $period = f('period'); $due = f('due'); $prio = f('priority');
    $assigned = fid('assigned'); $useMgr = f('use_manager') === '1';
    if (!is_partner($me)) { $assigned = (int) $me['id']; $useMgr = false; }
    if ($typ === '' || $period === '') { go('/work/bulk', 'Choose the work type and enter the period (for example AY 2026-27).'); }
    $ids = isset($_POST['c']) && is_array($_POST['c']) ? array_map('intval', $_POST['c']) : array();
    $res = tx(function (&$d) use ($ids, $typ, $period, $due, $prio, $assigned, $useMgr, $me) {
        $added = 0; $skipped = 0;
        foreach ($ids as $cid) {
            $c = find($d['clients'], $cid);
            if (!$c) { continue; }
            foreach ($d['works'] as $x) { if ((int) $x['client_id'] === $cid && $x['type'] === $typ && strcasecmp($x['period'], $period) === 0) { $skipped++; continue 2; } }
            $as = ($useMgr && !empty($c['manager'])) ? (int) $c['manager'] : $assigned;
            $d['works'][] = array('id' => new_id($d), 'client_id' => $cid, 'type' => $typ, 'period' => $period, 'assigned' => $as, 'due' => $due, 'status' => 'Not started',
                'priority' => $prio, 'ack_no' => '', 'done_date' => '', 'fee' => '', 'remarks' => '', 'created_by' => (int) $me['id'], 'created' => stamp(), 'updated' => stamp());
            $added++;
        }
        return array($added, $skipped);
    });
    go('/work?who=all&type=' . rawurlencode($typ), "Added {$res[0]} work entries. {$res[1]} clients already had this work for $period and were skipped.");
}

// ---------------- notices ----------------

function notice_list($me) {
    $who = qhas('who') ? q('who') : 'all'; $status = qhas('status') ? q('status') : 'open'; $act = q('act'); $qq = q('q');
    $list = array();
    foreach (D()['notices'] as $n) {
        if ($who === 'me' && (int) $n['assigned'] !== (int) $me['id']) { continue; }
        if (!in_array($who, array('me', 'all'), true) && (string) $n['assigned'] !== $who) { continue; }
        if ($status === 'open' && !notice_open($n['status'])) { continue; }
        if (!in_array($status, array('open', 'any', ''), true) && $n['status'] !== $status) { continue; }
        if ($act !== '' && $n['act'] !== $act) { continue; }
        if ($qq !== '') {
            $c = find(D()['clients'], $n['client_id']);
            $hay = $n['section'] . ' ' . $n['period'] . ' ' . $n['ref_no'] . ' ' . $n['summary'] . ' ' . $n['remarks'] . ($c ? ' ' . $c['name'] . ' ' . $c['pan'] . ' ' . $c['gstin'] : '');
            if (!has_text($hay, $qq)) { continue; }
        }
        $list[] = $n;
    }
    sort_notices($list);
    render('notices', 'Notices register', 'notices', array('list' => $list, 'who' => $who, 'status' => $status, 'act' => $act, 'qq' => $qq));
}

function blank_notice($me) {
    return array('id' => 0, 'client_id' => 0, 'act' => 'Income Tax', 'section' => '', 'period' => '', 'ref_no' => '', 'notice_date' => '', 'reply_due' => '', 'hearing' => '',
        'assigned' => (int) $me['id'], 'status' => 'Received', 'replied_date' => '', 'summary' => '', 'remarks' => '', 'created_by' => 0, 'created' => '', 'updated' => '');
}

function notice_form($me, $id = 0) {
    $n = blank_notice($me);
    if ($id) { $n = find(D()['notices'], $id); if (!$n) { go('/notices'); } }
    else { $n['client_id'] = (int) q('client'); }
    notice_render($n);
}

function notice_render($n, $err = '') {
    $comments = $n['id'] ? array_values(array_filter(D()['comments'], function ($c) use ($n) { return $c['kind'] === 'notice' && (int) $c['ref_id'] === (int) $n['id']; })) : array();
    render('notice_form', 'Notice', 'notices', array('n' => $n, 'comments' => $comments), $err);
}

function notice_save($me, $id = 0) {
    $n = blank_notice($me);
    if ($id) { $n = find(D()['notices'], $id); if (!$n) { go('/notices'); } }
    $t = $n;
    $t['client_id'] = fid('client_id');
    foreach (array('act', 'section', 'period', 'ref_no', 'notice_date', 'reply_due', 'hearing', 'status', 'replied_date', 'summary', 'remarks') as $k) { $t[$k] = f($k); }
    if (is_partner($me)) { $t['assigned'] = fid('assigned'); } elseif (!$id) { $t['assigned'] = (int) $me['id']; }
    if ($t['status'] === 'Replied' && $t['replied_date'] === '') { $t['replied_date'] = today(); }
    if (!find(D()['clients'], $t['client_id'])) { notice_render($t, 'Choose the client.'); }
    $t['updated'] = stamp();
    tx(function (&$d) use ($t, $id, $me) {
        if (!$id) { $t['id'] = new_id($d); $t['created_by'] = (int) $me['id']; $t['created'] = stamp(); $d['notices'][] = $t; return; }
        $d['notices'][find_idx($d['notices'], $id)] = $t;
    });
    go('/notices', 'Notice saved: ' . cname($t['client_id']));
}

function notice_delete($me, $id) {
    tx(function (&$d) use ($id) { $i = find_idx($d['notices'], $id); if ($i >= 0) { array_splice($d['notices'], $i, 1); } });
    go('/notices', 'Notice deleted.');
}

function notice_export($me) {
    $list = D()['notices'];
    sort_notices($list);
    $rows = array(array('Client', 'PAN', 'GSTIN', 'Act', 'Section', 'AY / Period', 'DIN / Ref', 'Notice date', 'Reply due', 'Hearing', 'Handled by', 'Status', 'Replied on', 'Summary', 'Remarks'));
    foreach ($list as $n) {
        $c = find(D()['clients'], $n['client_id']) ?: blank_client();
        $rows[] = array($c['name'], $c['pan'], $c['gstin'], $n['act'], $n['section'], $n['period'], $n['ref_no'], $n['notice_date'], $n['reply_due'], $n['hearing'], uname($n['assigned']), $n['status'], $n['replied_date'], $n['summary'], $n['remarks']);
    }
    csv_out('notices-' . today() . '.csv', $rows);
}

// ---------------- calendar ----------------

function calendar($me) {
    $m = q('m');
    if (!valid_day($m . '-01')) { $m = date('Y-m'); }
    $mine = q('mine') === '1';
    $days = array();
    $add = function ($dt, $k, $item) use (&$days) {
        if (!isset($days[$dt])) { $days[$dt] = array('date' => $dt, 'dues' => array(), 'works' => array(), 'notices' => array()); }
        $days[$dt][$k][] = $item;
    };
    foreach (D()['due_dates'] as $x) { if (strpos($x['date'], $m) === 0) { $add($x['date'], 'dues', $x); } }
    foreach (D()['works'] as $x) { if ($x['due'] !== '' && strpos($x['due'], $m) === 0 && !is_done($x['status']) && (!$mine || (int) $x['assigned'] === (int) $me['id'])) { $add($x['due'], 'works', $x); } }
    foreach (D()['notices'] as $n) {
        if (!notice_open($n['status']) || ($mine && (int) $n['assigned'] !== (int) $me['id'])) { continue; }
        foreach (array_unique(array($n['reply_due'], $n['hearing'])) as $dt) { if ($dt !== '' && strpos($dt, $m) === 0) { $add($dt, 'notices', $n); } }
    }
    ksort($days);
    $start = strtotime($m . '-01');
    render('calendar', 'Due dates', 'calendar', array('days' => array_values($days), 'month' => date('F Y', $start), 'm' => $m,
        'prev' => date('Y-m', strtotime('-1 month', $start)), 'next' => date('Y-m', strtotime('+1 month', $start)), 'mine' => $mine));
}

function due_add($me) {
    $dt = f('date'); $title = f('title');
    if (!valid_day($dt) || $title === '') { go('/calendar', 'Enter a date and a title.'); }
    $law = f('law'); $notes = f('notes');
    tx(function (&$d) use ($dt, $title, $law, $notes) { $d['due_dates'][] = array('id' => new_id($d), 'date' => $dt, 'title' => $title, 'law' => $law, 'notes' => $notes); });
    go('/calendar?m=' . substr($dt, 0, 7), 'Due date added.');
}

function due_delete($me, $id) {
    $m = tx(function (&$d) use ($id) {
        $i = find_idx($d['due_dates'], $id);
        if ($i < 0) { return ''; }
        $m = substr($d['due_dates'][$i]['date'], 0, 7);
        array_splice($d['due_dates'], $i, 1);
        return $m;
    });
    go('/calendar?m=' . $m, 'Due date removed.');
}
