<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function wol_send(string $mac, ?string $broadcast = null): array
{
    $normalized = normalize_mac($mac);
    if ($normalized === null) {
        return ['ok' => false, 'message' => 'Invalid MAC address.'];
    }

    $broadcast ??= env('WOL_BROADCAST', '255.255.255.255');
    $hex = str_replace(':', '', $normalized);
    $packet = str_repeat(chr(0xFF), 6) . str_repeat(hex2bin($hex), 16);

    $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($socket === false) {
        return ['ok' => false, 'message' => 'Could not create UDP socket.'];
    }

    socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
    $sent = @socket_sendto($socket, $packet, strlen($packet), 0, $broadcast, 9);
    socket_close($socket);

    if ($sent === false) {
        return ['ok' => false, 'message' => 'Failed to send magic packet.'];
    }

    return [
        'ok' => true,
        'message' => "Magic packet sent to {$normalized} via {$broadcast}:9",
    ];
}

function ping_host(string $ip, bool $verbose = false): array
{
    if (!normalize_ip($ip)) {
        return ['online' => false, 'label' => 'Error', 'detail' => 'Invalid IP'];
    }

    $cmd = sprintf('ping -c 1 -W 1 %s 2>&1', escapeshellarg($ip));
    $output = [];
    $code = 0;
    exec($cmd, $output, $code);
    $text = implode("\n", $output);

    $offlineMarkers = [
        '100% packet loss',
        '0 received',
        'Unreachable',
        'Network is unreachable',
        'Name or service not known',
    ];

    $online = $code === 0;
    foreach ($offlineMarkers as $marker) {
        if (stripos($text, $marker) !== false) {
            $online = false;
            break;
        }
    }

    if ($text === '' && $code !== 0) {
        return [
            'online' => false,
            'label' => 'Error',
            'detail' => 'Unable to ping (is iputils-ping installed / NET_RAW available?)',
        ];
    }

    if ($code !== 0 && stripos($text, 'Operation not permitted') !== false) {
        return [
            'online' => false,
            'label' => 'Error',
            'detail' => $verbose ? $text : 'Ping not permitted in container',
        ];
    }

    return [
        'online' => $online,
        'label' => $online ? 'Online' : 'Offline',
        'detail' => $verbose ? $text : '',
    ];
}
