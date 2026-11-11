<?php if (!defined('GGO')) { exit; } ?>
<div class="head-row"><div><h1><?= h($c['name']) ?><?= empty($c['active']) ? ' <span class="pill">Inactive</span>' : '' ?></h1><p class="muted"><?= h($c['type']) ?><?= $c['file_no'] !== '' ? ' · File ' . h($c['file_no']) : '' ?><?= $c['group'] !== '' ? ' · ' . h($c['group']) : '' ?></p></div>
  <div class="btns"><a class="btn line" href="<?= u('/clients/' . $c['id'] . '/edit') ?>">Edit</a><a class="btn line" href="<?= u('/notices/new?client=' . $c['id']) ?>">+ Notice</a><a class="btn" href="<?= u('/work/new?client=' . $c['id']) ?>">+ Work</a></div></div>
<div class="grid2">
<section class="card">
  <dl class="facts">
    <?php foreach (array('pan' => 'PAN', 'gstin' => 'GSTIN', 'tan' => 'TAN', 'cin' => 'CIN / LLPIN') as $k => $lab): if ($c[$k] !== ''): ?><dt><?= $lab ?></dt><dd><code><?= h($c[$k]) ?></code></dd><?php endif; endforeach; ?>
    <?php if ($c['phone'] !== ''): ?><dt>Phone</dt><dd><a href="tel:<?= h($c['phone']) ?>"><?= h($c['phone']) ?></a></dd><?php endif; ?>
    <?php if ($c['email'] !== ''): ?><dt>Email</dt><dd><a href="mailto:<?= h($c['email']) ?>"><?= h($c['email']) ?></a></dd><?php endif; ?>
    <?php if ($c['address'] !== ''): ?><dt>Address</dt><dd><?= nl($c['address']) ?></dd><?php endif; ?>
    <?php if (!empty($c['manager'])): ?><dt>Usually handled by</dt><dd><?= h(uname($c['manager'])) ?></dd><?php endif; ?>
  </dl>
  <?php if ($c['notes'] !== ''): ?><h3>Notes</h3><p><?= nl($c['notes']) ?></p><?php endif; ?>
</section>
<section class="card">
  <div class="card-h"><h2>Notices</h2></div>
  <?php if ($notices): ?><ul class="items"><?php foreach ($notices as $n): ?>
    <li class="<?= due_class($n['reply_due'], $n['status']) ?>"><a href="<?= u('/notices/' . $n['id']) ?>"><b><?= h($n['act']) ?><?= $n['section'] ? ' · ' . h($n['section']) : '' ?></b><span><?= h($n['period']) ?><?= $n['reply_due'] ? ' · reply by ' . fdate($n['reply_due']) : '' ?></span></a><span class="pill"><?= h($n['status']) ?></span></li><?php endforeach; ?></ul>
  <?php else: ?><p class="empty">No notices recorded.</p><?php endif; ?>
</section>
</div>
<section class="card">
  <div class="card-h"><h2>Work</h2></div>
  <?php if ($works): ?><div class="tbl"><table>
  <thead><tr><th>Work</th><th>Period</th><th>With</th><th>Due</th><th>Status</th><th>Ack / done</th></tr></thead>
  <tbody><?php foreach ($works as $x): ?><tr class="<?= due_class($x['due'], $x['status']) ?>">
    <td><a href="<?= u('/work/' . $x['id'] . '?back=' . rawurlencode('/clients/' . $c['id'])) ?>"><?= h($x['type']) ?></a></td><td><?= h($x['period']) ?></td><td><?= h(uname($x['assigned'])) ?></td><td class="nowrap"><span class="duet"><?= fdate($x['due']) ?></span></td>
    <td><span class="pill st-<?= slug($x['status']) ?>"><?= h($x['status']) ?></span></td><td><?= h($x['ack_no']) ?><?= $x['done_date'] ? ' <small class="muted">' . fdate($x['done_date']) . '</small>' : '' ?></td></tr><?php endforeach; ?></tbody></table></div>
  <?php else: ?><p class="empty">No work recorded for this client yet.</p><?php endif; ?>
</section>
<?php if (is_partner($me)): ?><form method="post" action="<?= u('/clients/' . $c['id'] . '/delete') ?>" data-confirm="Delete <?= h($c['name']) ?>? This cannot be undone." class="danger-zone"><?= csrf_field() ?><button class="link danger">Delete this client</button></form><?php endif; ?>
