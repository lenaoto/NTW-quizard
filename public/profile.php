<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT username, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();


$stmt = $pdo->prepare("SELECT id, title, description, created_at FROM quizzes WHERE user_id = ?");
$stmt->execute([$userId]);
$quizzes = $stmt->fetchAll();


$stmt = $pdo->prepare("
  SELECT r.id, q.title, r.score, r.total_questions, r.created_at
  FROM results r
  JOIN quizzes q ON r.quiz_id = q.id
  WHERE r.user_id = ?
  ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$results = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - <?= htmlspecialchars($user['username']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f9f7fd; }
    .section-title { color: #a259ff; }
    .btn-purple {
      background-color: #a259ff;
      color: white;
    }
    .btn-purple:hover {
      background-color: #843ee6;
      color: white;
    }
    .card { background: #f3edfc; border: 1px solid #e0d4f7; }
    .table { background: white; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Quizard</a>
    <div class="d-flex align-items-center">
       <ul class="navbar-nav me-auto mb-2 mb-lg-0">
      <li class="nav-item">
        <a href="index.php"  class="btn btn-outline-secondary me-2">Početna</a>
        <a href="about.php" class="btn btn-outline-secondary me-2">O nama</a>
        <a href="logout.php" class="btn btn-outline-danger">Odjava</a>
        
      </li>
    </ul>
 
    </div>
  </div>
</nav>

<div class="container py-5">
  <h1 class="mb-4 section-title">Profile: <?= htmlspecialchars($user['username']) ?></h1>
  <p><strong>Korisnik od:</strong> <?= date("F j, Y", strtotime($user['created_at'])) ?></p>

  <h3 class="mt-5 mb-3 section-title">Vaši kvizovi:</h3>
  <div class="card p-4 mb-4">
  <h4 class="section-title mb-3">Generiraj kviz sa Wikipedije:</h4>

  <div class="row g-2">
    <div class="col-md-8">
      <input id="wikiQuery" class="form-control" placeholder="Upiši pojam (npr. Nikola Tesla)">
      <div id="wikiResults" class="list-group mt-2"></div>
    </div>

    <div class="col-md-4">
      <select id="wikiLang" class="form-select">
        <option value="hr" selected>Hrvatski (hr)</option>
        <option value="en">English (en)</option>
      </select>

      <button id="btnGenerate" class="btn btn-purple w-100 mt-2" disabled>
        Generiraj kviz
      </button>

      <div id="genStatus" class="small text-muted mt-2"></div>
    </div>
  </div>

  <div id="wikiPreview" class="mt-3"></div>
</div>

  <?php if (count($quizzes) > 0): ?>
    <div class="list-group mb-5">
      <?php foreach ($quizzes as $quiz): ?>
        <div class="list-group-item">
  <div class="d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-1"><?= htmlspecialchars($quiz['title']) ?></h5>
      <p class="mb-1"><?= htmlspecialchars($quiz['description']) ?></p>
      <small><?= date("F j, Y", strtotime($quiz['created_at'])) ?></small>
    </div>
    <div class="btn-group">
      <a href="edit_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-outline-primary">Uredi</a>
      <form method="POST" action="delete_quiz.php" onsubmit="return confirm('Are you sure you want to delete this quiz?');">
        <input type="hidden" name="quiz_id" value="<?= $quiz['id'] ?>">
        <button type="submit" class="btn btn-sm btn-outline-danger">Obriši</button>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-muted mb-5">Niste još izradili nijedan kviz.</p>
  <?php endif; ?>

  <h3 class="mt-5 mb-3 section-title">Vaši rezultati kvizova:</h3>
  <?php if ($results): ?>
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>Kviz</th>
          <th>Bodovi</th>
          <th>Datum</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $result): ?>
          <tr>
            <td><?= htmlspecialchars($result['title']) ?></td>
            <td><?= $result['score'] ?> / <?= $result['total_questions'] ?></td>
            <td><?= date("F j, Y, H:i", strtotime($result['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p class="text-muted mb-5">Niste još završili nijedan kviz.</p>
  <?php endif; ?>

  <h3 class="mt-5 mb-3 section-title">Uredi profil</h3>
  <form method="POST" action="update_profile.php" class="mb-5 card p-4">
    <div class="mb-3">
      <label class="form-label">Ime</label>
      <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Nova lozinka (ostavite prazno ako ne želite promijeniti)</label>
      <input type="password" name="password" class="form-control">
    </div>
    <button type="submit" class="btn btn-purple">Spremi promjene</button>
  </form>
</div>
<script>
const $q = document.getElementById('wikiQuery');
const $lang = document.getElementById('wikiLang');
const $results = document.getElementById('wikiResults');
const $preview = document.getElementById('wikiPreview');
const $btn = document.getElementById('btnGenerate');
const $status = document.getElementById('genStatus');

let t = null;
let selectedTitle = null;

function esc(s) {
  return (s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
  }[m]));
}

async function doSearch() {
  clearTimeout(t);
  const val = $q.value.trim();
  if (val.length < 2) {
    $results.innerHTML = '';
    $preview.innerHTML = '';
    selectedTitle = null;
    $btn.disabled = true;
    return;
  }

  t = setTimeout(async () => {
    const res = await fetch(`api/wiki_search.php?q=${encodeURIComponent(val)}&lang=${encodeURIComponent($lang.value)}&limit=8`);
    const data = await res.json();
    const items = data.results || [];

    $results.innerHTML = items.map(r => `
      <button type="button" class="list-group-item list-group-item-action"
              data-title="${esc(r.title)}">
        <div class="fw-bold">${esc(r.title)}</div>
        <div class="small text-muted">${esc(r.desc || '')}</div>
      </button>
    `).join('');
  }, 250);
}

$q.addEventListener('input', doSearch);
$lang.addEventListener('change', () => {
  selectedTitle = null;
  $btn.disabled = true;
  $preview.innerHTML = '';
  doSearch();
});

$results.addEventListener('click', async (e) => {
  const item = e.target.closest('[data-title]');
  if (!item) return;

  selectedTitle = item.getAttribute('data-title');
  $btn.disabled = false;

  $preview.innerHTML = '<div class="text-muted">Loading preview...</div>';

  const res = await fetch(`api/wiki_summary.php?title=${encodeURIComponent(selectedTitle)}&lang=${encodeURIComponent($lang.value)}`);
  const data = await res.json();

  $preview.innerHTML = `
    <div class="d-flex gap-3 align-items-start">
      ${data.thumb ? `<img src="${data.thumb}" style="width:120px;border-radius:10px;" alt="">` : ''}
      <div>
        <div class="h6 mb-1">${esc(data.title)}</div>
        <div class="small">${esc(data.extract || '')}</div>
        ${data.pageUrl ? `<div class="mt-2"><a href="${data.pageUrl}" target="_blank" rel="noopener">Open Wikipedia</a></div>` : ''}
      </div>
    </div>
  `;
});

$btn.addEventListener('click', async () => {
  if (!selectedTitle) return;

  $btn.disabled = true;
  $status.textContent = 'Generating quiz...';

  try {
    const res = await fetch('api/generate_quiz_from_wiki.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ title: selectedTitle, lang: $lang.value })
    });

    const data = await res.json();

    if (!res.ok || !data.ok) {
      throw new Error(data.error || 'Generate failed');
    }

    $status.textContent = 'Quiz created! Reloading...';
    window.location.reload();

  } catch (err) {
    $status.textContent = 'Error: ' + err.message;
    $btn.disabled = false;
  }
});
</script>

</body>
</html>
