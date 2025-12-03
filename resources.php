<?php
// Connect to class MySQL database (group8)
require_once __DIR__ . '/class_db.php';

// Helper for safe output
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resources — New Horizon</title>
  <link rel="stylesheet" href="frontend/css/base.css">
  <link rel="stylesheet" href="frontend/css/resources.css">
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
<body class="resources-body">
  <header data-partial="header"></header>
-->

  <main>
    <!-- Hero -->
    <section class="resources-hero">
      <h1>Resources</h1>
      <p>Explore helpful mental wellness links.</p>
    </section>

    <!-- Dynamic resources list -->
    <section class="resources-section">
      <h2>Curated Mental Health Resources</h2>

      <?php
        // Load all resources that belong to the "resources" page
        $sql = "
          SELECT r.Resource_ID, r.Title, r.Description, r.Resource_URL
          FROM Resources r
          JOIN Webpages w ON r.Webpage_ID = w.Webpage_ID
          WHERE w.Handle = 'resources'
          ORDER BY r.Title
        ";

        $result = $mysqli->query($sql);

        if (!$result) {
            // Query failed
            echo '<p>Sorry, we could not load resources at this time. Please try again later.</p>';
        } elseif ($result->num_rows === 0) {
            // No rows yet
            echo '<p>No resources have been added yet. Please check back soon.</p>';
        } else {
            echo '<ul class="resources-list">';
            while ($row = $result->fetch_assoc()) {
                echo '<li class="resource-item">';
                echo '  <a href="' . h($row['Resource_URL']) . '" target="_blank" rel="noopener">';
                echo        h($row['Title']);
                echo '  </a>';

                if (!empty($row['Description'])) {
                    echo '<p class="resource-description">' . h($row['Description']) . '</p>';
                }

                echo '</li>';
            }
            echo '</ul>';
        }

        $mysqli->close();
      ?>
    </section>

    <div class="resources-back">
      <a class="btn" href="/group8/frontend/index.html">Back to Home</a>
    </div>
  </main>

  <footer data-partial="footer"></footer>
  <script src="/group8/frontend/js/config.js"></script>
  <script src="/group8/frontend/js/app.js"></script>
  <!-- If api.js is only for the old backend API, you can comment this out for now
  <script src="../js/api.js"></script>
  -->
</body>
</html>