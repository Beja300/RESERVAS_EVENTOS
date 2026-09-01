<?php require_once __DIR__ . '/../_header.php'; ?>

<div class="page-head">
  <div>
    <h1>Mis notificaciones</h1>
    <p class="muted"><?= (int) $unreadCount ?> sin leer</p>
  </div>
  <?php if ($unreadCount > 0): ?>
    <form method="post" action="<?= e(base_url('notification', 'markAllAsRead')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-outline" type="submit">Marcar todas como leídas</button>
    </form>
  <?php endif; ?>
</div>

<?php if (empty($notifications)): ?>
  <div class="card empty">
    <span class="emoji">&#128276;</span>
    No tienes notificaciones por ahora.
  </div>
<?php else: ?>
  <div class="list">
    <?php foreach ($notifications as $n): ?>
      <div class="card list-item <?= !$n->getIsRead() ? 'unread' : '' ?>">
        <div>
          <div class="title">
            <?= !$n->getIsRead() ? '<span class="dot"></span>' : '' ?>
            <?= e($n->getMessageNotification()) ?>
          </div>
          <div class="desc"><?= e(date('d/m/Y H:i', strtotime($n->getDateNotification()))) ?></div>
        </div>
        <div class="actions">
        <?php if (!empty($n->getLink())): ?>
          <a class="btn btn-sm btn-outline" href="<?= e(base_url('notification', 'open', ['id' => $n->getIdNotification()])) ?>">Ver</a>
        <?php endif; ?>
        <?php if (!$n->getIsRead()): ?>
          <form method="post" action="<?= e(base_url('notification', 'markAsRead')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $n->getIdNotification() ?>">
            <button class="btn btn-sm btn-outline" type="submit">Marcar leído</button>
          </form>
        <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../_footer.php'; ?>
