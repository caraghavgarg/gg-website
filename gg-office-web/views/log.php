<?php if (!defined('GGO')) { exit; } $uq = is_partner($me) ? '&u=' . $uid : ''; ?>
<div class="head-row"><h1>Daily work log · <?= fdate($date) ?></h1>
  <div class="btns"><a class="btn line" href="<?= u('/log?d=' . $prev . $uq) ?>">← Previous day</a><a class="btn line" href="<?= u('/log') ?>">Today</a><a class="btn line" href="<?= u('/log?d=' . $next . $uq) ?>">Next day →</a></div></div>
<div class="grid2">
<section class="card">
  <h2>Add what I worked on</h2>
  <form method="post" class="form-grid flat">
    <?= csrf_field() ?>
    <label>Date<input type="date" name="date" value="<?= h($date) ?>"></label>
    <label>Hours <small>(optional)</small><input name="hours" inputmode="decimal" placeholder="e.g. 2.5"></label>
    <label class="span2">Client <small>(optional)</small>
      <input type="search" class="pick-filter" data-for="log_client" placeholder="Type to find the client…">
      <select name="client_id" id="log_client"><option value="0">— office / general —</option><?php foreach (sorted_clients() as $c): if (!empty($c['active'])): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endif; endforeach; ?></select></label>
    <label class="span2">Work done<textarea name="text" rows="3" required placeholder="e.g. Prepared ITR computation, reconciled 26AS"></textarea></label>
    <div class="actions span2"><button class="btn">Add</button></div>
  </form>
</section>
<section class="card">
  <div class="card-h"><h2>Entries</h2>
  <?php if (is_partner($me)): ?><form method="get" data-autosubmit><input type="hidden" name="d" value="<?= h($date) ?>"><select name="u"><option value="0">Everyone</option><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $uid) ?>><?= h($x['name']) ?></option><?php endforeach; ?></select></form><?php endif; ?></div>
  <?php if ($list): ?><ul class="items"><?php foreach ($list as $l): ?><li><span><?= is_partner($me) ? '<b>' . h(uname($l['user_id'])) . '</b> · ' : '' ?><?= !empty($l['client_id']) ? '<a href="' . u('/clients/' . $l['client_id']) . '">' . h(cname($l['client_id'])) . '</a> · ' : '' ?><?= $l['hours'] !== '' ? h($l['hours']) . ' h' : '' ?><br><?= nl($l['text']) ?></span>
    <?php if ((int) $l['user_id'] === (int) $me['id'] || is_partner($me)): ?><form method="post" action="<?= u('/log/' . $l['id'] . '/delete') ?>" class="inline" data-confirm="Remove this entry?"><?= csrf_field() ?><button class="x" title="Remove">×</button></form><?php endif; ?></li><?php endforeach; ?></ul>
  <?php else: ?><p class="empty">No entries for this day.</p><?php endif; ?>
</section>
</div>
