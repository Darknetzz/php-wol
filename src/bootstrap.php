<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/wol.php';

auth_require();
