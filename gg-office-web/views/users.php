<?php if (!defined('GGO')) { exit; } ?>
<div class="head-row"><h1>Staff &amp; logins</h1><a class="btn" href="<?= u('/users/new') ?>">+ Add person</a></div>
<p class="muted">Partners and the administrator see everyone's work, assign work, approve leave and can delete records. Staff see all clients, update work and notices, and keep their own attendance and work log.</p>
<div class="card tbl"><table>
<thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr></thead>
<tbody><?php foreach ($users as $x): ?><tr class="<?= empty($x['active']) ? 'dim' : '' ?>"><td><b><?= h($x['name']) ?></b><?= !empty($online[$x['id']]) ? ' <span class="pill st-filed-done">signed in</span>' : '' ?></td><td><code><?= h($x['username']) ?></code></td><td><?= h($x['role']) ?></td><td><?= empty($x['active']) ? 'Left / disabled' : 'Active' ?></td><td class="r"><a href="<?= u('/users/' . $x['id']) ?>">Edit</a></td></tr><?php endforeach; ?></tbody>
</table></div>
