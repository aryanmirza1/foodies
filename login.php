<?php
// login.php
$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'login_required') {
  $msg = "You must log in first to purchase something.";
}


/* ---- Stable session cookie + start ---- */
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',                                        // critical so cookie works on all pages
    'domain'   => '',                                         // current host
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}



/* ---- If already logged in, go to account ---- */
if (!empty($_SESSION['USER']['id']) && empty($_SESSION['user_id'])) {
  $_SESSION['user_id'] = (int)$_SESSION['USER']['id'];
}
if (!empty($_SESSION['user_id'])) {
  header('Location: account.php');
  exit;
}

/* ---- DB (must NOT redirect or destroy session) ---- */
require __DIR__ . '/includes/db.php'; // defines $conn (mysqli)

/* ---- Handle POST ---- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // honeypot
  if (!empty($_POST['website'])) {
    header('Location: login.php');
    exit;
  }

  $email = strtolower(trim($_POST['email'] ?? ''));
  $pass  = $_POST['password'] ?? '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
    $error = 'Please enter a valid email and password.';
  } else {
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password_hash, status FROM user WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $user = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if ($user && (int)$user['status'] === 1 && password_verify($pass, $user['password_hash'])) {
      // success: set both keys, then redirect
      session_regenerate_id(true);

      $_SESSION['USER'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => strtolower($user['email']),
      ];
      $_SESSION['user_id'] = (int)$user['id'];   // <-- account.php checks this

      session_write_close();                      // flush cookie before redirect
      header('Location: index.php');
      exit;
    } else {
      $error = 'Invalid email or password.';
    }
  }
}

/* ---- View ---- */
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
?>
<?php include __DIR__ . "/includes/loader.php";  ?>

<main class="py-1" style="background: var(--bg);">
  <div class="container" style="max-width: 480px;">
    <h2 class="section-title">Login</h2>

    <div class="glass-card p-4 p-md-4 rounded-4">
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if ($msg): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>


      <form method="post" class="needs-validation" novalidate>
        <input type="text" name="website" class="d-none" autocomplete="off">

        <div class="mb-3">
          <input type="email" class="form-control pill-input" name="email" placeholder="Email" required>
          <div class="invalid-feedback">Please enter a valid email.</div>
        </div>

        <div class="mb-3">
          <div class="position-relative">
            <input type="password" class="form-control pill-input pe-5" id="loginPass" name="password" placeholder="Password" required>
            <button type="button" class="btn toggle-eye p-0 border-0 bg-transparent" onclick="toggleLoginPass()" aria-label="Toggle password visibility">
              <i id="toggleIcon" class="bi bi-eye fs-5 text-muted"></i>
            </button>
            <div class="invalid-feedback" style="position: absolute; bottom: -1.4rem; left: 0;">Password is required.</div>
          </div>
        </div>

        <button class="btn my-3 px-4 col-12 rounded-pill text-light" style="background: var(--brand); color: var(--brand);">Login</button>
      </form>

      <div class="small text-center text-decoration-none">
        No account? <a href="register.php" style="color: #6e3b16; text-decoration: none;">Register</a>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
  (() => {
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', e => {
        if (!form.checkValidity()) {
          e.preventDefault();
          e.stopPropagation();
        }
        form.classList.add('was-validated');
      });
    });
  })();

  function toggleLoginPass() {
    const input = document.getElementById('loginPass');
    const icon = document.getElementById('toggleIcon');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.classList.toggle('bi-eye', !show);
    icon.classList.toggle('bi-eye-slash', show);
  }
</script>