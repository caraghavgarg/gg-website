<?php if (!defined('GGO')) { exit; } ?>
<h1>Import clients from Excel</h1>
<div class="card narrow">
  <ol class="steps">
    <li>Open your client list in Excel. The first row must have column headings. Only <b>Name</b> is required; GG Office also reads: <?= h(implode(', ', CLIENT_COLS)) ?>.</li>
    <li>In Excel choose <b>File → Save As → CSV UTF-8 (Comma delimited)</b>.</li>
    <li>Choose that file below and click Import. Clients already in the list (same PAN, or same name when there is no PAN) are skipped, so importing twice is safe.</li>
  </ol>
  <p><a href="<?= u('/clients/template.csv') ?>">Download a sample file</a></p>
  <form method="post" enctype="multipart/form-data" class="stack">
    <?= csrf_field() ?>
    <input type="file" name="file" accept=".csv,text/csv" required>
    <button class="btn">Import</button>
  </form>
</div>
