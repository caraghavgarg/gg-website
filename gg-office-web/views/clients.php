<?php if (!defined('GGO')) { exit; } ?>
<div class="head-row"><h1>Clients <small class="muted"><?= count($list) ?> shown</small></h1>
  <div class="btns"><a class="btn line" href="<?= u('/clients/import') ?>">Import from Excel</a><a class="btn line" href="<?= u('/clients/export.csv') ?>">Export</a><a class="btn" href="<?= u('/clients/new') ?>">+ Add client</a></div></div>
<form class="filters" method="get" data-autosubmit>
  <input type="search" name="q" value="<?= h($qq) ?>" placeholder="Search name, PAN, GSTIN, file no, phone" autofocus>
  <select name="type"><option value="">All types</option><?php foreach (CLIENT_TYPES as $t): ?><option<?= sel($t, $typ) ?>><?= h($t) ?></option><?php endforeach; ?></select>
  <label class="check"><input type="checkbox" name="show" value="all"<?= chk($show === 'all') ?>> Include inactive</label>
  <noscript><button class="btn sm">Filter</button></noscript>
</form>
<?php if ($list): ?>
<div class="card tbl"><table>
<thead><tr><th>Client</th><th>File</th><th>Type</th><th>PAN</th><th>GSTIN</th><th>Phone</th><th class="r">Open work</th></tr></thead>
<tbody><?php foreach ($list as $c): ?><tr class="<?= empty($c['active']) ? 'dim' : '' ?>">
  <td><a href="<?= u('/clients/' . $c['id']) ?>"><b><?= h($c['name']) ?></b></a><?= $c['group'] !== '' ? '<br><small class="muted">' . h($c['group']) . '</small>' : '' ?></td>
  <td><?= h($c['file_no']) ?></td><td><?= h($c['type']) ?></td><td><code><?= h($c['pan']) ?></code></td><td><code><?= h($c['gstin']) ?></code></td><td><?= h($c['phone']) ?></td>
  <td class="r"><?php if (!empty($open[$c['id']])): ?><a href="<?= u('/work?who=all&client=' . $c['id']) ?>"><?= $open[$c['id']] ?></a><?php endif; ?></td></tr><?php endforeach; ?></tbody>
</table></div>
<?php else: ?>
<div class="card empty-big"><?php if ($total): ?>No client matches this search.<?php else: ?><h2>No clients yet</h2><p>Add clients one by one, or import your client list from Excel in one go.</p><p><a class="btn" href="<?= u('/clients/import') ?>">Import from Excel</a> <a class="btn line" href="<?= u('/clients/new') ?>">Add a client</a></p><?php endif; ?></div>
<?php endif; ?>
