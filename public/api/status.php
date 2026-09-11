<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
$computer = computer_find($id);
if ($computer === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$settings = settings_get();
$storedIp = $computer['ip'] !== null && $computer['ip'] !== '' ? (string) $computer['ip'] : null;
$target = resolve_ping_target($storedIp, (string) $computer['hostname']);

if ($target === null) {
    echo json_encode([
        'id' => $id,
        'online' => false,
        'label' => 'No IP',
        'detail' => 'No IP configured and hostname could not be resolved.',
        'ip' => '',
        'resolved' => false,
        'ping' => false,
    ]);
    exit;
}

$result = ping_host($target, !empty($settings['VerbosePing']));

echo json_encode([
    'id' => $id,
    'online' => $result['online'],
    'label' => $result['label'],
    'detail' => $result['detail'],
    'ip' => $target,
    'resolved' => $storedIp === null,
    'ping' => true,
]);
