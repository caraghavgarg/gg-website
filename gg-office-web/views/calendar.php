<?php if (!defined('GGO')) { exit; } $mq = $mine ? '&mine=1' : ''; ?>
<div class="head-row"><h1>Due dates · <?= h($month) ?></h1>
  <div class="btns"><a class="btn line" href="<?= u('/calendar?m=' . $prev . $mq) ?>">← Previous</a><a class="btn line" href="<?= u('/calendar' . ($mine ? '?mine=1' : '')) ?>">This month</a><a class="btn line" href="<?= u('/calendar?m=' . $next . $mq) ?>">Next →</a></div></div>
<p class="muted"><a href="<?= u('/calendar?m=' . $m . ($mine ? '' : '&mine=1')) ?>"><?= $mine ? "Show everyone's work" : 'Show only my work' ?></a> · Statutory dates are the regular ones; check extensions on the official portal.</p>
<?php if ($days): ?>
<div class="cal">
<?php foreach ($days as $dd): $cls = $dd['date'] === today() ? 'today' : (($dd['date'] < today() && !$dd['works'] && !$dd['notices']) ? 'past' : ''); ?>
<div class="cal-day <?= $cls ?>">
  <div class="cal-date"><b><?= substr($dd['date'], 8, 2) ?></b><span><?= dow($dd['date']) ?></span></div>
  <div class="cal-body">
    <?php foreach ($dd['dues'] as $x): ?><div class="cal-due"><span class="law"><?= h($x['law']) ?></span> <?= h($x['title']) ?><?php if (is_partner($me)): ?><form method="post" action="<?= u('/calendar/' . $x['id'] . '/delete') ?>" class="inline" data-confirm="Remove this due date?"><?= csrf_field() ?><button class="x" title="Remove">×</button></form><?php endif; ?></div><?php endforeach; ?>
    <?php foreach ($dd['works'] as $x): ?><a class="cal-work" href="<?= u('/work/' . $x['id']) ?>"><b><?= h(cname($x['client_id'])) ?></b> · <?= h($x['type']) ?><?= $x['period'] ? ' (' . h($x['period']) . ')' : '' ?> <small class="muted"><?= h(uname($x['assigned'])) ?></small></a><?php endforeach; ?>
    <?php foreach ($dd['notices'] as $n): ?><a class="cal-work notice" href="<?= u('/notices/' . $n['id']) ?>"><b><?= h(cname($n['client_id'])) ?></b> · <?= h($n['act']) ?> notice <?= h($n['section']) ?> <small class="muted"><?= h(uname($n['assigned'])) ?></small></a><?php endforeach; ?>
  </div></div>
<?php endforeach; ?>
</div>
<?php else: ?><div class="card empty-big">Nothing due this month.</div><?php endif; ?>
<?php if (is_partner($me)): ?>
<section class="card">
  <h2>Add a due date</h2>
  <form method="post" action="<?= u('/calendar/add') ?>" class="form-grid flat">
    <?= csrf_field() ?>
    <label>Date<input type="date" name="date" required></label>
    <label>Law<select name="law"><?php foreach (LAWS as $l): ?><option><?= h($l) ?></option><?php endforeach; ?></select></label>
    <label class="span2">What is due<input name="title" required placeholder="e.g. Extended due date: ITR audit cases"></label>
    <div class="actions span2"><button class="btn">Add</button></div>
  </form>
</section>
<?php endif; ?>
