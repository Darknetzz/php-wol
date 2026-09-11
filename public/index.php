<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (isset($_GET['darkmode'])) {
    settings_toggle('DarkTheme', $_GET['darkmode'] === '1');
    redirect('/index.php');
}

if (isset($_GET['verboseping'])) {
    settings_toggle('VerbosePing', $_GET['verboseping'] === '1');
    redirect('/index.php');
}

$settings = settings_get();
$computers = computers_all();

render_header();
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1 d-inline-flex align-items-center gap-2">
            <?= icon('cable') ?>
            Wake on LAN
        </h1>
        <p class="text-body-secondary mb-0">Monitor local devices and send magic packets.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" id="btn-update-all">
            <?= icon('refresh-cw') ?>
            Update
        </button>
        <a href="<?= e(url('/add.php')) ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
            <?= icon('plus') ?>
            New device
        </a>
    </div>
</div>

<div class="mb-3 small text-body-secondary d-flex flex-wrap align-items-center gap-2">
    <span class="d-inline-flex align-items-center gap-1"><?= icon('moon') ?> Dark mode:</span>
    <?php if (!empty($settings['DarkTheme'])): ?>
        <strong>ON</strong> · <a href="?darkmode=0">OFF</a>
    <?php else: ?>
        <a href="?darkmode=1">ON</a> · <strong>OFF</strong>
    <?php endif; ?>
    <span class="text-body-secondary">|</span>
    <span class="d-inline-flex align-items-center gap-1"><?= icon('activity') ?> Verbose ping:</span>
    <?php if (!empty($settings['VerbosePing'])): ?>
        <strong>ON</strong> · <a href="?verboseping=0">OFF</a>
    <?php else: ?>
        <a href="?verboseping=1">ON</a> · <strong>OFF</strong>
    <?php endif; ?>
</div>

<div id="wol-feedback" class="mb-3"></div>

<div class="table-responsive">
    <table class="table table-hover align-middle" id="computers-table">
        <thead>
            <tr>
                <th><?= icon('monitor') ?> Hostname</th>
                <th><?= icon('network') ?> IP</th>
                <th><?= icon('fingerprint') ?> MAC</th>
                <th><?= icon('radio') ?> Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($computers === []): ?>
            <tr>
                <td colspan="5" class="text-body-secondary">No devices yet. <a href="<?= e(url('/add.php')) ?>">Add one</a>.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($computers as $row): ?>
                <tr data-id="<?= (int) $row['id'] ?>">
                    <td><a href="<?= e(url('/edit.php?id=' . (int) $row['id'])) ?>"><?= e($row['hostname']) ?></a></td>
                    <td><?= e($row['ip']) ?></td>
                    <td><code><?= e($row['mac']) ?></code></td>
                    <td id="status-<?= (int) $row['id'] ?>" class="status-cell">…</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-secondary btn-wake d-inline-flex align-items-center gap-1" data-id="<?= (int) $row['id'] ?>">
                            <?= icon('power') ?>
                            Wake
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
render_footer(true);
