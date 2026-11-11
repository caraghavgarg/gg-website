<?php
if (!defined('GGO')) { http_response_code(403); exit; }

function hours_between($in, $out) {
    if (!preg_match('/^\d\d:\d\d$/', (string) $in) || !preg_match('/^\d\d:\d\d$/', (string) $out)) { return ''; }
    $a = strtotime("2000-01-01 $in"); $b = strtotime("2000-01-01 $out");
    if ($b <= $a) { return ''; }
    $m = ($b - $a) / 60;
    return sprintf('%d:%02d', floor($m / 60), $m % 60);
}

function on_leave($uid, $ds) {
    foreach (D()['leaves'] as $l) { if ((int) $l['user_id'] === (int) $uid && $l['status'] === 'Approved' && $l['from'] <= $ds && $l['to'] >= $ds) { return $l['kind']; } }
    return '';
}

function attendance($me) {
    $m = q('m');
    if (!valid_day($m . '-01')) { $m = date('Y-m'); }
    $uid = (int) $me['id'];
    if (is_partner($me) && (int) q('u') > 0) { $uid = (int) q('u'); }
    $who = find(D()['users'], $uid) ?: $me;
    $start = strtotime($m . '-01');
    $rows = array(); $present = 0; $leaves = 0;
    for ($t = $start; date('Y-m', $t) === $m; $t = strtotime('+1 day', $t)) {
        $ds = date('Y-m-d', $t);
        if ($ds > today()) { break; }
        $row = array('date' => $ds, 'day' => date('D', $t), 'sunday' => date('w', $t) === '0', 'in' => '', 'out' => '', 'note' => '', 'hours' => '', 'leave' => on_leave($uid, $ds));
        foreach (D()['attendance'] as $a) {
            if ((int) $a['user_id'] === $uid && $a['date'] === $ds) { $row['in'] = $a['in']; $row['out'] = $a['out']; $row['note'] = $a['note']; $row['hours'] = hours_between($a['in'], $a['out']); }
        }
        if ($row['in'] !== '') { $present++; }
        if ($row['leave'] !== '') { $leaves++; }
        $rows[] = $row;
    }
    $rows = array_reverse($rows);
    $mine = null;
    foreach (D()['attendance'] as $a) { if ((int) $a['user_id'] === (int) $me['id'] && $a['date'] === today()) { $mine = $a; } }
    render('attendance', 'Attendance', 'attendance', array('rows' => $rows, 'who' => $who, 'm' => $m, 'month' => date('F Y', $start),
        'prev' => date('Y-m', strtotime('-1 month', $start)), 'next' => date('Y-m', strtotime('+1 month', $start)),
        'present' => $present, 'leaves' => $leaves, 'today_all' => is_partner($me) ? team_rows() : array(), 'mine' => $mine));
}

function punch($me) {
    $what = f('what'); $hm = date('H:i'); $t = today(); $uid = (int) $me['id'];
    $msg = tx(function (&$d) use ($what, $hm, $t, $uid) {
        $idx = -1;
        foreach ($d['attendance'] as $i => $a) { if ((int) $a['user_id'] === $uid && $a['date'] === $t) { $idx = $i; } }
        if ($what === 'in' && $idx < 0) {
            $d['attendance'][] = array('id' => new_id($d), 'user_id' => $uid, 'date' => $t, 'in' => $hm, 'out' => '', 'note' => '');
            return "Checked in at $hm.";
        }
        if ($what === 'out' && $idx >= 0) { $d['attendance'][$idx]['out'] = $hm; return "Checked out at $hm."; }
        return '';
    });
    go(safe_next(f('back')), $msg);
}

function att_edit($me) {
    $uid = fid('u'); $dt = f('date');
    if (!valid_day($dt)) { go('/attendance', 'Choose a date.'); }
    $in = f('in'); $out = f('out'); $note = f('note');
    tx(function (&$d) use ($uid, $dt, $in, $out, $note) {
        $idx = -1;
        foreach ($d['attendance'] as $i => $a) { if ((int) $a['user_id'] === $uid && $a['date'] === $dt) { $idx = $i; } }
        if ($in === '' && $out === '' && $note === '') { if ($idx >= 0) { array_splice($d['attendance'], $idx, 1); } return; }
        if ($idx < 0) { $d['attendance'][] = array('id' => new_id($d), 'user_id' => $uid, 'date' => $dt, 'in' => $in, 'out' => $out, 'note' => $note); return; }
        $d['attendance'][$idx]['in'] = $in; $d['attendance'][$idx]['out'] = $out; $d['attendance'][$idx]['note'] = $note;
    });
    go("/attendance?u=$uid&m=" . substr($dt, 0, 7), 'Attendance corrected for ' . fdate($dt) . '.');
}

function att_export($me) {
    $m = q('m');
    if (!valid_day($m . '-01')) { $m = date('Y-m'); }
    $rows = array(array('Staff', 'Date', 'In', 'Out', 'Hours', 'Leave', 'Note'));
    $start = strtotime($m . '-01');
    foreach (active_users() as $s) {
        for ($t = $start; date('Y-m', $t) === $m; $t = strtotime('+1 day', $t)) {
            $ds = date('Y-m-d', $t); $in = $out = $note = '';
            foreach (D()['attendance'] as $a) { if ((int) $a['user_id'] === (int) $s['id'] && $a['date'] === $ds) { $in = $a['in']; $out = $a['out']; $note = $a['note']; } }
            $rows[] = array($s['name'], $ds, $in, $out, hours_between($in, $out), on_leave($s['id'], $ds), $note);
        }
    }
    csv_out("attendance-$m.csv", $rows);
}

// ---------------- leave ----------------

function leave_list($me) {
    $list = array_values(array_filter(D()['leaves'], function ($l) use ($me) { return is_partner($me) || (int) $l['user_id'] === (int) $me['id']; }));
    usort($list, function ($a, $b) {
        $pa = $a['status'] === 'Pending'; $pb = $b['status'] === 'Pending';
        if ($pa !== $pb) { return $pa ? -1 : 1; }
        return strcmp($b['from'], $a['from']);
    });
    render('leave', 'Leave', 'leave', array('list' => array_slice($list, 0, 300)));
}

function leave_apply($me) {
    $from = f('from'); $to = f('to') ?: $from; $kind = f('kind'); $reason = f('reason');
    if (!valid_day($from) || !valid_day($to) || $to < $from) { go('/leave', "Enter the leave dates (the 'to' date cannot be before the 'from' date)."); }
    tx(function (&$d) use ($me, $from, $to, $kind, $reason) {
        $d['leaves'][] = array('id' => new_id($d), 'user_id' => (int) $me['id'], 'from' => $from, 'to' => $to, 'kind' => $kind, 'reason' => $reason, 'status' => 'Pending', 'decided_by' => 0, 'created' => stamp());
    });
    go('/leave', 'Leave request sent to the partners.');
}

function leave_cancel($me, $id) {
    tx(function (&$d) use ($me, $id) {
        $i = find_idx($d['leaves'], $id);
        if ($i >= 0 && (int) $d['leaves'][$i]['user_id'] === (int) $me['id'] && $d['leaves'][$i]['status'] === 'Pending') { array_splice($d['leaves'], $i, 1); }
    });
    go('/leave', 'Leave request withdrawn.');
}

function leave_decide($me, $id) {
    $st = f('status');
    if (in_array($st, array('Approved', 'Rejected', 'Pending'), true)) {
        tx(function (&$d) use ($me, $id, $st) {
            $i = find_idx($d['leaves'], $id);
            if ($i >= 0) { $d['leaves'][$i]['status'] = $st; $d['leaves'][$i]['decided_by'] = (int) $me['id']; }
        });
    }
    go(safe_next(f('back')), 'Leave ' . strtolower($st) . '.');
}

// ---------------- daily work log ----------------

function log_list($me) {
    $dt = q('d');
    if (!valid_day($dt)) { $dt = today(); }
    $uid = is_partner($me) ? (int) q('u') : (int) $me['id'];
    $list = array_values(array_filter(D()['logs'], function ($l) use ($dt, $uid) { return $l['date'] === $dt && ($uid === 0 || (int) $l['user_id'] === $uid); }));
    usort($list, function ($a, $b) {
        $c = strcasecmp(uname($a['user_id']), uname($b['user_id']));
        return $c !== 0 ? $c : $a['id'] - $b['id'];
    });
    $t = strtotime($dt);
    render('log', 'Daily work log', 'log', array('list' => $list, 'date' => $dt, 'uid' => $uid,
        'prev' => date('Y-m-d', strtotime('-1 day', $t)), 'next' => date('Y-m-d', strtotime('+1 day', $t))));
}

function log_add($me) {
    $dt = f('date'); $text = f('text');
    if (!valid_day($dt)) { $dt = today(); }
    if ($text === '') { go("/log?d=$dt", 'Write what you worked on.'); }
    $cid = fid('client_id'); $hours = f('hours');
    tx(function (&$d) use ($me, $dt, $text, $cid, $hours) {
        $d['logs'][] = array('id' => new_id($d), 'user_id' => (int) $me['id'], 'date' => $dt, 'client_id' => $cid, 'hours' => $hours, 'text' => $text, 'created' => stamp());
    });
    go("/log?d=$dt" . (is_partner($me) ? '&u=' . $me['id'] : ''), 'Added to your work log.');
}

function log_delete($me, $id) {
    $dt = tx(function (&$d) use ($me, $id) {
        $i = find_idx($d['logs'], $id);
        if ($i < 0) { return today(); }
        $l = $d['logs'][$i];
        if ((int) $l['user_id'] === (int) $me['id'] || is_partner($me)) { array_splice($d['logs'], $i, 1); }
        return $l['date'];
    });
    go("/log?d=$dt", 'Entry removed.');
}
