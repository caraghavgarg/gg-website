<?php if (!defined('GGO')) { exit; } ?>
<h1>My account</h1>
<div class="card narrow">
  <p><b><?= h($me['name']) ?></b> · username <code><?= h($me['username']) ?></code> · <?= h($me['role']) ?></p>
  <h2>Change password</h2>
  <form method="post" class="stack">
    <?= csrf_field() ?>
    <label>Current password<input type="password" name="old" required autocomplete="current-password"></label>
    <label>New password <small>(at least 10 characters)</small><input type="password" name="pass" required minlength="10" autocomplete="new-password"></label>
    <label>New password again<input type="password" name="pass2" required minlength="10" autocomplete="new-password"></label>
    <button class="btn">Change password</button>
  </form>
</div>
