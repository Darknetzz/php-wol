<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$error = null;
$values = [
    'hostname' => '',
    'ip' => '',
    'mac' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $values = [
        'hostname' => trim((string) ($_POST['hostname'] ?? '')),
        'ip' => trim((string) ($_POST['ip'] ?? '')),
        'mac' => trim((string) ($_POST['mac'] ?? '')),
    ];
    $parsed = computer_parse_input($_POST);

    if (!$parsed['ok']) {
        $error = $parsed['error'];
    } else {
        try {
            computer_create($parsed['hostname'], $parsed['ip'], $parsed['mac']);
            flash_set('success', "Computer {$parsed['hostname']} created.");
            redirect('/index.php');
        } catch (PDOException $e) {
            $error = 'IP or MAC address already exists.';
        }
    }
}

render_header('Add device');
?>
<h1 class="h3 mb-3 d-inline-flex align-items-center gap-2"><?= icon('plus-circle') ?> New device</h1>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2"><?= icon('circle-alert') ?> <span><?= e($error) ?></span></div>
<?php endif; ?>

<form method="post" class="mw-form">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="hostname">Hostname</label>
        <input class="form-control" type="text" name="hostname" id="hostname" required value="<?= e($values['hostname']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="ip">IP <span class="text-body-secondary fw-normal">(optional)</span></label>
        <input class="form-control" type="text" name="ip" id="ip" value="<?= e($values['ip']) ?>" placeholder="Leave empty to resolve from hostname">
        <div class="form-text">If empty, ping will try to resolve the hostname via DNS.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="mac">MAC address <span class="text-body-secondary fw-normal">(optional)</span></label>
        <input class="form-control" type="text" name="mac" id="mac" placeholder="AA:BB:CC:DD:EE:FF" value="<?= e($values['mac']) ?>">
        <div class="form-text">If empty, Wake-on-LAN is disabled (ping only).</div>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1"><?= icon('save') ?> Save</button>
        <a href="<?= e(url('/index.php')) ?>" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1"><?= icon('arrow-left') ?> Back</a>
    </div>
</form>
<?php
render_footer();
