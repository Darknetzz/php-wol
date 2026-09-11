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
<h1 class="h3 mb-3 d-inline-flex align-items-center gap-2"><?= icon('trash-2') ?> Delete device</h1>

<div class="alert alert-danger d-flex align-items-center gap-2">
    <?= icon('triangle-alert') ?>
    <span>Delete <strong><?= e($computer['hostname']) ?></strong><?php
        $ip = $computer['ip'] !== null && $computer['ip'] !== '' ? (string) $computer['ip'] : '';
        echo $ip !== '' ? ' (' . e($ip) . ')' : '';
    ?>?</span>
</div>

<form method="post" class="d-flex gap-2">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-danger d-inline-flex align-items-center gap-1"><?= icon('trash-2') ?> Confirm delete</button>
    <a href="<?= e(url('/edit.php?id=' . (int) $computer['id'])) ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><?= icon('x') ?> Cancel</a>
</form>
<?php
render_footer();
