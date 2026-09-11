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
$result = ping_host($computer['ip'], !empty($settings['VerbosePing']));

echo json_encode([
    'id' => $id,
    'online' => $result['online'],
    'label' => $result['label'],
    'detail' => $result['detail'],
]);
