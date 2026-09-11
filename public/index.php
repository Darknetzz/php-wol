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
        <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" id="btn-new-device">
            <?= icon('plus') ?>
            New device
        </button>
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
                <th class="sortable" data-sort="hostname" data-type="text" scope="col" role="button" tabindex="0">
                    <?= icon('monitor') ?> Hostname <span class="sort-indicator" aria-hidden="true"></span>
                </th>
                <th class="sortable" data-sort="ip" data-type="text" scope="col" role="button" tabindex="0">
                    <?= icon('network') ?> IP <span class="sort-indicator" aria-hidden="true"></span>
                </th>
                <th class="sortable" data-sort="mac" data-type="text" scope="col" role="button" tabindex="0">
                    <?= icon('fingerprint') ?> MAC <span class="sort-indicator" aria-hidden="true"></span>
                </th>
                <th class="sortable" data-sort="status" data-type="text" scope="col" role="button" tabindex="0">
                    <?= icon('radio') ?> Status <span class="sort-indicator" aria-hidden="true"></span>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if ($computers === []): ?>
            <tr class="empty-row">
                <td colspan="5" class="text-body-secondary">No devices yet. Click <strong>New device</strong> to add one.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($computers as $row): ?>
                <?php
                $public = computer_public($row);
                $hasMac = $public['mac'] !== '';
                ?>
                <tr
                    data-id="<?= (int) $public['id'] ?>"
                    data-hostname="<?= e($public['hostname']) ?>"
                    data-ip="<?= e($public['ip']) ?>"
                    data-mac="<?= e($public['mac']) ?>"
                >
                    <td>
                        <button type="button" class="btn btn-link p-0 host-edit-link" data-id="<?= (int) $public['id'] ?>">
                            <?= e($public['hostname']) ?>
                        </button>
                    </td>
                    <td class="host-ip"><?= $public['ip'] !== '' ? e($public['ip']) : '<span class="text-body-secondary">—</span>' ?></td>
                    <td class="host-mac"><?= $public['mac'] !== '' ? '<code>' . e($public['mac']) . '</code>' : '<span class="text-body-secondary">—</span>' ?></td>
                    <td id="status-<?= (int) $public['id'] ?>" class="status-cell" data-status-label="">…</td>
                    <td class="text-end">
                        <button
                            type="button"
                            class="btn btn-sm btn-secondary btn-wake d-inline-flex align-items-center gap-1"
                            data-id="<?= (int) $public['id'] ?>"
                            <?= $hasMac ? '' : 'disabled title="No MAC address — Wake-on-LAN disabled"' ?>
                        >
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

<div class="modal fade" id="host-modal" tabindex="-1" aria-labelledby="host-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" id="host-form">
            <div class="modal-header">
                <h2 class="modal-title h5 d-inline-flex align-items-center gap-2" id="host-modal-title">
                    <?= icon('pencil') ?>
                    <span id="host-modal-heading">Edit device</span>
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="host-modal-error" class="alert alert-danger d-none d-flex align-items-center gap-2" role="alert">
                    <?= icon('circle-alert') ?>
                    <span></span>
                </div>
                <input type="hidden" name="id" id="host-id" value="">
                <div class="mb-3">
                    <label class="form-label" for="host-hostname">Hostname</label>
                    <input class="form-control" type="text" name="hostname" id="host-hostname" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="host-ip">IP <span class="text-body-secondary fw-normal">(optional)</span></label>
                    <input class="form-control" type="text" name="ip" id="host-ip" placeholder="Leave empty to resolve from hostname" autocomplete="off">
                    <div class="form-text">If empty, ping will try to resolve the hostname via DNS.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="host-mac">MAC address <span class="text-body-secondary fw-normal">(optional)</span></label>
                    <input class="form-control" type="text" name="mac" id="host-mac" placeholder="AA:BB:CC:DD:EE:FF" autocomplete="off">
                    <div class="form-text">If empty, Wake-on-LAN is disabled (ping only).</div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-danger d-inline-flex align-items-center gap-1" id="host-delete-btn">
                    <?= icon('trash-2') ?> Delete
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <?= icon('save') ?> Save
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
render_footer(true);
