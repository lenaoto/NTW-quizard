<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$role = $stmt->fetchColumn();

if ($role !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];

    $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$newRole, $userId]);

    header("Location: admin_user_management.php");
    exit;
}

$stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upravljanje korisnicima</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-5">
  <h1 class="mb-4">Upravljanje korisnicima</h1>

  <table class="table table-bordered bg-white">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Ime</th>
        <th>Rola</th>
        <th>Korisnik od</th>
        <th>Akcije</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['username']) ?></td>
          <td><?= $user['role'] ?></td>
          <td><?= date("F j, Y", strtotime($user['created_at'])) ?></td>
          <td>
            <?php if ($user['id'] !== $_SESSION['user_id']): ?> 
              <form method="POST" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <select name="new_role" class="form-select form-select-sm" required>
                  <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Korisnik</option>
                  <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                  <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Promijeni</button>
              </form>
            <?php else: ?>
              <span class="text-muted">Vi</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

</body>
</html>
