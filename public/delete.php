<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$computer = computer_find($id);
if ($computer === null) {
    flash_set('danger', 'Computer not found.');
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    computer_delete($id);
    flash_set('success', 'Computer deleted.');
    redirect('/index.php');
}

render_header('Delete device');
?>
<h1 class="h3 mb-3">Delete device</h1>

<div class="alert alert-danger">
    Delete <strong><?= e($computer['hostname']) ?></strong> (<?= e($computer['ip']) ?>)?
</div>

<form method="post" class="d-flex gap-2">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger">Confirm delete</button>
    <a href="<?= e(url('/edit.php?id=' . (int) $computer['id'])) ?>" class="btn btn-outline-secondary">Cancel</a>
</form>
<?php
render_footer();
