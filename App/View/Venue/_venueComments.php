<?php if (!empty($venueComments)): ?>
  <?php foreach ($venueComments as $c): ?>
    <div class="comment-item">
      <span class="c-author"><?= e($c['tbrolename']) ?></span>
      <span class="rating-stars"><?= str_repeat('&#9733;', (int) $c['tbvenueratingstars']) . str_repeat('&#9734;', 5 - (int) $c['tbvenueratingstars']) ?></span>
      <?php if (!empty($c['tbvenueratingcomment'])): ?>
        <div class="c-body"><?= e($c['tbvenueratingcomment']) ?></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p class="muted">Aún no hay comentarios.</p>
<?php endif; ?>
