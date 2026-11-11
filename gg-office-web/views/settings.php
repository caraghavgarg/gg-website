<?php if (!defined('GGO')) { exit; } ?>
<h1>Settings &amp; backup</h1>
<?php if ($update): ?><div class="flash"><b>GG Office <?= h($latest) ?> is available</b> (you have <?= GGO_VERSION ?>).
  <form method="post" action="<?= u('/settings/update') ?>" class="inline"><?= csrf_field() ?><button class="btn sm">Update now</button></form></div><?php endif; ?>
<?php if (!$protected): ?><div class="flash err">The data folder is not protected. Contact support before adding real data.</div><?php endif; ?>
<div class="grid2">
<section class="card">
  <h2>Address</h2>
  <p>Staff open GG Office from any computer or phone, anywhere, at:</p>
  <p><code><?= h($addr) ?></code></p>
  <p class="muted small">Tip for phones: open the address, then "Add to Home screen".</p>
  <h2>Office name</h2>
  <form method="post" class="row-form"><?= csrf_field() ?><input name="office" value="<?= h($office) ?>"><button class="btn line">Save</button></form>
  <h2>Version</h2>
  <p>GG Office <?= GGO_VERSION ?><?= $latest !== '' && !$update ? ' (up to date)' : '' ?> · <a href="<?= u('/settings?check=1') ?>">Check for updates</a></p>
</section>
<section class="card">
  <h2>Backup</h2>
  <p>A copy of all office data is saved on the server every day; the last 30 days are kept. The hosting company's own backups also include it.</p>
  <p>Now and then, download a copy and keep it on a pen drive or Google Drive.</p>
  <p><a class="btn" href="<?= u('/settings/backup') ?>">Download a backup now</a></p>
  <?php if ($backups): ?><details><summary>Copies on the server (<?= count($backups) ?>)</summary><ul class="addr"><?php foreach ($backups as $b): ?><li><a href="<?= u('/settings/backup?f=' . rawurlencode($b)) ?>"><?= h($b) ?></a></li><?php endforeach; ?></ul></details><?php endif; ?>
  <h2>Restore from a backup</h2>
  <p class="muted small">Replaces ALL current data with the backup file (from this website or the office-computer version). What you replace is saved first.</p>
  <form method="post" action="<?= u('/settings/restore') ?>" enctype="multipart/form-data" class="stack">
    <?= csrf_field() ?>
    <input type="file" name="file" accept=".json" required>
    <label>Type RESTORE to confirm<input name="confirm" autocomplete="off" required></label>
    <button class="btn line danger">Restore</button>
  </form>
</section>
</div>
