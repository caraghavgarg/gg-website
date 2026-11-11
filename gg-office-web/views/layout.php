<?php if (!defined('GGO')) { exit; } $navs = array(
  array('home', '/', 'i-home', 'Today'), array('work', '/work', 'i-work', 'Work tracker'), array('clients', '/clients', 'i-clients', 'Clients'),
  array('notices', '/notices', 'i-notice', 'Notices'), array('calendar', '/calendar', 'i-cal', 'Due dates'), 'Staff',
  array('attendance', '/attendance', 'i-clock', 'Attendance'), array('leave', '/leave', 'i-leave', 'Leave'), array('log', '/log', 'i-log', 'Daily work log'));
if (is_admin($me)) { $navs[] = 'Admin'; $navs[] = array('users', '/users', 'i-users', 'Staff &amp; logins'); $navs[] = array('settings', '/settings', 'i-gear', 'Settings &amp; backup'); }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#28292c">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> · <?= h($office) ?> Office</title>
<link rel="stylesheet" href="<?= u('/static/app.css?v=' . GGO_VERSION) ?>">
<link rel="icon" href="<?= u('/static/icon.svg') ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= u('/static/icon.svg') ?>">
</head>
<body class="<?= $me ? 'in' : 'out' ?>">
<?php if ($me): ?>
<aside class="side" id="side">
  <a class="brand" href="<?= u('/') ?>"><span class="mark">GG</span><span><b><?= h($office) ?></b><small>Office</small></span></a>
  <nav>
  <?php foreach ($navs as $nv): if (is_string($nv)): ?><span class="nav-h"><?= $nv ?></span><?php else: ?>
    <a href="<?= u($nv[1]) ?>" class="<?= $nav === $nv[0] ? "on" : "" ?>"><i class="<?= $nv[2] ?>"></i><?= $nv[3] ?></a>
  <?php endif; endforeach; ?>
  </nav>
  <div class="me">
    <a href="<?= u('/account') ?>" class="who"><span class="av"><?= h(initials($me['name'])) ?></span><span><?= h($me['name']) ?><small><?= h($me['role']) ?></small></span></a>
    <form method="post" action="<?= u('/logout') ?>"><?= csrf_field() ?><button class="link">Sign out</button></form>
  </div>
</aside>
<header class="topbar"><button class="burger" type="button" aria-label="Menu" onclick="document.body.classList.toggle('nav-open')"><span></span><span></span><span></span></button><b><?= h($title) ?></b></header>
<div class="scrim" onclick="document.body.classList.remove('nav-open')"></div>
<?php endif; ?>
<main class="main">
  <?php if ($flash !== ''): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>
  <?php if ($error !== ''): ?><div class="flash err"><?= h($error) ?></div><?php endif; ?>
  <?php include __DIR__ . '/' . $view . '.php'; ?>
</main>
<script src="<?= u('/static/app.js?v=' . GGO_VERSION) ?>"></script>
</body>
</html>
