<?php if (!defined('GGO')) { exit; } ?>
<div class="head-row"><h1>Notices register <small class="muted"><?= count($list) ?> shown</small></h1>
  <div class="btns"><a class="btn line" href="<?= u('/notices/export.csv') ?>">Export all</a><a class="btn" href="<?= u('/notices/new') ?>">+ Add notice</a></div></div>
<form class="filters" method="get" data-autosubmit>
  <input type="search" name="q" value="<?= h($qq) ?>" placeholder="Search client, section, DIN, AY">
  <select name="who"><option value="all"<?= sel($who, 'all') ?>>Everyone</option><option value="me"<?= sel($who, 'me') ?>>With me</option><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $who) ?>><?= h($x['name']) ?></option><?php endforeach; ?></select>
  <select name="status"><option value="open"<?= sel($status, 'open') ?>>All open</option><option value="any"<?= sel($status, 'any') ?>>Open and closed</option><?php foreach (NOTICE_STATUSES as $s): ?><option<?= sel($s, $status) ?>><?= h($s) ?></option><?php endforeach; ?></select>
  <select name="act"><option value="">All laws</option><?php foreach (NOTICE_ACTS as $s): ?><option<?= sel($s, $act) ?>><?= h($s) ?></option><?php endforeach; ?></select>
</form>
<?php if ($list): ?>
<div class="card tbl"><table>
<thead><tr><th>Client</th><th>Notice</th><th>Reply due</th><th>Hearing</th><th>With</th><th>Status</th></tr></thead>
<tbody><?php foreach ($list as $n): ?><tr class="<?= due_class($n['reply_due'], $n['status']) ?>">
  <td><a href="<?= u('/notices/' . $n['id']) ?>"><b><?= h(cname($n['client_id'])) ?></b></a></td>
  <td><?= h($n['act']) ?><?= $n['section'] ? ' · ' . h($n['section']) : '' ?><br><small class="muted"><?= h($n['period']) ?><?= $n['ref_no'] ? ' · ' . h($n['ref_no']) : '' ?></small></td>
  <td class="nowrap"><?php if ($n['reply_due']): ?><span class="duet"><?= fdate($n['reply_due']) ?></span><?php endif; ?></td>
  <td class="nowrap <?= due_class($n['hearing'], $n['status']) ?>"><?= fdate($n['hearing']) ?></td>
  <td><?= h(uname($n['assigned']) ?: '—') ?></td><td><span class="pill"><?= h($n['status']) ?></span></td></tr><?php endforeach; ?></tbody>
</table></div>
<?php else: ?><div class="card empty-big">No notices with these filters.</div><?php endif; ?>
