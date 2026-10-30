<?php

/** @var array $user */
?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

    <body>
        <!--
    Data  comes from ProfileController::index()
    Inside view(), extract($data) creates $user

    Session user_id → User model → Database row
    → Controller passes it to view → $user['name']
        -->
        <h1> <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>'s Profile</h1>
    </body>
</body>

</html>