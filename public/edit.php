<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$computer = computer_find($id);
if ($computer === null) {
    flash_set('danger', 'Computer not found.');
    redirect('/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $parsed = computer_parse_input($_POST);

    if (!$parsed['ok']) {
        $error = $parsed['error'];
        $computer = array_merge($computer, [
            'hostname' => trim((string) ($_POST['hostname'] ?? '')),
            'ip' => trim((string) ($_POST['ip'] ?? '')),
            'mac' => trim((string) ($_POST['mac'] ?? '')),
        ]);
    } else {
        try {
            computer_update($id, $parsed['hostname'], $parsed['ip'], $parsed['mac']);
            flash_set('success', "Computer {$parsed['hostname']} updated.");
            redirect('/edit.php?id=' . $id);
        } catch (PDOException $e) {
            $error = 'IP or MAC address already exists.';
            $computer = array_merge($computer, [
                'hostname' => trim((string) ($_POST['hostname'] ?? '')),
                'ip' => trim((string) ($_POST['ip'] ?? '')),
                'mac' => trim((string) ($_POST['mac'] ?? '')),
            ]);
        }
    }
}

$public = computer_public($computer);

render_header('Edit device');
?>
<h1 class="h3 mb-3 d-inline-flex align-items-center gap-2"><?= icon('pencil') ?> Edit device</h1>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2"><?= icon('circle-alert') ?> <span><?= e($error) ?></span></div>
<?php endif; ?>

<form method="post" class="mw-form">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="hostname">Hostname</label>
        <input class="form-control" type="text" name="hostname" id="hostname" required value="<?= e($public['hostname']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="ip">IP <span class="text-body-secondary fw-normal">(optional)</span></label>
        <input class="form-control" type="text" name="ip" id="ip" value="<?= e($public['ip']) ?>" placeholder="Leave empty to resolve from hostname">
        <div class="form-text">If empty, ping will try to resolve the hostname via DNS.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="mac">MAC address <span class="text-body-secondary fw-normal">(optional)</span></label>
        <input class="form-control" type="text" name="mac" id="mac" value="<?= e($public['mac']) ?>" placeholder="AA:BB:CC:DD:EE:FF">
        <div class="form-text">If empty, Wake-on-LAN is disabled (ping only).</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1"><?= icon('save') ?> Save</button>
        <a href="<?= e(url('/index.php')) ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><?= icon('arrow-left') ?> Back</a>
        <a href="<?= e(url('/delete.php?id=' . (int) $computer['id'])) ?>" class="btn btn-outline-danger ms-auto d-inline-flex align-items-center gap-1"><?= icon('trash-2') ?> Delete</a>
    </div>
</form>
<?php
render_footer();
