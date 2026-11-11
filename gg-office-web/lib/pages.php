<?php
if (!defined('GGO')) { http_response_code(403); exit; }

const UPDATE_BASE = 'https://raw.githubusercontent.com/caraghavgarg/gg-website/main/dist/';

// ---------------- first-run setup ----------------

function setup_form() {
    if (D()['users']) { go('/login'); }
    render('setup', 'Set up GG Office');
}

function setup_save() {
    if (D()['users']) { go('/login'); }
    $name = f('name'); $user = strtolower(f('username')); $p1 = (string) ($_POST['pass'] ?? ''); $p2 = (string) ($_POST['pass2'] ?? ''); $office = f('office');
    $err = '';
    if ($name === '' || $user === '' || preg_match('/\s/', $user)) { $err = 'Enter your name and a username without spaces.'; }
    elseif (strlen($p1) < 10) { $err = 'This office website is on the internet, so choose a password of at least 10 characters.'; }
    elseif ($p1 !== $p2) { $err = 'The two passwords do not match.'; }
    if ($err) { render('setup', 'Set up GG Office', '', array('v_name' => $name, 'v_user' => $user, 'v_office' => $office), $err); }
    $uid = tx(function (&$d) use ($name, $user, $p1, $office) {
        if ($d['users']) { return 0; }
        if ($office !== '') { $d['settings']['office'] = $office; }
        $id = new_id($d);
        $d['users'][] = array('id' => $id, 'name' => $name, 'username' => $user, 'pass' => hash_pass($p1), 'role' => 'admin', 'active' => true, 'created' => stamp());
        if (!$d['due_dates']) { seed_due_dates($d); }
        return $id;
    });
    if (!$uid) { go('/login'); }
    start_session($uid, false);
    go('/users', 'Welcome! Now add your partners and staff.');
}

// ---------------- sign in ----------------

function login_form() {
    if (!D()['users']) { go('/setup'); }
    if (me()) { go('/'); }
    render('login', 'Sign in', '', array('next' => q('next'), 'v_user' => ''));
}

function login_post() {
    $ip = client_ip();
    $user = strtolower(f('username')); $pass = (string) ($_POST['pass'] ?? ''); $next = f('next');
    if (login_locked($ip)) {
        render('login', 'Sign in', '', array('next' => $next, 'v_user' => $user), 'Too many wrong tries. Wait 15 minutes and try again.');
    }
    $found = null;
    foreach (D()['users'] as $u) { if ($u['username'] === $user && !empty($u['active'])) { $found = $u; } }
    if (!$found || !check_pass($found['pass'], $pass)) {
        note_fail($ip);
        usleep(400000);
        render('login', 'Sign in', '', array('next' => $next, 'v_user' => $user), 'Wrong username or password.');
    }
    // Move hashes from the Windows edition to the stronger PHP format.
    if (strpos($found['pass'], 'pbkdf2$') === 0 || password_needs_rehash($found['pass'], PASSWORD_DEFAULT)) {
        $uid = $found['id'];
        tx(function (&$d) use ($uid, $pass) { $i = find_idx($d['users'], $uid); if ($i >= 0) { $d['users'][$i]['pass'] = hash_pass($pass); } });
    }
    start_session($found['id'], f('remember') === '1');
    go(safe_next($next));
}

function logout() {
    end_session();
    go('/login');
}

// ---------------- dashboard ----------------

function team_rows() {
    $t = today();
    $rows = array();
    foreach (active_users() as $s) {
        $row = array('user' => $s, 'open' => 0, 'over' => 0, 'today' => 0, 'in' => '', 'out' => '', 'leave' => false);
        foreach (D()['works'] as $x) {
            if ((int) $x['assigned'] === (int) $s['id'] && !is_done($x['status'])) {
                $row['open']++;
                if ($x['due'] !== '' && $x['due'] < $t) { $row['over']++; }
                if ($x['due'] === $t) { $row['today']++; }
            }
        }
        foreach (D()['attendance'] as $a) { if ((int) $a['user_id'] === (int) $s['id'] && $a['date'] === $t) { $row['in'] = $a['in']; $row['out'] = $a['out']; } }
        foreach (D()['leaves'] as $l) { if ((int) $l['user_id'] === (int) $s['id'] && $l['status'] === 'Approved' && $l['from'] <= $t && $l['to'] >= $t) { $row['leave'] = true; } }
        $rows[] = $row;
    }
    return $rows;
}

function dashboard($me) {
    $d = D(); $t = today(); $uid = (int) $me['id'];
    $mine = array_values(array_filter($d['works'], function ($x) use ($uid) { return (int) $x['assigned'] === $uid && !is_done($x['status']); }));
    sort_work($mine);
    $c = array('open' => count($mine), 'over' => 0, 'week' => 0, 'notices' => 0);
    foreach ($mine as $x) {
        if ($x['due'] !== '' && $x['due'] < $t) { $c['over']++; }
        if ($x['due'] !== '' && $x['due'] >= $t && $x['due'] <= day_add(7)) { $c['week']++; }
    }
    $mine = array_slice($mine, 0, 15);
    $partner = is_partner($me);
    $notices = array_values(array_filter($d['notices'], function ($n) use ($uid, $partner) {
        return notice_open($n['status']) && ((int) $n['assigned'] === $uid || ($partner && (int) $n['assigned'] === 0));
    }));
    sort_notices($notices);
    $c['notices'] = count($notices);
    $dues = array_values(array_filter($d['due_dates'], function ($x) use ($t) { return $x['date'] >= $t && $x['date'] <= day_add(14); }));
    usort($dues, function ($a, $b) { return strcmp($a['date'], $b['date']); });
    $att = null;
    foreach ($d['attendance'] as $a) { if ((int) $a['user_id'] === $uid && $a['date'] === $t) { $att = $a; } }
    $v = array('mine' => $mine, 'notices' => $notices, 'dues' => $dues, 'att' => $att, 'c' => $c);
    if ($partner) {
        $v['team'] = team_rows();
        $v['pending_leave'] = array_values(array_filter($d['leaves'], function ($l) { return $l['status'] === 'Pending'; }));
        $v['unassigned'] = count(array_filter($d['works'], function ($x) { return (int) $x['assigned'] === 0 && !is_done($x['status']); }));
    }
    render('dashboard', 'Today', 'home', $v);
}

// ---------------- my account ----------------

function account_form($me) { render('account', 'My account', 'account'); }

function account_save($me) {
    $old = (string) ($_POST['old'] ?? ''); $p1 = (string) ($_POST['pass'] ?? ''); $p2 = (string) ($_POST['pass2'] ?? '');
    $err = '';
    if (!check_pass($me['pass'], $old)) { $err = 'Your current password is not correct.'; }
    elseif (strlen($p1) < 10) { $err = 'Choose a new password of at least 10 characters.'; }
    elseif ($p1 !== $p2) { $err = 'The two new passwords do not match.'; }
    if ($err) { render('account', 'My account', 'account', array(), $err); }
    $uid = $me['id'];
    $keep = hash('sha256', $_COOKIE[COOKIE]);
    tx(function (&$d) use ($uid, $p1, $keep) {
        $i = find_idx($d['users'], $uid);
        $d['users'][$i]['pass'] = hash_pass($p1);
        foreach ($d['sessions'] as $k => $s) { if ((int) $s['user_id'] === (int) $uid && $k !== $keep) { unset($d['sessions'][$k]); } }
    });
    go('/account', 'Password changed. You have been signed out on your other devices.');
}

// ---------------- staff & logins (admin) ----------------

function user_list($me) {
    $users = D()['users'];
    usort($users, function ($a, $b) {
        if (!empty($a['active']) !== !empty($b['active'])) { return empty($a['active']) ? 1 : -1; }
        return strcasecmp($a['name'], $b['name']);
    });
    $online = array();
    $now = date('c');
    foreach (D()['sessions'] as $s) { if ($s['expires'] >= $now) { $online[$s['user_id']] = true; } }
    render('users', 'Staff & logins', 'users', array('users' => $users, 'online' => $online));
}

function user_form($me, $id = 0) {
    $item = array('id' => 0, 'name' => '', 'username' => '', 'role' => 'staff', 'active' => true);
    if ($id) { $item = find(D()['users'], $id); if (!$item) { go('/users'); } }
    render('user_form', 'Login', 'users', array('item' => $item));
}

function user_save($me, $id = 0) {
    $d = D();
    $item = array('id' => 0, 'pass' => '', 'created' => stamp());
    if ($id) { $item = find($d['users'], $id); if (!$item) { go('/users'); } }
    $t = $item;
    $t['name'] = f('name'); $t['username'] = strtolower(f('username')); $t['role'] = f('role');
    $t['active'] = f('active') === '1';
    if (!in_array($t['role'], array('staff', 'partner', 'admin'), true)) { $t['role'] = 'staff'; }
    $pass = (string) ($_POST['pass'] ?? '');
    $err = '';
    if ($t['name'] === '' || $t['username'] === '' || preg_match('/\s/', $t['username'])) { $err = 'Enter a name and a username without spaces.'; }
    foreach ($d['users'] as $o) { if ((int) $o['id'] !== (int) $item['id'] && $o['username'] === $t['username']) { $err = 'That username is already used by ' . $o['name'] . '.'; } }
    if (!$id && strlen($pass) < 10) { $err = 'Set a first password of at least 10 characters.'; }
    if ($id && $pass !== '' && strlen($pass) < 10) { $err = 'A new password must have at least 10 characters.'; }
    if ((int) $item['id'] === (int) $me['id'] && ($t['role'] !== 'admin' || !$t['active'])) { $err = 'You cannot remove your own administrator access.'; }
    if ($err) { render('user_form', 'Login', 'users', array('item' => $t), $err); }
    if ($pass !== '') { $t['pass'] = hash_pass($pass); }
    $meid = (int) $me['id'];
    tx(function (&$d) use ($t, $id, $pass, $meid) {
        if (!$id) {
            $t['id'] = new_id($d);
            $d['users'][] = $t;
            return;
        }
        $i = find_idx($d['users'], $id);
        $d['users'][$i] = $t;
        if (($pass !== '' && $id !== $meid) || !$t['active']) {
            foreach ($d['sessions'] as $k => $s) { if ((int) $s['user_id'] === (int) $id) { unset($d['sessions'][$k]); } }
        }
    });
    go('/users', 'Saved ' . $t['name'] . '.');
}

function user_signout($me, $id) {
    tx(function (&$d) use ($id, $me) {
        foreach ($d['sessions'] as $k => $s) { if ((int) $s['user_id'] === (int) $id && (int) $id !== (int) $me['id']) { unset($d['sessions'][$k]); } }
    });
    go('/users/' . $id, 'Signed out on all devices.');
}

// ---------------- comments ----------------

function comment_add($me) {
    $kind = f('kind'); $ref = fid('ref'); $text = f('text');
    $to = $kind === 'work' ? "/work/$ref" : ($kind === 'notice' ? "/notices/$ref" : '/');
    if ($text !== '' && ($kind === 'work' || $kind === 'notice')) {
        tx(function (&$d) use ($kind, $ref, $text, $me) {
            $d['comments'][] = array('id' => new_id($d), 'kind' => $kind, 'ref_id' => $ref, 'user_id' => (int) $me['id'], 'text' => $text, 'at' => stamp());
        });
    }
    header('Location: ' . u($to) . '#notes', true, 303);
    exit;
}

// ---------------- settings, backup, restore, update (admin) ----------------

function remote_version($force = false) {
    $cache = data_dir() . '/update-check.json';
    if (!$force && file_exists($cache) && filemtime($cache) > time() - 3600) {
        $j = json_decode((string) file_get_contents($cache), true);
        return is_array($j) ? ($j['gg-office-web'] ?? '') : '';
    }
    $raw = http_get(UPDATE_BASE . 'versions.json?t=' . time());
    if ($raw !== '') { @file_put_contents($cache, $raw); }
    $j = json_decode($raw, true);
    return is_array($j) ? ($j['gg-office-web'] ?? '') : '';
}

function http_get($url) {
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'GG-Office'));
        $r = curl_exec($c);
        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);
        return ($r !== false && $code === 200) ? $r : '';
    }
    $r = @file_get_contents($url);
    return $r === false ? '' : $r;
}

function settings_page($me) {
    $backups = array_map('basename', glob(data_dir() . '/backups/*.json') ?: array());
    rsort($backups);
    $latest = remote_version(qhas('check'));
    render('settings', 'Settings & backup', 'settings', array(
        'backups' => $backups, 'latest' => $latest, 'update' => $latest !== '' && version_compare($latest, GGO_VERSION, '>'),
        'addr' => (is_https() ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . u('/'),
        'protected' => data_protected(),
    ));
}

/** True when the data folder cannot be opened from the internet. */
function data_protected() {
    $real = realpath(data_dir());
    $root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    if ($real && $root && strpos($real, $root) !== 0) { return true; } // outside the web folder
    return file_exists(data_dir() . '/.htaccess');
}

function settings_save($me) {
    $o = f('office');
    if ($o !== '') { tx(function (&$d) use ($o) { $d['settings']['office'] = $o; }); }
    go('/settings', 'Saved.');
}

function backup_download($me) {
    $name = q('f');
    if ($name !== '') {
        $name = basename($name);
        $file = data_dir() . '/backups/' . $name;
        if (!preg_match('/^[a-z-]+[0-9-]+\.json$/', $name) || !file_exists($file)) { go('/settings'); }
        $raw = file_get_contents($file);
    } else {
        $name = 'gg-office-backup-' . date('Y-m-d-Hi') . '.json';
        $raw = file_exists(data_file()) ? file_get_contents(data_file()) : '{}';
    }
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    echo $raw;
    exit;
}

function restore($me) {
    if (f('confirm') !== 'RESTORE') { go('/settings', 'Type RESTORE in capitals to confirm a restore.'); }
    if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) { go('/settings', 'Choose a backup file first.'); }
    $raw = file_get_contents($_FILES['file']['tmp_name']);
    $nd = json_decode($raw, true);
    if (!is_array($nd) || empty($nd['users'])) { go('/settings', 'That file is not a GG Office backup.'); }
    normalise($nd);
    @mkdir(data_dir() . '/backups', 0750, true);
    if (file_exists(data_file())) { @copy(data_file(), data_dir() . '/backups/before-restore-' . date('Y-m-d-His') . '.json'); }
    $keep = hash('sha256', $_COOKIE[COOKIE]);
    $mine = D()['sessions'][$keep] ?? null;
    tx(function (&$d) use ($nd, $keep, $mine) {
        $d = $nd;
        $d['sessions'] = array();
        $d['fails'] = array();
        if ($mine && find($d['users'], $mine['user_id'])) { $d['sessions'][$keep] = $mine; }
    });
    if (!$mine || !find(D()['users'], $mine['user_id'])) { go('/login', 'Backup restored. Sign in with a login from that backup.'); }
    go('/settings', 'Backup restored. Everyone else needs to sign in again. The data you replaced was saved in the backups folder.');
}

function self_update($me) {
    $latest = remote_version(true);
    if ($latest === '' || !version_compare($latest, GGO_VERSION, '>')) { go('/settings', 'GG Office is already up to date.'); }
    if (!class_exists('ZipArchive')) { go('/settings', 'This server cannot unpack updates (ZipArchive missing). Ask the hosting provider to enable the PHP zip extension.'); }
    $zipRaw = http_get(UPDATE_BASE . 'gg-office-web.zip?v=' . rawurlencode($latest));
    if (strlen($zipRaw) < 1000) { go('/settings', 'Could not download the update. Try again later.'); }
    $tmp = data_dir() . '/update.zip';
    file_put_contents($tmp, $zipRaw);
    $z = new ZipArchive();
    if ($z->open($tmp) !== true || $z->locateName('gg-office-web/index.php') === false) { @unlink($tmp); go('/settings', 'The update file was not valid.'); }
    $root = realpath(__DIR__ . '/..');
    for ($i = 0; $i < $z->numFiles; $i++) {
        $n = $z->getNameIndex($i);
        if (strpos($n, 'gg-office-web/') !== 0) { continue; }
        $rel = substr($n, strlen('gg-office-web/'));
        if ($rel === '' || strpos($rel, '..') !== false || strpos($rel, 'data/') === 0 || $rel === 'config.php') { continue; }
        $dest = $root . '/' . $rel;
        if (substr($n, -1) === '/') { @mkdir($dest, 0755, true); continue; }
        @mkdir(dirname($dest), 0755, true);
        file_put_contents($dest, $z->getFromIndex($i));
    }
    $z->close();
    @unlink($tmp);
    if (function_exists('opcache_reset')) { @opcache_reset(); }
    go('/settings', 'Updated to GG Office ' . $latest . '.');
}
