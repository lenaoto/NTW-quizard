<?php
session_start();
require 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$success = false;
$error = null;
$sentData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if ($name === '' || $email === '' || $message === '') {
    $error = 'All fields are required.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
  } else {
    try {
      $stmt = $pdo->prepare(
        "INSERT INTO contact_messages (name, email, message)
         VALUES (?, ?, ?)"
      );
      $stmt->execute([$name, $email, $message]);

      $success = true;
      $sentData = [
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'id' => (int)$pdo->lastInsertId()
      ];
    } catch (Throwable $e) {
      $error = 'Failed to save message.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact – Quizzard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body { background-color: #f9f7fd; }
    .section-title { color: #a259ff; }
    .card { background: #f3edfc; border: 1px solid #e0d4f7; }
    .btn-purple {
      background-color: #a259ff;
      color: white;
    }
    .btn-purple:hover {
      background-color: #843ee6;
      color: white;
    }
    .preview-box {
      background: white;
      border: 1px solid #e0d4f7;
      border-radius: 10px;
      padding: 12px;
      white-space: pre-wrap;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Quizard</a>
    <div class="d-flex gap-2">
      <a href="index.php"  class="btn btn-outline-secondary me-2">Početna</a>
        <a href="about.php" class="btn btn-outline-secondary me-2">O nama</a>
        <a href="profile.php" class="btn btn-outline-secondary me-2">Profil</a>
        <a href="logout.php" class="btn btn-outline-danger">Odjava</a>
    </div>
  </div>
</nav>

<div class="container py-5">
  <h1 class="mb-4 section-title text-center">Kontaktirajte nas</h1>

  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card p-4">

        <?php if ($success): ?>
          <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill"></i>
            Vaša poruka je uspješno poslana.
          </div>

          <div class="card p-3 mb-3">
            <h5 class="mb-3">
              <i class="bi bi-envelope-check-fill me-2"></i>
              Pošalji pregled poruke
            </h5>

            <p class="mb-1"><strong>Message ID:</strong> <?= (int)$sentData['id'] ?></p>
            <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($sentData['name']) ?></p>
            <p class="mb-3"><strong>Email:</strong> <?= htmlspecialchars($sentData['email']) ?></p>

            <div class="mt-2">
              <strong>Poruka:</strong>
              <div class="preview-box mt-2"><?= htmlspecialchars($sentData['message']) ?></div>
            </div>
          </div>

          <a href="contact.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-repeat me-1"></i>
            Pošalji još jednu poruku
          </a>

        <?php else: ?>

          <?php if ($error): ?>
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Ime</label>
              <input type="text" name="name" class="form-control" required
                     value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Email adresa</label>
              <input type="email" name="email" class="form-control" required
                     value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="mb-3">
              <label class="form-label">Poruka</label>
              <textarea name="message" rows="5" class="form-control" required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
            </div>

            <button type="submit" class="btn btn-purple">
              <i class="bi bi-send-fill me-1"></i>
              Pošalji poruku 
            </button>
          </form>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

</body>
</html>
