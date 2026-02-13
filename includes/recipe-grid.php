<div class="recipe-grid">

<?php if (!empty($recipes)): ?>

  <?php foreach ($recipes as $recipe): ?>
    <div class="tile">
      <?= htmlspecialchars($recipe['title']) ?>
    </div>
  <?php endforeach; ?>

<?php else: ?>

  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>
  <div class="tile">Recipe Placeholder</div>

<?php endif; ?>

</div>