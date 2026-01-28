<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$isGuest = isset($_SESSION['guest']) && $_SESSION['guest'] === true;
$canSeeMap = $isLoggedIn || $isGuest;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>About – Quizard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body { background-color: #f9f7fd; }

    .section-title {
      color: #a259ff;
    }

    .card {
      background: #f3edfc;
      border: 1px solid #e0d4f7;
    }

    .icon {
      font-size: 1.6rem;
      color: #a259ff;
    }

    .map-wrapper iframe {
      width: 100%;
      height: 360px;
      border: 0;
      border-radius: 12px;
    }
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
      </li>
    </ul>
      <?php if ($isLoggedIn): ?>
        <a href="profile.php" class="btn btn-outline-secondary me-2">Profil</a>
        <a href="logout.php" class="btn btn-outline-danger">Odjava</a>
      <?php elseif ($isGuest): ?>
        <a href="logout.php" class="btn btn-outline-danger">Izađi iz Gost načina</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-primary me-2">Prijavi se</a>
        <a href="register.php" class="btn btn-outline-primary">Registriraj se</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-5">

  <h1 class="mb-4 section-title text-center">O projektu</h1>

  <div class="card p-4 mb-5">
    <p>
      <strong>Quizard</strong> je web platforma za kvizove razvijena kao dio akademskog
      projekta usmjerenog na moderne web tehnologije i sigurno dizajniranje aplikacija.
    </p>
    <p>
      Projekt demonstrira implementaciju autentikacije korisnika, autorizacije temeljene na
      ulogama, interakciju s bazom podataka korištenjem pripremljenih izjava te zaštitu
      od uobičajenih sigurnosnih ranjivosti poput SQL injekcija.
    <p>
      Sustav omogućava korisnicima kreiranje, rješavanje i upravljanje kvizovima, dok
      moderatorii administratori mogu nadzirati kvalitetu sadržaja putem moderiranja komentara.
    <p class="mb-0">
      Tehnologije koje su korištene uključuju <strong>PHP</strong>, <strong>MySQL (PDO)</strong>,
      <strong>JavaScript</strong> i <strong>Bootstrap</strong>, s naglaskom na čistu strukturu
      koda i najbolje sigurnosne prakse.
    </p>
  </div>

  <h2 class="mb-4 section-title text-center">O nama</h2>

  <div class="row mb-5">
    <div class="col-md-6 mb-3">
      <div class="card p-4 h-100">
        <h5><i class="bi bi-people-fill icon me-2"></i>Tko smo mi?</h5>
        <p class="mt-3">
          Mi smo mali tim developera strastvenih u izgradnji jednostavnih,
          učinkovitih i korisnički prijateljskih edukativnih aplikacija.
        </p>
        <p>
          Quizard je irađen s idejom da učenje treba biti interaktivno,
          dostupno i zabavno za svakoga.
        </p>
      </div>
    </div>

    <div class="col-md-6 mb-3">
      <div class="card p-4 h-100">
        <h5><i class="bi bi-lightbulb-fill icon me-2"></i>Naša misija</h5>
        <ul class="mt-3">
          <li>Poticati učenje kroz praksu</li>
          <li>Omogućiti alate za jednostavno stvaranje kvizova</li>
          <li>Podržavati i registrirane korisnike i goste</li>
          <li>Osigurati sigurnu i pouzdanu platformu</li>
        </ul>
      </div>
    </div>
  </div>

<h2 class="mb-4 section-title text-center">Kontakt</h2>

<div class="row justify-content-center mb-5">
  <div class="col-md-8">
    <div class="card p-4 text-start">

      <p>
        <i class="bi bi-envelope-fill icon me-2"></i>
        Email:
        <a href="mailto:msykora@tvz.hr">msykora@tvz.hr</a>
      </p>
      <p>
        <i class="bi bi-github icon me-2"></i>
        GitHub:
        <a href="https://github.com/lenaoto/NTW-quizard" target="_blank">
          github.com/lenaoto/quizard
        </a>
      </p>

      <p>
  <i class="bi bi-instagram icon me-2"></i>
  Instagram:
  <a href="https://www.instagram.com/quizard_app" target="_blank">
    instagram.com/quizard_app
  </a>
</p>

<p>
  <i class="bi bi-facebook icon me-2"></i>
  Facebook:
  <a href="https://www.facebook.com/quizard" target="_blank">
    facebook.com/quizard
  </a>
</p>


      <a href="contact.php" class="btn btn-primary mt-3">
        <i class="bi bi-chat-dots-fill me-1"></i>
        Kontakirajte nas putem forme
      </a>
    </div>
  </div>
</div>


  <h2 class="mb-4 section-title text-center">Naše sjedište</h2>

  <?php if ($canSeeMap): ?>
    <p class="text-center mb-3">
      Naše sjedište se nalazi na adresi prikazanoj na karti ispod.
    </p>

    <div class="map-wrapper mb-3">
      <iframe
        src="https://www.openstreetmap.org/export/embed.html?bbox=15.965%2C45.780%2C16.025%2C45.820&layer=mapnik&marker=45.800%2C15.995"
        loading="lazy"
        allowfullscreen>
      </iframe>
    </div>

    <p class="text-center text-muted">
      <i class="bi bi-geo-alt-fill"></i> Zagreb, Croatia
    </p>
  <?php else: ?>
    <p class="text-center text-muted">
      <i class="bi bi-lock-fill"></i>
      Molimo prijavite se ili nastavite kao gost za pregled naše lokacije.
    </p>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
