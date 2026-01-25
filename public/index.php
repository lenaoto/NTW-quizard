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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quizard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .hero {
      background: linear-gradient(90deg, rgba(162,89,255,1) 0%, rgba(255,255,255,1) 100%);
      padding: 80px 0;
      color: white;
    }
    .hero img {
      max-width: 100%;
      height: auto;
    }
    .btn-outline-purple {
      color: #a259ff;
      border-color: #a259ff;
    }
    .btn-outline-purple:hover {
      background-color: #a259ff;
      color: white;
    }
    .card-img-top {
      object-fit: cover;
      border: 3px solid #a259ff;
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-light">
  <div class="container">
    <a class="navbar-brand fw-bold" href="about.php">Quizard</a>
    <div class="d-flex align-items-center">
      <?php if ($isAdmin): ?>
        <a href="admin_dashboard.php" class="btn btn-warning me-2">Admin Panel</a>
      <?php endif; ?>
      <?php if ($isLoggedIn): ?>
        <a href="create_quiz.php" class="btn btn-primary me-2">Kreiraj kviz</a>
        <a href="profile.php" class="btn btn-outline-secondary me-2">Profil</a>
        <a href="logout.php" class="btn btn-outline-danger">Odjava</a>
      <?php elseif ($isGuest): ?>
        <a href="logout.php" class="btn btn-outline-danger">Izađi iz gost načina</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-outline-purple me-2">Registracija</a>
        <a href="login.php" class="btn btn-outline-purple">Prijava</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-6 text-center text-md-start">
        <h1 class="display-4 fw-bold">Uči pametnije,<br>ne teže.</h1>
        <p class="lead">Quizard čini učenje lakšim!<br>Počni učiti danas!</p>
        <?php if (!$isLoggedIn && !$isGuest): ?>
          <a href="register.php" class="btn btn-outline-light btn-lg mt-3 me-2">Registriraj se besplatno!</a>
          <a href="?guest=1" class="btn btn-light btn-lg mt-3">Nastavi kao gost</a>
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
  <form method="GET" class="row g-2 align-items-center">
    <div class="col-md-4">
      <input type="text" name="keyword" class="form-control" placeholder="Search by quiz title or author" value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-outline-purple w-100">Traži</button>
    </div>
  </form>
</div>

<section class="py-5">
  <div class="container">
    <h2 class="text-center mb-4 fw-bold">Dostupni kvizovi</h2>
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
            <div class="card shadow-sm">
              <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h5>
                <p class="card-text"><?= htmlspecialchars($quiz['description']) ?></p>
                <p class="text-muted small mb-2">
                  <?= $quiz['username'] ? 'Created by: ' . htmlspecialchars($quiz['username']) : 'Created by: Guest or Admin' ?>
                </p>
                <a href="quiz_details.php?id=<?= $quiz['id'] ?>" class="btn btn-outline-purple">View Details</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-center text-muted">Još nema dostupnih kvizova.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>



<div class="container py-5">
  <div class="trivia-box">
    <h3 class="fw-bold mb-3">Trivia dana</h3>
    <div id="triviaQuestion">Učitavanje...</div>
    <div id="triviaOptions" class="mt-3"></div>
    <button id="newTrivia" class="btn btn-outline-purple mt-3">Novo pitanje</button>
  </div>
</div>


<script>
async function fetchTrivia() {
    const questionDiv = document.getElementById('triviaQuestion');
    const optionsDiv = document.getElementById('triviaOptions');

    questionDiv.innerHTML = 'Učitavanje...';
    optionsDiv.innerHTML = '';

    try {
        const response = await fetch('trivia_api.php');
        const data = await response.json();

        if (data.error) {
            questionDiv.innerHTML = data.error;
            return;
        }

        questionDiv.innerHTML = `<strong>${data.question}</strong>`;

        let answers = [...data.incorrect_answers, data.correct_answer];
        answers = answers.sort(() => Math.random() - 0.5);

        answers.forEach(ans => {
            const btn = document.createElement('button');
            btn.textContent = ans;
            btn.className = 'btn btn-outline-primary m-1';
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
        questionDiv.innerHTML = 'Greška pri učitavanju pitanja.';
    }
}


fetchTrivia();

document.getElementById('newTrivia').addEventListener('click', fetchTrivia);
</script>

<style>
  

.trivia-box {
  background-color: #d8c6ff; 
  border-radius: 16px;
  padding: 30px;
  max-width: 100%;
  margin: 0 auto;
  text-align: center;
}
</style>




<div class="dictionary-box">
  <h3 class="fw-bold mb-3">Hrvatski rječnik</h3>

  <input type="text" id="rijec" class="form-control" placeholder="Upiši riječ">
  <button onclick="dohvati()" class="btn btn-light">Traži</button>

  <p id="rezultat"></p>
</div>

<style>
.dictionary-box { 
  background-color: rgba(57, 16, 110, 0.73); 
  color: white;
  border-radius: 16px;
  padding: 30px;
  max-width: 900px;
  margin: 40px auto;
  text-align: center;}

  .dictionary-box input {
  max-width: 300px;
  margin: 10px auto;
}

.dictionary-box button {
  margin-top: 10px;
}

.dictionary-box p {
  margin-top: 15px;
  font-size: 1.1rem;
}
</style>

<script>
function dohvati() {
    const rijec = document.getElementById('rijec').value;

    fetch('rjecnik_api.php?rijec=' + rijec)
        .then(res => res.json())
        .then(data => {
            const p = document.getElementById('rezultat');
            if (data.error) {
                p.innerHTML = data.error;
            } else {
                p.innerHTML = `<strong>${data.rijec}</strong>: ${data.definicija}`;
            }
        });
}
</script>


<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-5 fw-bold">Što korisnici kažu</h2>
    <div class="row justify-content-center">
      <div class="col-md-4">
        <div class="card text-center shadow-sm mb-4">
          <img src="leo.png" class="card-img-top mx-auto mt-3 rounded-circle" style="width: 120px; height: 120px;" alt="Leo">
          <div class="card-body">
            <h5 class="card-title">Leo</h5>
            <p class="card-text">"Quizard mi pomaže da učim pametnije i brže. Volim koliko je lagano izraditi sam svoj kviz!"</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center shadow-sm mb-4">
          <img src="thompson.png" class="card-img-top mx-auto mt-3 rounded-circle" style="width: 120px; height: 120px;" alt="Thompson">
          <div class="card-body">
            <h5 class="card-title">Thompson</h5>
            <p class="card-text">"Ovo je najbolja platforma za kvizove koju sam ikad koristio. Čini učenje stvarno zabavnim i interaktivnim!"</p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center shadow-sm mb-4">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
