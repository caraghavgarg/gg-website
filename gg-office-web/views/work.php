<?php if (!defined('GGO')) { exit; } $back = '/work' . ($query !== '' ? '?' . $query : ''); ?>
<div class="head-row"><h1>Work tracker <small class="muted"><?= $total ?> <?= $total === 1 ? 'entry' : 'entries' ?></small></h1>
  <div class="btns"><a class="btn line" href="<?= u('/work/export.csv' . ($query !== '' ? '?' . h($query) : '')) ?>">Export</a><a class="btn line" href="<?= u('/work/bulk') ?>">+ For many clients</a><a class="btn" href="<?= u('/work/new') ?>">+ Add work</a></div></div>
<form class="filters" method="get" data-autosubmit>
  <input type="search" name="q" value="<?= h($fl['q']) ?>" placeholder="Search client, PAN, period, remarks">
  <select name="who">
    <option value="me"<?= sel($fl['who'], 'me') ?>>Assigned to me</option>
    <option value="all"<?= sel($fl['who'], 'all') ?>>Everyone</option>
    <option value="none"<?= sel($fl['who'], 'none') ?>>Not assigned</option>
    <?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $fl['who']) ?>><?= h($x['name']) ?></option><?php endforeach; ?>
  </select>
  <select name="status">
    <option value="open"<?= sel($fl['status'], 'open') ?>>All pending</option>
    <option value="any"<?= sel($fl['status'], 'any') ?>>Pending and done</option>
    <?php foreach (WORK_STATUSES as $s): ?><option<?= sel($s, $fl['status']) ?>><?= h($s) ?></option><?php endforeach; ?>
  </select>
  <select name="type"><option value="">All work types</option><?php foreach (WORK_TYPES as $t): ?><option<?= sel($t, $fl['type']) ?>><?= h($t) ?></option><?php endforeach; ?></select>
  <select name="due">
    <option value="">Any due date</option>
    <option value="overdue"<?= sel($fl['due'], 'overdue') ?>>Overdue</option>
    <option value="week"<?= sel($fl['due'], 'week') ?>>Due within 7 days</option>
    <option value="month"<?= sel($fl['due'], 'month') ?>>Due within a month</option>
    <option value="nodate"<?= sel($fl['due'], 'nodate') ?>>No due date</option>
  </select>
  <?php if ($fl['client'] !== ''): ?><input type="hidden" name="client" value="<?= h($fl['client']) ?>"><a class="chip" href="<?= u('/work?who=all') ?>">Client: <?= h($client_name) ?> ✕</a><?php endif; ?>
</form>
<?php if ($list): ?>
<div class="card tbl"><table class="work">
<thead><tr><th>Client</th><th>Work</th><th>With</th><th>Due</th><th>Status</th><th class="hide-sm">Remarks</th></tr></thead>
<tbody><?php foreach ($list as $x): ?><tr class="<?= due_class($x['due'], $x['status']) ?>">
  <td><a href="<?= u('/work/' . $x['id'] . '?back=' . rawurlencode($back)) ?>"><b><?= h(cname($x['client_id'])) ?></b></a></td>
  <td><?= h($x['type']) ?><?= $x['period'] !== '' ? '<br><small class="muted">' . h($x['period']) . '</small>' : '' ?></td>
  <td><?= h(uname($x['assigned']) ?: '—') ?></td>
  <td class="nowrap"><?php if ($x['due']): ?><span class="duet"><?= fdate($x['due']) ?></span><?php endif; ?><?= $x['priority'] !== 'Normal' ? '<br><small class="prio">' . h($x['priority']) . '</small>' : '' ?></td>
  <td><form method="post" action="<?= u('/work/' . $x['id'] . '/status') ?>" data-autosubmit><?= csrf_field() ?><input type="hidden" name="back" value="<?= h($back) ?>">
    <select name="status" class="pillsel st-<?= slug($x['status']) ?>" aria-label="Status"><?php foreach (WORK_STATUSES as $s): ?><option<?= sel($s, $x['status']) ?>><?= h($s) ?></option><?php endforeach; ?></select></form></td>
  <td class="hide-sm small"><?= h($x['remarks']) ?><?= $x['ack_no'] !== '' ? ' <span class="muted">Ack ' . h($x['ack_no']) . '</span>' : '' ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<?php if ($total > 600): ?><p class="muted">Showing the first 600. Use the filters to narrow the list, or Export to see everything.</p><?php endif; ?>
<?php else: ?>
<div class="card empty-big">Nothing here with these filters.<?php if ($fl['who'] === 'me'): ?> <a href="<?= u('/work?who=all') ?>">See everyone's work</a>.<?php endif; ?></div>
<?php endif; ?>
