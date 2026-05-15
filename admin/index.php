<?php
// --------- LOGIN PAGE (admin/index.php) ---------

// Start session first so flags are available to includes
if (session_status() === PHP_SESSION_NONE) session_start();

// Tell admin-top.php to NOT force auth on this page
$SKIP_ADMIN_AUTH = true;

// Include your bootstrap/DB/etc.
require_once("../assets/inc/admin-top.php");

// Where to land after login (adjust if needed)
$DASHBOARD_URL = "dashboard"; // e.g., "dashboard" folder with index.php

// If already logged in, go to dashboard
if (!empty($_SESSION["ADMIN_LOGIN"]["ADMIN_ID"])) {
  header("Location: {$DASHBOARD_URL}");
  exit;
}

// CSRF token
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$err = null;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["btn-login"])) {
  $csrf = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf'], $csrf)) {
    $err = "Invalid request. Please try again.";
  } else {
    // Treat the single input as USERNAME (DB has no email column)
    $loginInput = trim($_POST["email"] ?? "");   // keeping the field name 'email' for your form
    $password   = $_POST["password"] ?? "";

    if ($loginInput === "" || $password === "") {
      $err = "Username and password are required.";
    } else {
      // Prepared statement: username only
      $stmt = $conn->prepare("
        SELECT id, name, username, password, image, role
        FROM admins
        WHERE username = ?
        LIMIT 1
      ");
      $stmt->bind_param("s", $loginInput);
      $stmt->execute();
      $res = $stmt->get_result();

      if ($row = $res->fetch_assoc()) {
        if (password_verify($password, $row["password"])) {
          session_regenerate_id(true);

          // Use legacy keys the rest of the panel expects
          $_SESSION["ADMIN_LOGIN"] = [];
          $_SESSION["ADMIN_LOGIN"]["ADMIN_ID"]    = (int)$row["id"];
          $_SESSION["ADMIN_LOGIN"]["ADMIN_NAME"]  = $row["name"];
          $_SESSION["ADMIN_LOGIN"]["ADMIN_USER"]  = $row["username"];
          $_SESSION["ADMIN_LOGIN"]["ADMIN_IMAGE"] = $row["image"];
          $_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"]  = $row["role"];
          $_SESSION["ADMIN_LOGIN"]["AT"]          = time();

          // rotate CSRF after login
          $_SESSION['csrf'] = bin2hex(random_bytes(32));

          header("Location: {$DASHBOARD_URL}");
          exit;
        } else {
          $err = "Invalid credentials.";
        }
      } else {
        $err = "Account not found.";
      }
      $stmt->close();
    }
  }
}
?>
<style>
/* Brown theme color */
:root {
  --brand-brown: #795548;
  --brand-brown-hover: #6d4c41;
}

/* Button */
.btn-brown {
  background-color: var(--brand-brown);
  border-color: var(--brand-brown);
  color: #fff;
}
.btn-brown:hover,
.btn-brown:focus {
  background-color: var(--brand-brown-hover);
  border-color: var(--brand-brown-hover);
  color: #fff;
}

/* Inputs focus color */
.form-control:focus {
  border-color: var(--brand-brown);
  box-shadow: 0 0 0 .25rem rgba(121, 85, 72, .25);
}

/* Spinner rings brown variant (if you add rings later) */
.logo-wrap::before { border-top-color: var(--brand-brown); border-right-color: rgba(0, 204, 255, .8); }
.logo-wrap::after  { border-left-color: var(--brand-brown); border-bottom-color: rgba(0, 204, 255, .8); }

/* Page background */
.auth-bg {
  min-height: 100vh;
  background:
    radial-gradient(1200px 600px at 10% -10%, rgba(0, 204, 255, .15), transparent 60%),
    radial-gradient(900px 500px at 110% 10%, rgba(255, 0, 150, .12), transparent 55%),
    linear-gradient(180deg, var(--bg, #f5f7fb), #eef2f7);
  display: flex;
  align-items: center;
}

/* Glass card */
.glass-card {
  background: rgba(255, 255, 255, .6);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, .4);
  box-shadow: 0 10px 30px rgba(13, 38, 76, .08);
  border-radius: 1.5rem;
}

.logo-wrap { position: relative; height: 50px; width: 50px; margin: 0 auto 12px; }
.logo-img  { height: 50px; width: 50px; }

/* Inputs (match premium theme) */
.form-control,
.form-floating>.form-control {
  border-radius: 14px !important;
  border: 1px solid #dfe6ef;
}

.btn-primary { border-radius: 14px; padding: .65rem 1.25rem; font-weight: 600; }

.small-muted { color: #64748b; }
</style>

<body>
  <div class="auth-bg">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
          <div class="card glass-card p-4 p-md-5">
            <div class="text-center">
              <div class="logo-wrap">
                <img src="../assets/img/logo.png" alt="Logo" class="logo-img">
              </div>
              <h5 class="mb-3">Foodies</h5>
              <h5 class="mb-1 small-muted">Admin Sign In</h5>
              <div class="small small-muted mb-3">Manage products, orders & categories</div>
            </div>

            <?php if (!empty($err)): ?>
              <div class="alert alert-warning rounded-4 py-2 px-3 mb-3">
                <strong>Heads up:</strong> <?= htmlspecialchars($err) ?>
              </div>
            <?php endif; ?>

            <form method="post" action="">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
              <div class="mb-3 form-floating">
                <!-- Keep id/name 'email' so you don't have to change any JS; it's actually username -->
                <input type="text" class="form-control" id="email" name="email" placeholder="Email or Username" required autocomplete="username">
                <label for="email">Email or Username</label>
              </div>
              <div class="mb-2 form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required autocomplete="current-password">
                <label for="password">Password</label>
              </div>

              <div class="d-grid mt-3">
                <button class="btn btn-brown rounded-pill" id="login" type="submit" name="btn-login">Login</button>
              </div>
            </form>
          </div>
          <div class="text-center small small-muted mt-3">
            © <?= date('Y') ?> Foodies — Admin Panel
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php require_once("../assets/inc/admin-bottom.php"); ?>
</body>
