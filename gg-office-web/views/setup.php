<?php if (!defined('GGO')) { exit; } ?>
<div class="auth">
  <div class="auth-mark">GG</div>
  <h1>Set up GG Office</h1>
  <p class="muted">This is the first start. Create the administrator login. You will add partners and staff on the next page.</p>
  <form method="post" class="stack">
    <label>Office name<input name="office" value="<?= h($v_office ?? 'GG Associates') ?>"></label>
    <label>Your name<input name="name" value="<?= h($v_name ?? '') ?>" required autofocus></label>
    <label>Username <small>(used to sign in, no spaces)</small><input name="username" value="<?= h($v_user ?? '') ?>" required autocapitalize="none" autocomplete="username"></label>
    <label>Password <small>(at least 10 characters)</small><input type="password" name="pass" required minlength="10" autocomplete="new-password"></label>
    <label>Type the password again<input type="password" name="pass2" required minlength="10" autocomplete="new-password"></label>
    <button class="btn">Create administrator</button>
  </form>
</div>
