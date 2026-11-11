<?php if (!defined('GGO')) { exit; } ?>
<h1>Leave</h1>
<div class="grid2">
<section class="card">
  <h2>Apply for leave</h2>
  <form method="post" class="form-grid flat">
    <?= csrf_field() ?>
    <label>From<input type="date" name="from" required value="<?= today() ?>"></label>
    <label>To<input type="date" name="to" value="<?= today() ?>"></label>
    <label class="span2">Type<select name="kind"><?php foreach (LEAVE_KINDS as $k): ?><option><?= h($k) ?></option><?php endforeach; ?></select></label>
    <label class="span2">Reason<input name="reason"></label>
    <div class="actions span2"><button class="btn">Send request</button></div>
  </form>
</section>
<section class="card">
  <h2><?= is_partner($me) ? 'All requests' : 'My requests' ?></h2>
  <?php if ($list): ?><ul class="items"><?php foreach ($list as $l): ?><li>
    <span><?= is_partner($me) ? '<b>' . h(uname($l['user_id'])) . '</b> · ' : '' ?><?= h($l['kind']) ?> · <?= fdate($l['from']) ?><?= $l['from'] !== $l['to'] ? ' to ' . fdate($l['to']) : '' ?><?= $l['reason'] ? '<br><small class="muted">' . h($l['reason']) . '</small>' : '' ?>
      <?= !empty($l['decided_by']) ? '<br><small class="muted">' . h($l['status']) . ' by ' . h(uname($l['decided_by'])) . '</small>' : '' ?></span>
    <span class="meta"><span class="pill lv-<?= slug($l['status']) ?>"><?= h($l['status']) ?></span>
      <?php if (is_partner($me) && $l['status'] === 'Pending'): ?><form method="post" action="<?= u('/leave/' . $l['id'] . '/decide') ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="back" value="/leave"><button class="btn sm" name="status" value="Approved">Approve</button><button class="btn sm line" name="status" value="Rejected">Reject</button></form>
      <?php elseif ((int) $l['user_id'] === (int) $me['id'] && $l['status'] === 'Pending'): ?><form method="post" action="<?= u('/leave/' . $l['id'] . '/cancel') ?>" class="inline"><?= csrf_field() ?><button class="link">Withdraw</button></form><?php endif; ?></span>
  </li><?php endforeach; ?></ul><?php else: ?><p class="empty">No leave requests.</p><?php endif; ?>
</section>
</div>
