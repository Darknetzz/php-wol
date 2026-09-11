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

    $hostname = trim((string) ($_POST['hostname'] ?? ''));
    $ip = normalize_ip(trim((string) ($_POST['ip'] ?? '')));
    $mac = normalize_mac(trim((string) ($_POST['mac'] ?? '')));

    if ($hostname === '' || $ip === null || $mac === null) {
        $error = 'Hostname, a valid IP, and a valid MAC address are required.';
    } else {
        try {
            computer_update($id, $hostname, $ip, $mac);
            flash_set('success', "Computer {$hostname} updated.");
            redirect('/edit.php?id=' . $id);
        } catch (PDOException $e) {
            $error = 'IP or MAC address already exists.';
        }
    }

    $computer = array_merge($computer, [
        'hostname' => $hostname,
        'ip' => (string) ($_POST['ip'] ?? ''),
        'mac' => (string) ($_POST['mac'] ?? ''),
    ]);
}

render_header('Edit device');
?>
<h1 class="h3 mb-3">Edit device</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="mw-form">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="hostname">Hostname</label>
        <input class="form-control" type="text" name="hostname" id="hostname" required value="<?= e($computer['hostname']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="ip">IP</label>
        <input class="form-control" type="text" name="ip" id="ip" required value="<?= e($computer['ip']) ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="mac">MAC address</label>
        <input class="form-control" type="text" name="mac" id="mac" required value="<?= e($computer['mac']) ?>">
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="<?= e(url('/index.php')) ?>" class="btn btn-outline-secondary">Back</a>
        <a href="<?= e(url('/delete.php?id=' . (int) $computer['id'])) ?>" class="btn btn-outline-danger ms-auto">Delete</a>
    </div>
</form>
<?php
render_footer();
