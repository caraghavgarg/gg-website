<?php if (!defined('GGO')) { exit; } ?>
<div class="auth">
  <div class="auth-mark">GG</div>
  <h1><?= h($office) ?> Office</h1>
  <p class="muted">Sign in with the username and password the office gave you.</p>
  <form method="post" action="<?= u('/login') ?>" class="stack">
    <input type="hidden" name="next" value="<?= h($next) ?>">
    <label>Username<input name="username" value="<?= h($v_user) ?>" required autofocus autocapitalize="none" autocomplete="username"></label>
    <label>Password<input type="password" name="pass" required autocomplete="current-password"></label>
    <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in on this device for 14 days</label>
    <button class="btn">Sign in</button>
  </form>
  <p class="muted small">Forgot your password? Ask the office administrator to set a new one. Do not tick "keep me signed in" on a shared or public computer.</p>
</div>
