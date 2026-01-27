<?php

function output($text) {
    if (php_sapi_name() === 'cli') {
        echo $text . PHP_EOL;
    } else {
        echo "<pre>" . htmlspecialchars($text) . "</pre>";
    }
}

if (
    (php_sapi_name() === 'cli' && isset($argv[1])) ||
    (php_sapi_name() !== 'cli' && isset($_POST['password']))
) {
    $password = php_sapi_name() === 'cli' ? $argv[1] : $_POST['password'];

    if (trim($password) === '') {
        output('❌ Lozinka ne smije biti prazna');
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    output("✔ Plain password:");
    output($password);

    output("\n✔ Bcrypt hash:");
    output($hash);

    output("\n✔ SQL primjer:");
    output("INSERT INTO users (username, password, role) VALUES ('admin', '{$hash}', 'admin');");
    exit;
}

if (php_sapi_name() !== 'cli') :
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <title>Make bcrypt hash</title>
    <style>
        body { font-family: system-ui, Arial; background:#f6f6f6; padding:40px; }
        .box { background:white; padding:20px; max-width:520px; margin:auto; border-radius:10px; }
        input, button { width:100%; padding:10px; font-size:16px; margin-top:10px; }
        button { background:#a259ff; color:white; border:none; border-radius:6px; cursor:pointer; }
        button:hover { background:#843ee6; }
    </style>
</head>
<body>
<div class="box">
    <h2>Generate bcrypt hash</h2>
    <form method="post">
        <label>Unesi lozinku:</label>
        <input type="text" name="password" required>
        <button type="submit">Generate hash</button>
    </form>
    <p style="margin-top:15px;color:#666;font-size:14px;">
        Hash koristi <code>password_hash()</code> (bcrypt).
    </p>
</div>
</body>
</html>
<?php
endif;
