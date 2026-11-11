<?php if (!defined('GGO')) { exit; } ?>
<h1><?= $w['id'] ? h(cname($w['client_id'])) . ' · ' . h($w['type']) : 'Add work' ?></h1>
<form method="post" class="card form-grid">
  <?= csrf_field() ?><input type="hidden" name="back" value="<?= h($back) ?>">
  <label class="span2">Client
    <?php $pick_id = $w['client_id']; include __DIR__ . '/_client_pick.php'; ?>
    <small><a href="<?= u('/clients/new') ?>">New client? Add it first</a></small></label>
  <label>Type of work<select name="type" required><option value="">— choose —</option><?php foreach (WORK_TYPES as $t): ?><option<?= sel($t, $w['type']) ?>><?= h($t) ?></option><?php endforeach; ?></select></label>
  <label>Period <small>(AY / FY / month / quarter)</small><input name="period" value="<?= h($w['period']) ?>" placeholder="e.g. AY 2026-27, Oct 2026, Q2 FY 2026-27"></label>
  <label>Assigned to
    <?php if (is_partner($me)): ?><select name="assigned"><option value="0">— not assigned —</option><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $w['assigned']) ?>><?= h($x['name']) ?></option><?php endforeach; ?></select>
    <?php else: ?><input value="<?= h(uname($w['assigned']) ?: '—') ?>" disabled><?php endif; ?></label>
  <label>Due date<input type="date" name="due" value="<?= h($w['due']) ?>"></label>
  <label>Status<select name="status"><?php foreach (WORK_STATUSES as $s): ?><option<?= sel($s, $w['status']) ?>><?= h($s) ?></option><?php endforeach; ?></select></label>
  <label>Priority<select name="priority"><?php foreach (PRIORITIES as $s): ?><option<?= sel($s, $w['priority']) ?>><?= h($s) ?></option><?php endforeach; ?></select></label>
  <label>Ack no. / SRN / ARN<input name="ack_no" value="<?= h($w['ack_no']) ?>"></label>
  <label>Filed / done on<input type="date" name="done_date" value="<?= h($w['done_date']) ?>"></label>
  <label>Fee <small>(optional)</small><input name="fee" value="<?= h($w['fee']) ?>" inputmode="decimal"></label>
  <label class="span2">Remarks<textarea name="remarks" rows="3" placeholder="Documents pending, query raised, etc."><?= h($w['remarks']) ?></textarea></label>
  <div class="actions span2"><button class="btn">Save</button><a href="<?= u($back !== '' ? safe_next($back) : '/work') ?>">Cancel</a>
    <?php if ($w['id']): ?><span class="muted small">Added <?= h($w['created']) ?> by <?= h(uname($w['created_by'])) ?> · last change <?= h($w['updated']) ?></span><?php endif; ?></div>
</form>
<?php if ($w['id']): $note_kind = 'work'; $note_ref = $w['id']; include __DIR__ . '/_notes.php'; ?>
<?php if (is_partner($me)): ?><form method="post" action="<?= u('/work/' . $w['id'] . '/delete') ?>" data-confirm="Delete this work entry?" class="danger-zone"><?= csrf_field() ?><button class="link danger">Delete this work entry</button></form><?php endif; ?>
<?php endif; ?>
