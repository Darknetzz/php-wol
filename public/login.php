<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/settings.php';
require_once __DIR__ . '/../src/auth.php';

if (!auth_required()) {
    redirect('/index.php');
}

if (auth_check()) {
    redirect('/index.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = (string) ($_POST['password'] ?? '');
    if (auth_login($password)) {
        redirect('/index.php');
    }
    $error = 'Incorrect password.';
}

render_header('Login');
?>
<h1 class="h3 mb-3">Login</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" class="mw-form">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" name="password" id="password" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary">Sign in</button>
</form>
<?php
render_footer();
