<?php if (!defined('GGO')) { exit; } ?>
<h1><?= $n['id'] ? h(cname($n['client_id'])) . ' · ' . h($n['act']) . ' notice' : 'Add a notice' ?></h1>
<form method="post" class="card form-grid">
  <?= csrf_field() ?>
  <label class="span2">Client <?php $pick_id = $n['client_id']; include __DIR__ . '/_client_pick.php'; ?></label>
  <label>Law<select name="act"><?php foreach (NOTICE_ACTS as $s): ?><option<?= sel($s, $n['act']) ?>><?= h($s) ?></option><?php endforeach; ?></select></label>
  <label>Section / type <small>(e.g. 143(2), 148A, ASMT-10, DRC-01)</small><input name="section" value="<?= h($n['section']) ?>"></label>
  <label>AY / period<input name="period" value="<?= h($n['period']) ?>" placeholder="e.g. AY 2024-25"></label>
  <label>DIN / reference no.<input name="ref_no" value="<?= h($n['ref_no']) ?>"></label>
  <label>Date of notice<input type="date" name="notice_date" value="<?= h($n['notice_date']) ?>"></label>
  <label>Reply due by<input type="date" name="reply_due" value="<?= h($n['reply_due']) ?>"></label>
  <label>Hearing date<input type="date" name="hearing" value="<?= h($n['hearing']) ?>"></label>
  <label>Handled by
    <?php if (is_partner($me)): ?><select name="assigned"><option value="0">— not assigned —</option><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $n['assigned']) ?>><?= h($x['name']) ?></option><?php endforeach; ?></select>
    <?php else: ?><input value="<?= h(uname($n['assigned']) ?: '—') ?>" disabled><?php endif; ?></label>
  <label>Status<select name="status"><?php foreach (NOTICE_STATUSES as $s): ?><option<?= sel($s, $n['status']) ?>><?= h($s) ?></option><?php endforeach; ?></select></label>
  <label>Replied on<input type="date" name="replied_date" value="<?= h($n['replied_date']) ?>"></label>
  <label class="span2">What the notice asks<textarea name="summary" rows="3"><?= h($n['summary']) ?></textarea></label>
  <label class="span2">Remarks<textarea name="remarks" rows="2"><?= h($n['remarks']) ?></textarea></label>
  <div class="actions span2"><button class="btn">Save notice</button><a href="<?= u('/notices') ?>">Cancel</a>
    <?php if ($n['id']): ?><a href="<?= u('/clients/' . $n['client_id']) ?>">Client page</a><span class="muted small">Added <?= h($n['created']) ?> by <?= h(uname($n['created_by'])) ?></span><?php endif; ?></div>
</form>
<?php if ($n['id']): $note_kind = 'notice'; $note_ref = $n['id']; include __DIR__ . '/_notes.php'; ?>
<?php if (is_partner($me)): ?><form method="post" action="<?= u('/notices/' . $n['id'] . '/delete') ?>" data-confirm="Delete this notice?" class="danger-zone"><?= csrf_field() ?><button class="link danger">Delete this notice</button></form><?php endif; ?>
<?php endif; ?>
