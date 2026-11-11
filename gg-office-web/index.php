<?php
/**
 * GG Office (web edition) — office work tracker, clients, notices, due dates,
 * attendance, leave and daily work log for the staff of the firm.
 */
define('GGO', true);
define('GGO_VERSION', '2.0.0');
if (file_exists(__DIR__ . '/config.php')) { require __DIR__ . '/config.php'; }
require __DIR__ . '/lib/core.php';
require __DIR__ . '/lib/lists.php';
require __DIR__ . '/lib/pages.php';
require __DIR__ . '/lib/work.php';
require __DIR__ . '/lib/staff.php';

send_security_headers();

// Always use https when the site has it.
if (!is_https() && !in_array($_SERVER['HTTP_HOST'] ?? '', array('localhost', '127.0.0.1'), true) && strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') !== 0 && !defined('GGO_ALLOW_HTTP')) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (base() !== '' && strpos($path, base()) === 0) { $path = substr($path, strlen(base())); }
if ($path === '' || $path === '/index.php') { $path = '/'; }
$method = $_SERVER['REQUEST_METHOD'];

daily_backup();

// Public pages.
$public = array(
    'GET /setup' => 'setup_form', 'POST /setup' => 'setup_save',
    'GET /login' => 'login_form', 'POST /login' => 'login_post',
);
// level: in = any signed-in person, partner, admin.
$routes = array(
    array('POST', '#^/logout$#', 'in', 'logout'),
    array('GET', '#^/$#', 'in', 'dashboard'),
    array('GET', '#^/account$#', 'in', 'account_form'),
    array('POST', '#^/account$#', 'in', 'account_save'),

    array('GET', '#^/clients$#', 'in', 'client_list'),
    array('GET', '#^/clients/new$#', 'in', 'client_form'),
    array('POST', '#^/clients/new$#', 'in', 'client_save'),
    array('GET', '#^/clients/import$#', 'in', 'client_import_form'),
    array('POST', '#^/clients/import$#', 'in', 'client_import'),
    array('GET', '#^/clients/template\.csv$#', 'in', 'client_template'),
    array('GET', '#^/clients/export\.csv$#', 'in', 'client_export'),
    array('GET', '#^/clients/(\d+)$#', 'in', 'client_view'),
    array('GET', '#^/clients/(\d+)/edit$#', 'in', 'client_form'),
    array('POST', '#^/clients/(\d+)/edit$#', 'in', 'client_save'),
    array('POST', '#^/clients/(\d+)/delete$#', 'partner', 'client_delete'),

    array('GET', '#^/work$#', 'in', 'work_list'),
    array('GET', '#^/work/new$#', 'in', 'work_form'),
    array('POST', '#^/work/new$#', 'in', 'work_save'),
    array('GET', '#^/work/bulk$#', 'in', 'work_bulk_form'),
    array('POST', '#^/work/bulk$#', 'in', 'work_bulk_save'),
    array('GET', '#^/work/export\.csv$#', 'in', 'work_export'),
    array('GET', '#^/work/(\d+)$#', 'in', 'work_form'),
    array('POST', '#^/work/(\d+)$#', 'in', 'work_save'),
    array('POST', '#^/work/(\d+)/status$#', 'in', 'work_status'),
    array('POST', '#^/work/(\d+)/delete$#', 'partner', 'work_delete'),

    array('GET', '#^/notices$#', 'in', 'notice_list'),
    array('GET', '#^/notices/new$#', 'in', 'notice_form'),
    array('POST', '#^/notices/new$#', 'in', 'notice_save'),
    array('GET', '#^/notices/export\.csv$#', 'in', 'notice_export'),
    array('GET', '#^/notices/(\d+)$#', 'in', 'notice_form'),
    array('POST', '#^/notices/(\d+)$#', 'in', 'notice_save'),
    array('POST', '#^/notices/(\d+)/delete$#', 'partner', 'notice_delete'),

    array('POST', '#^/comment$#', 'in', 'comment_add'),

    array('GET', '#^/calendar$#', 'in', 'calendar'),
    array('POST', '#^/calendar/add$#', 'partner', 'due_add'),
    array('POST', '#^/calendar/(\d+)/delete$#', 'partner', 'due_delete'),

    array('GET', '#^/attendance$#', 'in', 'attendance'),
    array('POST', '#^/attendance/punch$#', 'in', 'punch'),
    array('POST', '#^/attendance/edit$#', 'partner', 'att_edit'),
    array('GET', '#^/attendance/export\.csv$#', 'partner', 'att_export'),
    array('GET', '#^/leave$#', 'in', 'leave_list'),
    array('POST', '#^/leave$#', 'in', 'leave_apply'),
    array('POST', '#^/leave/(\d+)/cancel$#', 'in', 'leave_cancel'),
    array('POST', '#^/leave/(\d+)/decide$#', 'partner', 'leave_decide'),
    array('GET', '#^/log$#', 'in', 'log_list'),
    array('POST', '#^/log$#', 'in', 'log_add'),
    array('POST', '#^/log/(\d+)/delete$#', 'in', 'log_delete'),

    array('GET', '#^/users$#', 'admin', 'user_list'),
    array('GET', '#^/users/new$#', 'admin', 'user_form'),
    array('POST', '#^/users/new$#', 'admin', 'user_save'),
    array('GET', '#^/users/(\d+)$#', 'admin', 'user_form'),
    array('POST', '#^/users/(\d+)$#', 'admin', 'user_save'),
    array('POST', '#^/users/(\d+)/signout$#', 'admin', 'user_signout'),
    array('GET', '#^/settings$#', 'admin', 'settings_page'),
    array('POST', '#^/settings$#', 'admin', 'settings_save'),
    array('GET', '#^/settings/backup$#', 'admin', 'backup_download'),
    array('POST', '#^/settings/restore$#', 'admin', 'restore'),
    array('POST', '#^/settings/update$#', 'admin', 'self_update'),
);

if (isset($public["$method $path"])) {
    call_user_func($public["$method $path"]);
    exit;
}

foreach ($routes as $r) {
    if ($r[0] !== $method || !preg_match($r[1], $path, $m)) { continue; }
    if (!D()['users']) { go('/setup'); }
    $me = me();
    if (!$me) {
        if ($method === 'GET') { go('/login?next=' . rawurlencode($path . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']))); }
        go('/login');
    }
    if (($r[2] === 'partner' && !is_partner($me)) || ($r[2] === 'admin' && !is_admin($me))) {
        http_response_code(403);
        render('message', 'Not allowed', '', array('text' => 'This page is only for partners or the administrator.'));
    }
    if ($method === 'POST' && !hash_equals(csrf(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        render('message', 'Form expired', '', array('text' => 'This form has expired. Go back, reload the page and try again.'));
    }
    $id = isset($m[1]) ? (int) $m[1] : 0;
    call_user_func($r[3], $me, $id);
    exit;
}

http_response_code(404);
if (me()) { render('message', 'Page not found', '', array('text' => 'There is no page at this address.')); }
go('/login');
