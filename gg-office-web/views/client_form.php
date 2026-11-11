<?php if (!defined('GGO')) { exit; } ?>
<h1><?= $c['id'] ? 'Edit ' . h($c['name']) : 'Add a client' ?></h1>
<form method="post" class="card form-grid">
  <?= csrf_field() ?>
  <label class="span2">Client name<input name="name" value="<?= h($c['name']) ?>" required autofocus></label>
  <label>File no.<input name="file_no" value="<?= h($c['file_no']) ?>"></label>
  <label>Type<select name="type"><?php foreach (CLIENT_TYPES as $t): ?><option<?= sel($t, $c['type']) ?>><?= h($t) ?></option><?php endforeach; ?></select></label>
  <label>PAN<input name="pan" value="<?= h($c['pan']) ?>" maxlength="10" class="up" pattern="[A-Za-z]{5}[0-9]{4}[A-Za-z]" title="10 characters, e.g. ABCDE1234F"></label>
  <label>GSTIN<input name="gstin" value="<?= h($c['gstin']) ?>" maxlength="15" class="up"></label>
  <label>TAN<input name="tan" value="<?= h($c['tan']) ?>" maxlength="10" class="up"></label>
  <label>CIN / LLPIN<input name="cin" value="<?= h($c['cin']) ?>" maxlength="21" class="up"></label>
  <label>Phone<input name="phone" value="<?= h($c['phone']) ?>" inputmode="tel"></label>
  <label>Email<input name="email" value="<?= h($c['email']) ?>" type="email"></label>
  <label>Group / family<input name="group" value="<?= h($c['group']) ?>" placeholder="e.g. Bansal family"></label>
  <label>Usually handled by<select name="manager"><option value="0">—</option><?php foreach (active_users() as $x): ?><option value="<?= $x['id'] ?>"<?= sel($x['id'], $c['manager']) ?>><?= h($x['name']) ?></option><?php endforeach; ?></select></label>
  <label class="span2">Address<textarea name="address" rows="2"><?= h($c['address']) ?></textarea></label>
  <label class="span2">Notes<textarea name="notes" rows="3" placeholder="Never write passwords here."><?= h($c['notes']) ?></textarea></label>
  <label class="check span2"><input type="checkbox" name="active" value="1"<?= chk(!empty($c['active'])) ?>> Active client</label>
  <div class="actions span2"><button class="btn">Save client</button><a href="<?= u($c['id'] ? '/clients/' . $c['id'] : '/clients') ?>">Cancel</a></div>
</form>
