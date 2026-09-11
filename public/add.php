<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $hostname = trim((string) ($_POST['hostname'] ?? ''));
    $ip = normalize_ip(trim((string) ($_POST['ip'] ?? '')));
    $mac = normalize_mac(trim((string) ($_POST['mac'] ?? '')));

    if ($hostname === '' || $ip === null || $mac === null) {
        $error = 'Hostname, a valid IP, and a valid MAC address are required.';
    } else {
        try {
            computer_create($hostname, $ip, $mac);
            flash_set('success', "Computer {$hostname} created.");
            redirect('/index.php');
        } catch (PDOException $e) {
            $error = 'IP or MAC address already exists.';
        }
    }
}

render_header('Add device');
?>
<h1 class="h3 mb-3">New device</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="mw-form">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="hostname">Hostname</label>
        <input class="form-control" type="text" name="hostname" id="hostname" required value="<?= e($_POST['hostname'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="ip">IP</label>
        <input class="form-control" type="text" name="ip" id="ip" required value="<?= e($_POST['ip'] ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="mac">MAC address</label>
        <input class="form-control" type="text" name="mac" id="mac" required placeholder="AA:BB:CC:DD:EE:FF" value="<?= e($_POST['mac'] ?? '') ?>">
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="/index.php" class="btn btn-outline-secondary">Back</a>
    </div>
</form>
<?php
render_footer();
