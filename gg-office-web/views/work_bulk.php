<?php if (!defined('GGO')) { exit; } ?>
<h1>Add the same work for many clients</h1>
<p class="muted">For example: "Income Tax Return, AY 2026-27" for all individual clients in one go. Clients who already have that work for that period are skipped.</p>
<form method="get" class="filters">
  <select name="ctype" onchange="this.form.submit()"><option value="">Show all active clients</option><?php foreach (CLIENT_TYPES as $t): ?><option<?= sel($t, $ctype) ?>><?= h($t) ?></option><?php endforeach; ?></select>
  <noscript><button class="btn sm">Show</button></noscript>
</form>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="form-grid flat">
    <label>Type of work<select name="type" required><option value="">— choose —</option><?php foreach (WORK_TYPES as $t): ?><option><?= h($t) ?></option><?php endforeach; ?></select></label>
    <label>Period<input name="period" required placeholder="e.g. AY 2026-27"></label>
    <label>Due date<input type="date" name="due"></label>
    <label>Priority<select name="priority"><?php foreach (PRIORITIES as $t): ?><option><?= h($t) ?></option><?php endforeach; ?></select></label>
    <?php if (is_partner($me)): ?>
    <label>Assign to<select name="assigned"><option value="0">— not assigned —</option><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"><?= h($x['name']) ?></option><?php endforeach; ?></select></label>
    <label class="check"><input type="checkbox" name="use_manager" value="1" checked> Give each client to the person who usually handles it, where set</label>
    <?php endif; ?>
  </div>
  <div class="pick-head"><b>Clients (<?= count($clients) ?>)</b> <label class="check"><input type="checkbox" data-checkall="c[]"> Select all</label>
    <input type="search" class="list-filter" data-for="bulk-list" placeholder="Filter list…"></div>
  <div class="pick-list" id="bulk-list"><?php if ($clients): foreach ($clients as $c): ?><label class="check"><input type="checkbox" name="c[]" value="<?= $c['id'] ?>"> <?= h($c['name']) ?> <small class="muted"><?= h($c['type']) ?><?= $c['pan'] !== '' ? ' · ' . h($c['pan']) : '' ?></small></label><?php endforeach; else: ?><p class="empty">No active clients<?= $ctype ? ' of this type' : '' ?>.</p><?php endif; ?></div>
  <div class="actions"><button class="btn">Add work for ticked clients</button><a href="<?= u('/work') ?>">Cancel</a></div>
</form>
