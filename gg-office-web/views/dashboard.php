<?php if (!defined('GGO')) { exit; } ?>
<div class="head-row">
  <div><h1>Good day, <?= h($me['name']) ?></h1><p class="muted"><?= fdate(today()) ?></p></div>
  <form method="post" action="<?= u('/attendance/punch') ?>" class="punch">
    <?= csrf_field() ?><input type="hidden" name="back" value="/">
    <?php if ($att): ?>
      <span class="muted">In <?= h($att['in']) ?><?= $att['out'] ? ' · Out ' . h($att['out']) : '' ?></span>
      <button class="btn line" name="what" value="out"><?= $att['out'] ? 'Update check-out' : 'Check out' ?></button>
    <?php else: ?>
      <span class="muted">Not checked in yet</span>
      <button class="btn" name="what" value="in">Check in</button>
    <?php endif; ?>
  </form>
</div>

<div class="stats">
  <a class="stat" href="<?= u('/work?who=me') ?>"><b><?= $c['open'] ?></b><span>My open work</span></a>
  <a class="stat <?= $c['over'] ? 'bad' : '' ?>" href="<?= u('/work?who=me&due=overdue') ?>"><b><?= $c['over'] ?></b><span>Overdue</span></a>
  <a class="stat" href="<?= u('/work?who=me&due=week') ?>"><b><?= $c['week'] ?></b><span>Due in 7 days</span></a>
  <a class="stat" href="<?= u('/notices?who=me') ?>"><b><?= $c['notices'] ?></b><span>Open notices</span></a>
</div>

<div class="grid2">
  <section class="card">
    <div class="card-h"><h2>My work</h2><a href="<?= u('/work/new') ?>">+ Add work</a></div>
    <?php if ($mine): ?>
    <ul class="items">
      <?php foreach ($mine as $x): ?>
      <li class="<?= due_class($x['due'], $x['status']) ?>"><a href="<?= u('/work/' . $x['id'] . '?back=/') ?>"><b><?= h(cname($x['client_id'])) ?></b><span><?= h($x['type']) ?><?= $x['period'] !== '' ? ' · ' . h($x['period']) : '' ?></span></a>
        <span class="meta"><?php if ($x['due']): ?><span class="duet"><?= fdate($x['due']) ?></span><?php endif; ?><span class="pill st-<?= slug($x['status']) ?>"><?= h($x['status']) ?></span></span></li>
      <?php endforeach; ?>
    </ul>
    <p class="more"><a href="<?= u('/work?who=me') ?>">All my work →</a></p>
    <?php else: ?><p class="empty">Nothing pending. Well done.</p><?php endif; ?>
  </section>

  <section class="card">
    <div class="card-h"><h2>Coming due dates</h2><a href="<?= u('/calendar') ?>">Calendar →</a></div>
    <?php if ($dues): ?>
    <ul class="items compact">
      <?php foreach ($dues as $x): ?><li class="<?= $x['date'] === today() ? 'today' : '' ?>"><span class="dd"><?= fdate($x['date']) ?></span><span><?= h($x['title']) ?> <small class="muted"><?= h($x['law']) ?></small></span></li><?php endforeach; ?>
    </ul>
    <?php else: ?><p class="empty">No statutory dates in the next two weeks.</p><?php endif; ?>
  </section>
</div>

<?php if ($notices): ?>
<section class="card">
  <div class="card-h"><h2>Notices with me</h2><a href="<?= u('/notices/new') ?>">+ Add notice</a></div>
  <ul class="items">
    <?php foreach ($notices as $n): ?>
    <li class="<?= due_class($n['reply_due'], $n['status']) ?>"><a href="<?= u('/notices/' . $n['id']) ?>"><b><?= h(cname($n['client_id'])) ?></b><span><?= h($n['act']) ?><?= $n['section'] ? ' · ' . h($n['section']) : '' ?><?= $n['period'] ? ' · ' . h($n['period']) : '' ?></span></a>
      <span class="meta"><?php if ($n['reply_due']): ?><span class="duet">Reply <?= fdate($n['reply_due']) ?></span><?php endif; ?><?php if ($n['hearing']): ?><span class="duet">Hearing <?= fdate($n['hearing']) ?></span><?php endif; ?><span class="pill"><?= h($n['status']) ?></span></span></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if (is_partner($me)): ?>
<section class="card">
  <div class="card-h"><h2>Team today</h2><?php if ($unassigned): ?><a href="<?= u('/work?who=none') ?>"><?= $unassigned ?> work not assigned →</a><?php endif; ?></div>
  <div class="tbl"><table>
    <thead><tr><th>Staff</th><th>In</th><th>Out</th><th class="r">Open work</th><th class="r">Overdue</th><th class="r">Due today</th></tr></thead>
    <tbody>
    <?php foreach ($team as $r): ?><tr>
      <td><a href="<?= u('/work?who=' . $r['user']['id']) ?>"><?= h($r['user']['name']) ?></a><?= $r['leave'] ? ' <span class="pill">On leave</span>' : '' ?></td>
      <td><?= h($r['in'] ?: '–') ?></td><td><?= h($r['out'] ?: '–') ?></td>
      <td class="r"><?= $r['open'] ?></td><td class="r <?= $r['over'] ? 'bad' : '' ?>"><?= $r['over'] ?></td><td class="r"><?= $r['today'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php if ($pending_leave): ?>
<section class="card">
  <div class="card-h"><h2>Leave waiting for approval</h2></div>
  <ul class="items">
  <?php foreach ($pending_leave as $l): ?><li><span><b><?= h(uname($l['user_id'])) ?></b> · <?= h($l['kind']) ?> · <?= fdate($l['from']) ?><?= $l['from'] !== $l['to'] ? ' to ' . fdate($l['to']) : '' ?><br><small class="muted"><?= h($l['reason']) ?></small></span>
    <form method="post" action="<?= u('/leave/' . $l['id'] . '/decide') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="back" value="/">
    <button class="btn sm" name="status" value="Approved">Approve</button><button class="btn sm line" name="status" value="Rejected">Reject</button></form></li><?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
<?php endif; ?>
