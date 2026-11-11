<?php if (!defined('GGO')) { exit; } ?>
<section class="card" id="notes">
  <h2>Notes &amp; updates</h2>
  <?php if ($comments): foreach ($comments as $cm): ?><div class="cmt"><b><?= h(uname($cm['user_id'])) ?></b> <small class="muted"><?= h($cm['at']) ?></small><p><?= nl($cm['text']) ?></p></div><?php endforeach; else: ?><p class="empty">No notes yet.</p><?php endif; ?>
  <form method="post" action="<?= u('/comment') ?>" class="stack"><?= csrf_field() ?><input type="hidden" name="kind" value="<?= $note_kind ?>"><input type="hidden" name="ref" value="<?= (int) $note_ref ?>">
    <textarea name="text" rows="2" placeholder="Add a note: called client, documents received…" required></textarea><button class="btn line sm">Add note</button></form>
</section>
