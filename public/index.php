<?php
session_start();
require 'db.php';

$isLoggedIn = isset($_SESSION['user_id']);
$isGuest = isset($_SESSION['guest']) && $_SESSION['guest'] === true;
$isAdmin = false;

if (isset($_GET['guest']) && $_GET['guest'] == 1) {
    $_SESSION['guest'] = true;
    header("Location: index.php");
    exit;
}

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userRole = $stmt->fetchColumn();
    $isAdmin = ($userRole === 'admin');
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quizard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background-color: #f9f7fd; }

    .section-title { color: #a259ff; }

    .btn-purple {
      background-color: #a259ff;
      color: white;
      border: none;
    }
    .btn-purple:hover {
      background-color: #843ee6;
      color: white;
    }

    .btn-outline-purple {
      color: #a259ff;
      border-color: #a259ff;
    }
    .btn-outline-purple:hover {
      background-color: #a259ff;
      color: white;
    }

    .card-soft {
      background: #f3edfc;
      border: 1px solid #e0d4f7;
      border-radius: 16px;
    }

    .hero {
      background: linear-gradient(90deg, rgba(162,89,255,1) 0%, rgba(255,255,255,1) 100%);
      padding: 72px 0;
      color: white;
    }
    .hero img { max-width: 100%; height: auto; }

    .pill {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .35rem .75rem;
      border-radius: 999px;
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.22);
      font-size: .95rem;
    }

    #triviaOptions .btn { margin: .25rem; }
    .footer-space { height: 24px; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="about.php">Quizard</a>

    <div class="d-flex align-items-center gap-2">
      <?php if ($isAdmin): ?>
        <a href="admin_dashboard.php" class="btn btn-warning">Admin Panel</a>
      <?php endif; ?>

      <?php if ($isLoggedIn): ?>
        <a href="create_quiz.php" class="btn btn-purple">Kreiraj kviz</a>
        <a href="profile.php" class="btn btn-outline-secondary">Profil</a>
        <a href="logout.php" class="btn btn-outline-danger">Odjava</a>
      <?php elseif ($isGuest): ?>
        <a href="logout.php" class="btn btn-outline-danger">Izađi iz gost načina</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-outline-purple">Registracija</a>
        <a href="login.php" class="btn btn-outline-purple">Prijava</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-md-6 text-center text-md-start">
        <div class="pill mb-3">🎓 Učenje kroz kvizove</div>
        <h1 class="display-5 fw-bold">Uči pametnije,<br>ne teže.</h1>
        <p class="lead mb-4">Quizard čini učenje lakšim.<br>Počni već danas!</p>

        <?php if (!$isLoggedIn && !$isGuest): ?>
          <a href="register.php" class="btn btn-outline-light btn-lg me-2">Registriraj se besplatno</a>
          <a href="?guest=1" class="btn btn-light btn-lg">Nastavi kao gost</a>
        <?php endif; ?>
      </div>

      <div class="col-md-6 text-center">
        <img src="hero-illustration.png" alt="Hero Image">
      </div>
    </div>
  </div>
</section>

<?php if ($isLoggedIn || $isGuest): ?>
  <div class="container py-4">
    <div class="card-soft p-4">
      <h2 class="h4 fw-bold section-title mb-3">Dostupni kvizovi</h2>

      <form method="GET" class="row g-2 align-items-center mb-3">
        <div class="col-md-7">
          <input type="text"
                 name="keyword"
                 class="form-control"
                 placeholder="Pretraži po nazivu kviza ili autoru"
                 value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-outline-purple w-100">Traži</button>
        </div>
        <div class="col-md-2">
          <a href="index.php" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
      </form>

      <div class="row">
        <?php
          $query = "SELECT q.id, q.title, q.description, u.username
                    FROM quizzes q
                    LEFT JOIN users u ON q.user_id = u.id
                    WHERE 1=1";

          $params = [];

          if (isset($_GET['keyword']) && !empty(trim($_GET['keyword']))) {
              $keyword = '%' . trim($_GET['keyword']) . '%';
              $query .= " AND (q.title LIKE ? OR u.username LIKE ?)";
              $params[] = $keyword;
              $params[] = $keyword;
          }

          $stmt = $pdo->prepare($query);
          $stmt->execute($params);
          $quizzes = $stmt->fetchAll();
        ?>

        <?php if (count($quizzes) > 0): ?>
          <?php foreach ($quizzes as $quiz): ?>
            <div class="col-md-4 mb-3">
              <div class="card card-soft h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                  <h5 class="card-title mb-2"><?= htmlspecialchars($quiz['title']) ?></h5>
                  <p class="card-text flex-grow-1"><?= htmlspecialchars($quiz['description']) ?></p>

                  <p class="text-muted small mb-3">
                    <?= $quiz['username'] ? 'Autor: ' . htmlspecialchars($quiz['username']) : 'Autor: Gost ili Admin' ?>
                  </p>

                  <a href="quiz_details.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-purple mt-auto">
                    Detalji
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-center text-muted mb-0">Još nema dostupnih kvizova.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>


<div class="container py-2">
  <div class="card-soft p-4 text-center shadow-sm">
    <h3 class="h4 section-title mb-2">Trivia dana</h3>
    <p class="text-muted mb-3">Brzo pitanje za zagrijavanje 😄</p>

    <div id="triviaQuestion" class="fw-semibold">Učitavanje...</div>
    <div id="triviaOptions" class="mt-3"></div>

    <button id="newTrivia" class="btn btn-outline-purple mt-3">Novo pitanje</button>
  </div>
</div>

<div class="container py-4">
  <div class="card-soft p-4 text-center shadow-sm">
    <h3 class="h4 section-title mb-2">Hrvatski rječnik</h3>
    <p class="text-muted mb-3">Upiši riječ i dohvati objašnjenje.</p>

    <div class="row justify-content-center g-2">
      <div class="col-md-6">
        <input type="text" id="rijec" class="form-control" placeholder="Upiši riječ">
      </div>
      <div class="col-md-2">
        <button onclick="dohvati()" class="btn btn-purple w-100">Traži</button>
      </div>
    </div>

    <p id="rezultat" class="mt-3 mb-0"></p>
  </div>
</div>


<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-5 fw-bold">Što korisnici kažu</h2>

    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card card-soft text-center shadow-sm mb-4">
          <img src="leo.png" class="card-img-top mx-auto mt-3 rounded-circle" style="width: 120px; height: 120px;" alt="Leo">
          <div class="card-body">
            <h5 class="card-title">Leo</h5>
            <p class="card-text">"Quizard mi pomaže da učim pametnije i brže. Volim koliko je lagano izraditi sam svoj kviz!"</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-soft text-center shadow-sm mb-4">
          <img src="thompson.png" class="card-img-top mx-auto mt-3 rounded-circle" style="width: 120px; height: 120px;" alt="Thompson">
          <div class="card-body">
            <h5 class="card-title">Thompson</h5>
            <p class="card-text">"Ovo je najbolja platforma za kvizove koju sam ikad koristio. Čini učenje stvarno zabavnim i interaktivnim!"</p>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card card-soft text-center shadow-sm mb-4">
          <img src="emily.png" class="card-img-top mx-auto mt-3 rounded-circle" style="width: 120px; height: 120px;" alt="Emily">
          <div class="card-body">
            <h5 class="card-title">Emily</h5>
            <p class="card-text">"Lako mogu pratiti napredak i učiti u svom ritmu. Preporučujem Quizard!"</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="footer-space"></div>

<script>
async function fetchTrivia() {
  const questionDiv = document.getElementById('triviaQuestion');
  const optionsDiv = document.getElementById('triviaOptions');

  questionDiv.textContent = 'Učitavanje...';
  optionsDiv.innerHTML = '';

  try {
    const response = await fetch('trivia_api.php');
    const data = await response.json();

    if (data.error) {
      questionDiv.textContent = data.error;
      return;
    }

    questionDiv.innerHTML = `<strong>${data.question}</strong>`;

    let answers = [...data.incorrect_answers, data.correct_answer];
    answers = answers.sort(() => Math.random() - 0.5);

    answers.forEach(ans => {
      const btn = document.createElement('button');
      btn.textContent = ans;
      btn.className = 'btn btn-outline-purple';
      btn.onclick = () => {
        if (ans === data.correct_answer) {
          alert('Točno! 🎉');
        } else {
          alert(`Netočno! Točan odgovor je: ${data.correct_answer}`);
        }
        fetchTrivia();
      };
      optionsDiv.appendChild(btn);
    });
  } catch (err) {
    questionDiv.textContent = 'Greška pri učitavanju pitanja.';
  }
}

fetchTrivia();
document.getElementById('newTrivia').addEventListener('click', fetchTrivia);

function dohvati() {
  const rijec = document.getElementById('rijec').value;

  fetch('rjecnik_api.php?rijec=' + encodeURIComponent(rijec))
    .then(res => res.json())
    .then(data => {
      const p = document.getElementById('rezultat');
      if (data.error) {
        p.textContent = data.error;
      } else {
        p.innerHTML = `<strong>${data.rijec}</strong>: ${data.definicija}`;
      }
    })
    .catch(() => {
      document.getElementById('rezultat').textContent = 'Greška pri dohvaćanju definicije.';
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
