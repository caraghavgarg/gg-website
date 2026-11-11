<?php
// GG Office (web edition): storage, sessions, security and small helpers.
// Data format is the same JSON as the Windows edition, so backups move both ways.

if (!defined('GGO')) { http_response_code(403); exit; }

date_default_timezone_set('Asia/Kolkata');

// ---------------- storage ----------------

function data_dir() {
    static $d = null;
    if ($d === null) {
        $d = defined('GGO_DATA') ? GGO_DATA : __DIR__ . '/../data';
        if (!is_dir($d)) { @mkdir($d, 0750, true); }
        if (!file_exists($d . '/.htaccess')) {
            @file_put_contents($d . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
        }
        if (!file_exists($d . '/index.html')) { @file_put_contents($d . '/index.html', ''); }
    }
    return $d;
}

function data_file() { return data_dir() . '/office.json'; }

function empty_data() {
    return array('next_id' => 1, 'settings' => array('office' => 'GG Associates'), 'users' => array(), 'clients' => array(),
        'works' => array(), 'notices' => array(), 'attendance' => array(), 'leaves' => array(), 'logs' => array(),
        'comments' => array(), 'due_dates' => array(), 'sessions' => new stdClass(), 'fails' => new stdClass());
}

function normalise(&$d) {
    $base = empty_data();
    foreach ($base as $k => $v) {
        if (!isset($d[$k]) || $d[$k] === null) { $d[$k] = is_object($v) ? array() : $v; }
    }
    if (empty($d['settings']['office'])) { $d['settings']['office'] = 'GG Associates'; }
}

function read_raw() {
    $f = data_file();
    if (!file_exists($f)) { $d = empty_data(); normalise($d); return $d; }
    $fh = fopen($f, 'rb');
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $d = json_decode($raw, true);
    if (!is_array($d)) { die('Office data could not be read. Restore a backup.'); }
    normalise($d);
    return $d;
}

/** Read-only view of the data for this request. */
function D() {
    global $GGO_D;
    if ($GGO_D === null) { $GGO_D = read_raw(); }
    return $GGO_D;
}

/** Change the data under an exclusive lock; the callback receives the data by reference. */
function tx($fn) {
    global $GGO_D;
    $lock = fopen(data_dir() . '/office.lock', 'c');
    flock($lock, LOCK_EX);
    $d = read_raw();
    $ret = $fn($d);
    write_data($d);
    flock($lock, LOCK_UN);
    fclose($lock);
    $GGO_D = $d;
    return $ret;
}

function write_data($d) {
    foreach (array('sessions', 'fails') as $k) { if (empty($d[$k])) { $d[$k] = new stdClass(); } }
    $json = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) { die('Could not save.'); }
    $tmp = data_file() . '.tmp';
    file_put_contents($tmp, $json, LOCK_EX);
    rename($tmp, data_file());
}

function new_id(&$d) { $id = (int) $d['next_id']; $d['next_id'] = $id + 1; return $id; }

function daily_backup() {
    $dir = data_dir() . '/backups';
    if (!is_dir($dir)) { @mkdir($dir, 0750, true); }
    $name = $dir . '/office-' . today() . '.json';
    if (file_exists($name) || !file_exists(data_file())) { return; }
    @copy(data_file(), $name);
    $files = glob($dir . '/office-*.json');
    sort($files);
    while (count($files) > 30) { @unlink(array_shift($files)); }
}

// ---------------- lookups ----------------

function find($list, $id) {
    foreach ($list as $x) { if ((int) $x['id'] === (int) $id) { return $x; } }
    return null;
}
function find_idx($list, $id) {
    foreach ($list as $i => $x) { if ((int) $x['id'] === (int) $id) { return $i; } }
    return -1;
}
function uname($id) { $u = find(D()['users'], $id); return $u ? $u['name'] : ''; }
function cname($id) { $c = find(D()['clients'], $id); return $c ? $c['name'] : ''; }

function active_users() {
    $out = array_values(array_filter(D()['users'], function ($u) { return !empty($u['active']); }));
    usort($out, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    return $out;
}
function sorted_clients() {
    $out = D()['clients'];
    usort($out, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
    return $out;
}

// ---------------- time ----------------

function today() { return date('Y-m-d'); }
function stamp() { return date('Y-m-d H:i'); }
function day_add($n) { return date('Y-m-d', strtotime(($n >= 0 ? '+' : '') . $n . ' days')); }
function valid_day($s) { $t = DateTime::createFromFormat('!Y-m-d', (string) $s); return $t && $t->format('Y-m-d') === $s; }
function fdate($s) { return valid_day($s) ? date('d M Y', strtotime($s)) : $s; }
function dow($s) { return valid_day($s) ? date('D', strtotime($s)) : ''; }

function is_done($s) { return $s === 'Filed / Done'; }
function notice_open($s) { return $s !== 'Closed'; }

function due_class($due, $status) {
    if ($due === '' || $due === null || is_done($status)) { return ''; }
    $t = today();
    if ($due < $t) { return 'overdue'; }
    if ($due === $t) { return 'today'; }
    if ($due <= day_add(7)) { return 'soon'; }
    return '';
}

// ---------------- output helpers ----------------

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function nl($s) { return nl2br(h($s)); }
function sel($a, $b) { return ((string) $a === (string) $b) ? ' selected' : ''; }
function chk($b) { return $b ? ' checked' : ''; }
function slug($s) { return trim(preg_replace('/[^a-z]+/', '-', strtolower($s)), '-'); }
function initials($s) {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($s)), 0, 2) as $w) { $out .= mb_strtoupper(mb_substr($w, 0, 1)); }
    return $out;
}

function base() {
    static $b = null;
    if ($b === null) { $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'); }
    return $b;
}
function u($path) { return base() . $path; }

function f($k) { return isset($_POST[$k]) ? trim((string) $_POST[$k]) : (isset($_GET[$k]) ? trim((string) $_GET[$k]) : ''); }
function fid($k) { return (int) f($k); }
function q($k) { return isset($_GET[$k]) ? trim((string) $_GET[$k]) : ''; }
function qhas($k) { return isset($_GET[$k]); }

function go($to, $msg = '') {
    if ($msg !== '') { $to .= (strpos($to, '?') === false ? '?' : '&') . 'ok=' . rawurlencode($msg); }
    header('Location: ' . u($to), true, 303);
    exit;
}

function safe_next($s) {
    $s = (string) $s;
    return (strpos($s, '/') === 0 && strpos($s, '//') !== 0) ? $s : '/';
}

// ---------------- security ----------------

function send_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    if (is_https()) { header('Strict-Transport-Security: max-age=31536000'); }
}

function is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

function hash_pass($p) { return password_hash($p, PASSWORD_DEFAULT); }

function check_pass($stored, $p) {
    if (strpos($stored, 'pbkdf2$') === 0) { // made by the Windows edition
        $parts = explode('$', $stored);
        if (count($parts) !== 4) { return false; }
        $salt = base64_decode(strtr($parts[2], '-_', '+/') . str_repeat('=', (4 - strlen($parts[2]) % 4) % 4));
        $want = base64_decode($parts[3] . str_repeat('=', (4 - strlen($parts[3]) % 4) % 4));
        $k = hash_pbkdf2('sha256', $p, $salt, (int) $parts[1], strlen($want), true);
        return hash_equals($want, $k);
    }
    return password_verify($p, $stored);
}

function client_ip() { return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''; }

/** 8 wrong passwords from one address within 15 minutes locks that address for 15 minutes. */
function login_locked($ip) {
    $f = D()['fails'];
    if (empty($f[$ip])) { return false; }
    $cut = time() - 900;
    return count(array_filter($f[$ip], function ($t) use ($cut) { return $t > $cut; })) >= 8;
}
function note_fail($ip) {
    tx(function (&$d) use ($ip) {
        $cut = time() - 900;
        foreach ($d['fails'] as $k => $list) {
            $d['fails'][$k] = array_values(array_filter($list, function ($t) use ($cut) { return $t > $cut; }));
            if (!$d['fails'][$k]) { unset($d['fails'][$k]); }
        }
        $d['fails'][$ip][] = time();
    });
}

const COOKIE = 'ggoffice';

function cookie_opts($exp) {
    return array('expires' => $exp, 'path' => base() === '' ? '/' : base() . '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax');
}

/** Returns array(user, session) for the signed-in person, or array(null, null). */
function current_user() {
    static $cur = null;
    if ($cur !== null) { return $cur; }
    $cur = array(null, null);
    if (empty($_COOKIE[COOKIE])) { return $cur; }
    $tok = hash('sha256', $_COOKIE[COOKIE]);
    $ss = isset(D()['sessions'][$tok]) ? D()['sessions'][$tok] : null;
    if (!$ss || $ss['expires'] < date('c')) { return $cur; }
    $u = find(D()['users'], $ss['user_id']);
    if (!$u || empty($u['active'])) { return $cur; }
    $cur = array($u, $ss);
    return $cur;
}
function me() { $c = current_user(); return $c[0]; }
function csrf() { $c = current_user(); return $c[1] ? $c[1]['csrf'] : ''; }

function start_session($uid, $remember) {
    $raw = bin2hex(random_bytes(24));
    $hours = $remember ? 24 * 14 : 12;
    tx(function (&$d) use ($raw, $uid, $hours) {
        $now = date('c');
        foreach ($d['sessions'] as $k => $s) { if ($s['expires'] < $now) { unset($d['sessions'][$k]); } }
        $d['sessions'][hash('sha256', $raw)] = array('user_id' => $uid, 'csrf' => bin2hex(random_bytes(16)),
            'expires' => date('c', time() + $hours * 3600), 'ip' => client_ip(), 'started' => stamp());
    });
    setcookie(COOKIE, $raw, cookie_opts($remember ? time() + $hours * 3600 : 0));
}

function end_session() {
    if (!empty($_COOKIE[COOKIE])) {
        $tok = hash('sha256', $_COOKIE[COOKIE]);
        tx(function (&$d) use ($tok) { unset($d['sessions'][$tok]); });
    }
    setcookie(COOKIE, '', cookie_opts(time() - 3600));
}

function is_partner($u) { return $u && ($u['role'] === 'admin' || $u['role'] === 'partner'); }
function is_admin($u) { return $u && $u['role'] === 'admin'; }

// ---------------- rendering ----------------

function render($view, $title, $nav = '', $v = array(), $error = '') {
    $flash = q('ok');
    $me = me();
    $office = D()['settings']['office'];
    $csrf = csrf();
    extract($v);
    header('Content-Type: text/html; charset=utf-8');
    include __DIR__ . '/../views/layout.php';
    exit;
}

/** Shortcut used inside views: hidden CSRF field. */
function csrf_field() { return '<input type="hidden" name="csrf" value="' . h(csrf()) . '">'; }

function csv_out($name, $rows) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    foreach ($rows as $r) { fputcsv($out, $r); }
    fclose($out);
    exit;
}
