<?php
// Connect to class MySQL database (group8)
require_once __DIR__ . '/class_db.php';

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Guided mental wellness exercises including breathing techniques, mindfulness, and stress reduction activities.">
  <title>Exercises — New Horizon</title>
  <link rel="stylesheet" href="frontend/css/base.css">
  <link rel="stylesheet" href="frontend/css/exercises.css">
</head>

<body class="exercises-body">
  <header class="site-header">
    <nav class="nav">
      <a class="brand" href="/group8/frontend/index.html">New Horizon</a>

      <div class="nav-container">
        <ul class="nav-links">
          <li><a href="/group8/frontend/index.html">Home</a></li>
          <li><a href="/group8/exercises.php">Exercises</a></li>
          <li><a href="/group8/resources.php">Resources</a></li>
          <li><a href="/group8/frontend/pages/calendar.html">Calendar</a></li>
          <li><a href="/group8/frontend/pages/forum.html">Forum</a></li>
          <li><a href="/group8/frontend/pages/about.html">About</a></li>
        </ul>

        <div class="auth-buttons">
          <a href="/group8/frontend/pages/login.html" class="btn-header btn-login" id="login-button-container">Login</a>
          <a href="/group8/frontend/pages/account.html" class="btn-header btn-account" id="account-button-container" style="display: none;">My Account</a>
          <a href="/group8/frontend/pages/admin.html" class="btn-header btn-admin" id="admin-button-container" style="display: none;">Admin Panel</a>
          <button onclick="handleLogout()" class="btn-header btn-logout" id="logout-button-container" style="display: none;">Logout</button>
        </div>
      </div>
    </nav>
  </header>
<!--
<body class="exercises-body">
  <header data-partial="header"></header>
-->

  <main>
    <!-- Page title -->
    <section class="section exercises-hero">
      <div class="container">
        <h1 class="exercises-hero__title">Wellness Exercises</h1>
        <p class="exercises-hero__description">
          Short, research-informed activities you can try anytime to support mental wellness.
        </p>
      </div>
    </section>

    <!-- Exercise list -->
    <section class="section">
      <div class="container exercises-stack">
        <?php
          $sql = "
            SELECT e.Exercise_ID, e.Name, e.Description, e.Exercise_URL
            FROM Exercises e
            JOIN Webpages w ON e.Webpage_ID = w.Webpage_ID
            WHERE w.Handle = 'exercises'
            ORDER BY e.Name
          ";

          $result = $mysqli->query($sql);

          if (!$result) {
              echo '
                <div class="error-state">
                  <h3>Failed to load exercises</h3>
                  <p>Please try again later.</p>
                </div>';
          } elseif ($result->num_rows === 0) {
              echo '
                <div class="empty-state">
                  <h3>No exercises available yet</h3>
                  <p>Check back soon for wellness exercises!</p>
                </div>';
          } else {
              while ($row = $result->fetch_assoc()) {
                  echo '
                  <article class="exercises-card">
                    <h2 class="exercises-card__title">' . h($row['Name']) . '</h2>
                    <p class="exercises-card__content">' . h($row['Description']) . '</p>';

                  if (!empty($row['Exercise_URL'])) {
                      echo '
                      <p>
                        <a class="exercises-card__link" href="' . h($row['Exercise_URL']) . '" target="_blank" rel="noopener">
                          Learn more
                        </a>
                      </p>';
                  }

                  echo '</article>';
              }
          }

          $mysqli->close();
        ?>
      </div>
    </section>

    <!-- Bottom callout -->
    <section class="section">
      <div class="container exercises-cta">
        <p>
          New here? Start with <a href="#" class="exercises-link">Box Breathing</a>,
          or explore more on the <a href="resources.php" class="exercises-link">Resources</a> page.
        </p>
        <a href="/group8/frontend/index.html" class="btn">Back to Home</a>
      </div>
    </section>
  </main>


  
  <footer data-partial="footer"></footer>
  <script src="/group8/frontend/js/config.js"></script>
  <script src="/group8/frontend/js/app.js"></script>
  <!--
   Old API-based JS can stay commented out for now
  <script src="../js/api.js"></script>
  <script src="../js/exercises.js"></script>
  -->
</body>
</html>