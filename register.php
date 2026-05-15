<?php
// register.php

/* =========================
   Gmail SMTP CONFIG (EDIT)
   ========================= */
const GMAIL_USERNAME   = 'aryanmirza112233@gmail.com';     // your full Gmail
const GMAIL_APP_PASS   = 'kpamtdcmwlhlkofb';          // 16-char App Password (no spaces)
const GMAIL_FROM       = 'aryanmirza112233@gmail.com';     // usually same as username
const GMAIL_FROM_NAME  = 'Foodies';                   // shown to recipient

/* PHPMailer (Composer autoload required):
   composer require phpmailer/phpmailer
*/

/* ---- Namespaces for PHPMailer ---- */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ---- Session (stable cookie) ---- */
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

/* ---- If already logged in, block this page ---- */
if (!empty($_SESSION['user_id']) || (!empty($_SESSION['USER']) && !empty($_SESSION['USER']['id']))) {
  if (empty($_SESSION['user_id']) && !empty($_SESSION['USER']['id'])) {
    $_SESSION['user_id'] = (int)$_SESSION['USER']['id']; // normalize
  }
  header('Location: account.php');
  exit;
}

/* ---- Helpers ---- */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function send_otp_email($toEmail, $toName, $otp){
  $subject = 'Your verification code';
  $bodyTxt = "Hi {$toName},\n\nYour verification code is: {$otp}\nThis code expires in 10 minutes.\n\nIf you didn't request this, ignore this email.";

  $autoload = __DIR__ . '/vendor/autoload.php';
  if (!file_exists($autoload)) {
    error_log('PHPMailer autoload not found. Run: composer require phpmailer/phpmailer');
    return false;
  }

  require_once $autoload;

  try {
    $mail = new PHPMailer(true);

    // Enable for one-time debugging if needed:
    // $mail->SMTPDebug = 2;           // verbose debug output
    // $mail->Debugoutput = 'error_log';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = GMAIL_USERNAME;      // Gmail address
    $mail->Password   = GMAIL_APP_PASS;      // 16-char App Password (no spaces)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS
    $mail->Port       = 587;

    // If you hit SSL cert issues on Windows localhost, TEMPORARILY:
    // $mail->SMTPOptions = [
    //   'ssl' => [
    //     'verify_peer' => false,
    //     'verify_peer_name' => false,
    //     'allow_self_signed' => true,
    //   ],
    // ];

    $mail->setFrom(GMAIL_FROM, GMAIL_FROM_NAME); // should match your Gmail unless you've added an alias
    $mail->addAddress($toEmail, $toName);

    $mail->isHTML(false);
    $mail->Subject = $subject;
    $mail->Body    = $bodyTxt;

    $mail->send();
    return true;
  } catch (Exception $ex) {
    error_log('PHPMailer error: ' . (isset($mail) ? $mail->ErrorInfo : $ex->getMessage()));
    return false;
  }
}

/* ---- DB ---- */
require __DIR__ . '/includes/db.php'; // must define $conn (mysqli)

$error = "";
$success = "";

/* Preserve posted values (so the user doesn't retype everything) */
$name  = trim($_POST['name']  ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));

/* ---- Handle POST (send OTP or register) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // honeypot
  if (!empty($_POST['website'])) { header('Location: register.php'); exit; }

  // Which button?
  $sendingOtp    = isset($_POST['send_otp']);
  $doingRegister = !$sendingOtp; // the other submit is "Register"

  if ($sendingOtp) {
    // Validate name + email
    if (!$name) {
      $error = "Please enter your full name before sending OTP.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = "Please enter a valid email.";
    } else {
      // Check duplicate email
      $stmt = mysqli_prepare($conn, "SELECT id FROM user WHERE email=? LIMIT 1");
      mysqli_stmt_bind_param($stmt, "s", $email);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_store_result($stmt);
      if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Email already registered.";
      }
      mysqli_stmt_close($stmt);

      // Cooldown (60s) and send if OK
      if (!$error) {
        $now  = time();
        $last = (int)($_SESSION['OTP_LAST_SENT'] ?? 0);
        if ($now - $last < 60) {
          $error = "Please wait a minute before resending the OTP.";
        } else {
          $otp = random_int(100000, 999999);
          $_SESSION['EMAIL_OTP'] = [
            'email'   => $email,
            'name'    => $name,
            'code'    => (string)$otp,
            'expires' => $now + 600, // 10 minutes
          ];

          if (send_otp_email($email, $name, $otp)) {
            $_SESSION['OTP_LAST_SENT'] = $now;
            $success = "OTP sent to " . e($email) . ". It expires in 10 minutes.";
          } else {
            $error = "Couldn't send OTP email. Check your Gmail settings (App Password, ports) and PHP OpenSSL.";
          }
        }
      }
    }
  } else {
    // Final registration
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';
    $otpIn = trim($_POST['otp'] ?? '');

    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6 || $pass !== $pass2) {
      $error = "Please fill all fields correctly.";
    } else {
      // Validate OTP
      $otpSession = $_SESSION['EMAIL_OTP'] ?? null;
      if (!$otpSession) {
        $error = "Please request an OTP first.";
      } elseif ($otpSession['email'] !== $email) {
        $error = "This OTP was sent to a different email. Send a new one.";
      } elseif (time() > (int)$otpSession['expires']) {
        $error = "OTP expired. Please request a new one.";
      } elseif ($otpIn !== (string)$otpSession['code']) {
        $error = "Invalid OTP. Please check the code and try again.";
      }

      // Duplicate check again (race safety)
      if (!$error) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM user WHERE email=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
          $error = "Email already registered.";
        }
        mysqli_stmt_close($stmt);
      }

      // Create user
      if (!$error) {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt2 = mysqli_prepare($conn, "INSERT INTO user (name, email, password_hash, status) VALUES (?,?,?,1)");
        mysqli_stmt_bind_param($stmt2, "sss", $name, $email, $hash);
        if (mysqli_stmt_execute($stmt2)) {
          // Clear OTP after success
          unset($_SESSION['EMAIL_OTP']);
          header("Location: login.php");
          exit;

          // If you prefer auto-login instead:
          /*
          session_regenerate_id(true);
          $_SESSION['USER'] = ['id' => mysqli_insert_id($conn), 'name' => $name, 'email' => $email];
          $_SESSION['user_id'] = (int)$_SESSION['USER']['id'];
          session_write_close();
          header('Location: account.php'); exit;
          */
        } else {
          $error = "Something went wrong. Please try again.";
        }
        mysqli_stmt_close($stmt2);
      }
    }
  }
}

/* ---- View ---- */
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
include __DIR__ . "/includes/loader.php";

/* For UI cooldown timer */
$cooldownLeft = 0;
if (!empty($_SESSION['OTP_LAST_SENT'])) {
  $cooldownLeft = max(0, $_SESSION['OTP_LAST_SENT'] + 60 - time());
}
?>
<style>
  .input-wrap { position: relative; display: flex; flex-direction: column; }
  .input-wrap .form-control { padding-right: 2.5rem; }
  .toggle-eye { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); z-index: 5; }
  .field-feedback { position: static; margin-top: 0.25rem; font-size: 0.85rem; }
  .input-shell { position: relative; }
  .input-shell .form-control { padding-right: 2.5rem; }
  .toggle-eye { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); line-height: 1; z-index: 1; }
  .invalid-feedback { margin-top: .35rem; }
  .otp-help { font-size: .85rem; opacity: .85; }
</style>

<main class="py-1" style="background: var(--bg);">
  <div class="container" style="max-width: 520px;">
    <h2 class="section-title">Create account</h2>

    <div class="glass-card p-4 p-md-3 rounded-4">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php elseif ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
      <?php endif; ?>

      <form method="post" class="needs-validation" novalidate>
        <input type="text" name="website" class="d-none" autocomplete="off">

        <!-- Name -->
        <div class="mb-3">
          <input type="text" class="form-control pill-input" name="name" placeholder="Full name" value="<?= e($name) ?>" required>
          <div class="invalid-feedback">Please enter your full name.</div>
        </div>

        <!-- Email + Send OTP -->
        <div class="mb-2">
          <div class="input-group">
            <input type="email" class="form-control pill-input" name="email" placeholder="Email" value="<?= e($email) ?>" required>
            <button class="btn rounded-pill text-light ms-2" name="send_otp" value="1" formnovalidate
                    id="sendOtpBtn"
                    data-cooldown="<?= (int)$cooldownLeft ?>"
                    style="background: var(--brand); white-space: nowrap;">
              Send OTP
            </button>
          </div>
          <div class="invalid-feedback d-block" style="display:none;"></div>
        </div>

        <!-- OTP -->
        <div class="mb-3">
          <input type="text" class="form-control pill-input" name="otp" inputmode="numeric" pattern="\d{6}" maxlength="6" placeholder="Enter 6-digit OTP">
          <div class="otp-help text-muted mt-1">Check your email for the code. Expires in 10 minutes.</div>
        </div>

        <!-- Password -->
        <div class="mb-3">
          <div class="input-shell">
            <input type="password" class="form-control pill-input pe-5" id="regPass"
              name="password" placeholder="Password (min 6 chars)" required minlength="6">
            <button type="button" class="btn toggle-eye p-0 border-0 bg-transparent"
              onclick="togglePass('regPass','passIcon')" aria-label="Toggle password">
              <i id="passIcon" class="bi bi-eye fs-5 text-muted"></i>
            </button>
          </div>
          <div class="invalid-feedback">Password is required (min 6 chars).</div>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
          <div class="input-shell">
            <input type="password" class="form-control pill-input pe-5" id="regPass2"
              name="password_confirm" placeholder="Confirm password" required>
            <button type="button" class="btn toggle-eye p-0 border-0 bg-transparent"
              onclick="togglePass('regPass2','passIcon2')" aria-label="Toggle confirm password">
              <i id="passIcon2" class="bi bi-eye fs-5 text-muted"></i>
            </button>
          </div>
          <div class="invalid-feedback">Passwords must match.</div>
        </div>

        <button class="btn my-3 px-4 col-12 rounded-pill text-light" style="background: var(--brand);">Register</button>
      </form>

      <div class="small text-center mt-3">
        Already have an account? <a href="login.php" style="color: #6e3b16;text-decoration: none;">Login</a>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(() => {
  const form = document.querySelector('.needs-validation');
  if (!form) return;

  const pass = document.getElementById('regPass');
  const pass2 = document.getElementById('regPass2');

  const checkMatch = () => {
    if (!pass2.value) { pass2.setCustomValidity(''); return; }
    pass2.setCustomValidity(pass2.value === pass.value ? '' : 'Passwords must match.');
  };

  pass.addEventListener('input', checkMatch);
  pass2.addEventListener('input', checkMatch);

  form.addEventListener('submit', e => {
    // If user clicked "Send OTP", skip validation of required fields
    if (e.submitter && e.submitter.name === 'send_otp') return;
    checkMatch();
    if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
    form.classList.add('was-validated');
  }, false);

  // Resend cooldown UI
  const btn = document.getElementById('sendOtpBtn');
  if (btn) {
    let left = parseInt(btn.dataset.cooldown || '0', 10);
    const origText = btn.textContent;
    const tick = () => {
      if (left > 0) {
        btn.disabled = true;
        btn.textContent = 'Resend in ' + left + 's';
        left--;
        setTimeout(tick, 2000);
      } else {
        btn.disabled = false;
        btn.textContent = origText;
      }
    };
    if (left > 0) tick();
  }
})();

function togglePass(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  const isPwd = input.type === 'password';
  input.type = isPwd ? 'text' : 'password';
  icon.classList.toggle('bi-eye', !isPwd);
  icon.classList.toggle('bi-eye-slash', isPwd);
}
</script>
 