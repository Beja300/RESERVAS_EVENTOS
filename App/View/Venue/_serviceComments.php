<?php if (!empty($comments)): ?>
  <?php foreach ($comments as $c): ?>
    <div class="comment-item">
      <span class="c-author"><?= e($c['tbrolename']) ?></span>
      <span class="rating-stars"><?= str_repeat('&#9733;', (int) $c['tbserviceratingstars']) . str_repeat('&#9734;', 5 - (int) $c['tbserviceratingstars']) ?></span>
      <?php if (!empty($c['tbserviceratingcomment'])): ?>
        <div class="c-body"><?= e($c['tbserviceratingcomment']) ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p class="muted">Aún no hay comentarios.</p>
<?php endif; ?>
