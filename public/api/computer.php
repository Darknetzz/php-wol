<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $id = (int) ($_GET['id'] ?? 0);
    $computer = computer_find($id);
    if ($computer === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Not found']);
        exit;
    }

    echo json_encode(['ok' => true, 'computer' => computer_public($computer)]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

verify_csrf();

$action = (string) ($_POST['action'] ?? 'save');
$id = (int) ($_POST['id'] ?? 0);

if ($action === 'delete') {
    if ($id <= 0 || computer_find($id) === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Not found']);
        exit;
    }
    computer_delete($id);
    echo json_encode(['ok' => true, 'message' => 'Computer deleted.', 'id' => $id]);
    exit;
}

$parsed = computer_parse_input($_POST);
if (!$parsed['ok']) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $parsed['error']]);
    exit;
}

try {
    if ($id > 0) {
        if (computer_find($id) === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'message' => 'Not found']);
            exit;
        }
        computer_update($id, $parsed['hostname'], $parsed['ip'], $parsed['mac']);
        $message = "Computer {$parsed['hostname']} updated.";
    } else {
        $id = computer_create($parsed['hostname'], $parsed['ip'], $parsed['mac']);
        $message = "Computer {$parsed['hostname']} created.";
    }
} catch (PDOException $e) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'message' => 'IP or MAC address already exists.']);
    exit;
}

$computer = computer_find($id);
echo json_encode([
    'ok' => true,
    'message' => $message,
    'computer' => computer_public($computer ?? [
        'id' => $id,
        'hostname' => $parsed['hostname'],
        'ip' => $parsed['ip'] ?? '',
        'mac' => $parsed['mac'] ?? '',
    ]),
]);
