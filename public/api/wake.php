<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

$id = (int) ($_GET['id'] ?? 0);
$computer = computer_find($id);
if ($computer === null) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'message' => 'Not found']);
    exit;
}

$result = wol_send($computer['mac']);
http_response_code($result['ok'] ? 200 : 500);
echo json_encode($result);
