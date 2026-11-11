<?php if (!defined('GGO')) { exit; } ?>
<div class="head-row"><h1>Attendance</h1>
  <form method="post" action="<?= u('/attendance/punch') ?>" class="punch">
    <?= csrf_field() ?><input type="hidden" name="back" value="/attendance">
    <?php if ($mine): ?><span class="muted">Today: in <?= h($mine['in']) ?><?= $mine['out'] ? ', out ' . h($mine['out']) : '' ?></span><button class="btn line" name="what" value="out"><?= $mine['out'] ? 'Update check-out' : 'Check out' ?></button>
    <?php else: ?><button class="btn" name="what" value="in">Check in now</button><?php endif; ?>
  </form></div>

<?php if (is_partner($me)): ?>
<section class="card">
  <div class="card-h"><h2>Today, <?= fdate(today()) ?></h2><a href="<?= u('/attendance/export.csv?m=' . $m) ?>">Export <?= h($month) ?> for all staff</a></div>
  <div class="chips"><?php foreach ($today_all as $r): $cl = $r['leave'] ? 'leave' : ($r['in'] ? 'in' : 'absent'); ?><a href="<?= u('/attendance?u=' . $r['user']['id'] . '&m=' . $m) ?>" class="chip <?= $cl ?>"><?= h($r['user']['name']) ?> <small><?= $r['leave'] ? 'leave' : ($r['in'] ? h($r['in']) . ($r['out'] ? '–' . h($r['out']) : '') : 'not in') ?></small></a><?php endforeach; ?></div>
</section>
<?php endif; ?>

<section class="card">
  <div class="card-h"><h2><?= h($who['name']) ?> · <?= h($month) ?></h2>
    <span class="btns"><a href="<?= u('/attendance?u=' . $who['id'] . '&m=' . $prev) ?>">← Prev</a> <a href="<?= u('/attendance?u=' . $who['id'] . '&m=' . $next) ?>">Next →</a></span></div>
  <?php if (is_partner($me)): ?><form method="get" class="filters" data-autosubmit><input type="hidden" name="m" value="<?= h($m) ?>"><select name="u"><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $who['id']) ?>><?= h($x['name']) ?></option><?php endforeach; ?></select></form><?php endif; ?>
  <p class="muted">Days present: <b><?= $present ?></b> · Days on approved leave: <b><?= $leaves ?></b></p>
  <div class="tbl"><table>
  <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Note</th><?php if (is_partner($me)): ?><th>Correct</th><?php endif; ?></tr></thead>
  <tbody><?php foreach ($rows as $r): ?><tr class="<?= $r['sunday'] ? 'dim' : '' ?>">
    <td class="nowrap"><?= fdate($r['date']) ?> <small class="muted"><?= $r['day'] ?></small></td>
    <td><?php if ($r['in']): ?><?= h($r['in']) ?><?php elseif ($r['leave']): ?><span class="pill"><?= h($r['leave']) ?></span><?php elseif ($r['sunday']): ?><small class="muted">Sunday</small><?php else: ?>–<?php endif; ?></td>
    <td><?= h($r['out']) ?></td><td><?= $r['hours'] ?></td><td class="small"><?= h($r['note']) ?></td>
    <?php if (is_partner($me)): ?><td><form method="post" action="<?= u('/attendance/edit') ?>" class="att-edit"><?= csrf_field() ?><input type="hidden" name="u" value="<?= $who['id'] ?>"><input type="hidden" name="date" value="<?= $r['date'] ?>">
      <input type="time" name="in" value="<?= h($r['in']) ?>" aria-label="In"><input type="time" name="out" value="<?= h($r['out']) ?>" aria-label="Out"><input name="note" value="<?= h($r['note']) ?>" placeholder="note" aria-label="Note"><button class="btn sm line">Save</button></form></td><?php endif; ?>
  </tr><?php endforeach; ?></tbody></table></div>
</section>
