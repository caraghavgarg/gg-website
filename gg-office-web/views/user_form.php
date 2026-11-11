<?php if (!defined('GGO')) { exit; } $x = $item; ?>
<h1><?= $x['id'] ? 'Edit ' . h($x['name']) : 'Add a person' ?></h1>
<form method="post" class="card narrow stack">
  <?= csrf_field() ?>
  <label>Full name<input name="name" value="<?= h($x['name']) ?>" required autofocus></label>
  <label>Username <small>(for signing in, no spaces, e.g. "amit")</small><input name="username" value="<?= h($x['username']) ?>" required autocapitalize="none" autocomplete="off"></label>
  <label>Role<select name="role">
    <option value="staff"<?= sel($x['role'], 'staff') ?>>Staff: own work first, cannot delete or assign</option>
    <option value="partner"<?= sel($x['role'], 'partner') ?>>Partner: sees all, assigns work, approves leave</option>
    <option value="admin"<?= sel($x['role'], 'admin') ?>>Administrator: partner rights plus logins, settings and backup</option>
  </select></label>
  <label><?= $x['id'] ? 'New password <small>(leave empty to keep the current one)</small>' : 'First password <small>(at least 10 characters; ask them to change it)</small>' ?>
    <input type="text" name="pass" <?= $x['id'] ? '' : 'required' ?> minlength="10" autocomplete="off"></label>
  <label class="check"><input type="checkbox" name="active" value="1"<?= chk(!empty($x['active'])) ?>> Can sign in (untick when someone leaves; their history stays)</label>
  <div class="actions"><button class="btn">Save</button><a href="<?= u('/users') ?>">Cancel</a></div>
</form>
<?php if ($x['id'] && (int) $x['id'] !== (int) $me['id']): ?>
<form method="post" action="<?= u('/users/' . $x['id'] . '/signout') ?>" class="danger-zone"><?= csrf_field() ?><button class="link">Sign <?= h($x['name']) ?> out on all devices</button> <small class="muted">(use this if a phone is lost)</small></form>
<?php endif; ?>
