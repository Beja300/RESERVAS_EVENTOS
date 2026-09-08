</main>
<script src="<?= e(js_url()) ?>"></script>
<?php if (!empty($pageJs) && is_array($pageJs)): ?>
  <?php foreach ($pageJs as $viewJs): ?>
    <script src="<?= e(js_url($viewJs)) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
<script>document.addEventListener('DOMContentLoaded', App.init);</script>
</body>
</html>
