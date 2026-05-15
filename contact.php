<?php
/* ---------- PROCESS FORM (runs before any HTML) ---------- */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$sent = false;
$errors = [];
$old = ['name'=>'','email'=>'','subject'=>'','message'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Honeypot (bots)
  if (!empty($_POST['website'] ?? '')) {
    // pretend success to bots
    $sent = true;
  } else {
    // Collect + basic validate
    $old['name']    = trim((string)($_POST['name'] ?? ''));
    $old['email']   = trim((string)($_POST['email'] ?? ''));
    $old['subject'] = trim((string)($_POST['subject'] ?? '')); // PHONE goes here
    $old['message'] = trim((string)($_POST['message'] ?? ''));

    if ($old['name'] === '')                                   $errors['name'] = 'Please enter your name.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))     $errors['email'] = 'Please enter a valid email.';
    if ($old['subject'] === '')                                $errors['subject'] = 'Please enter your phone number.';
    if ($old['message'] === '')                                $errors['message'] = 'Please write a short message.';

    if (!$errors) {
      // Use shared mysqli connection
      require __DIR__ . '/includes/db.php'; // gives $conn (mysqli)

      if (!$conn) {
        $errors['__db'] = 'Database connection failed.';
      } else {
        $sql = "INSERT INTO contacts (name, email, subject, message, status) VALUES (?, ?, ?, ?, 1)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
          mysqli_stmt_bind_param($stmt, "ssss",
            $old['name'],
            $old['email'],
            $old['subject'], // phone saved in subject
            $old['message']
          );
          if (mysqli_stmt_execute($stmt)) {
            $sent = true;
            // clear old values after success
            $old = ['name'=>'','email'=>'','subject'=>'','message'=>''];
          } else {
            $errors['__db'] = 'Could not save right now. Please try again.';
          }
          mysqli_stmt_close($stmt);
        } else {
          $errors['__db'] = 'Unable to prepare request. Please try again.';
        }
        // mysqli_close($conn); // optional; usually closed at end of request
      }
    }
  }
}
?>

<?php include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
?>
<?php include __DIR__ . "/includes/loader.php";  ?>

<style>
  :root { --brand: #DB7D31; } /* same orange as your button */

  .pill-input,
  .form-floating>.pill-input {
    border: 1.5px solid rgba(219,125,49,.45);
    background: transparent;
    border-radius: 999px;
    outline: none;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.35);
    transition: border-color .2s, box-shadow .2s, background-color .2s;
  }
  .pill-input:focus, .pill-textarea:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 .2rem rgba(219,125,49,.15);
    background: rgba(255,255,255,.35);
  }
  .was-validated .pill-input:invalid,
  .was-validated .pill-textarea:invalid { border-color:#dc3545; box-shadow:none; }

  .pill-input { padding: .6rem 1.5rem; }
  .pill-textarea {
    border: 1.5px solid rgba(219,125,49,.45);
    background: transparent;
    border-radius: 20px;
    padding: .75rem 1.5rem;
    outline: none; resize: vertical; min-height: 150px;
  }
</style>

<main class="py-4" style="background: var(--bg);">
  <div class="container">
    <h2 class="section-title mb-1">Contact Us</h2>

    <div class="row g-4 align-items-stretch">
      <!-- Left: Form -->
      <div class="col-lg-5">
        <div class="glass-card p-4 p-lg-5 h-100">
          <?php if ($sent): ?>
            <div class="alert alert-success rounded-3 py-2 mb-3">
              Thanks — we’ll get back to you soon.
            </div>
          <?php endif; ?>

          <?php if (!empty($errors['__db'])): ?>
            <div class="alert alert-danger rounded-3 py-2 mb-3"><?= e($errors['__db']) ?></div>
          <?php endif; ?>

          <!-- Post to THIS page -->
          <form method="post" action="" class="needs-validation <?= $errors ? 'was-validated' : '' ?>" novalidate>
            <input type="text" name="website" class="d-none" autocomplete="off">

            <div class="mb-3">
              <input type="text" class="form-control pill-input" id="cName" name="name"
                     placeholder="Your name" value="<?= e($old['name']) ?>" required>
              <div class="invalid-feedback"><?= e($errors['name'] ?? 'Enter your name.') ?></div>
            </div>

            <div class="mb-3">
              <input type="email" class="form-control pill-input" id="cEmail" name="email"
                     placeholder="you@example.com" value="<?= e($old['email']) ?>" required>
              <div class="invalid-feedback"><?= e($errors['email'] ?? 'Enter a valid email.') ?></div>
            </div>

            <!-- Phone number -> SUBJECT (DB) -->
            <div class="mb-3">
              <input type="tel" class="form-control pill-input" id="cSubject" name="subject"
                     placeholder="+61 xxx xxx xxx" value="<?= e($old['subject']) ?>" required>
              <div class="invalid-feedback"><?= e($errors['subject'] ?? 'Enter your phone number.') ?></div>
            </div>

            <div class="mb-3">
              <textarea class="form-control pill-textarea" id="cMsg" name="message"
                        placeholder="Message" required><?= e($old['message']) ?></textarea>
              <div class="invalid-feedback"><?= e($errors['message'] ?? 'Write a short message.') ?></div>
            </div>

            <button class="btn col-12 col-md-12 my-md-4 py-2 btn-sm btn-outline-success rounded-pill" type="submit">
              Send
            </button>
          </form>
        </div>
      </div>

      <!-- Right: Map with glass info panel -->
      <div class="col-lg-7">
        <div class="position-relative rounded-4 overflow-hidden h-100 shadow-sm">
          <div class="ratio ratio-16x9 ratio-lg-4x3 map">
            <iframe
              src="https://maps.google.com/maps?q=melbourne&t=&z=13&ie=UTF8&iwloc=&output=embed"
              loading="lazy" allowfullscreen style="border:0;"></iframe>
          </div>

          <div class="contact-glass rounded-4 p-3 p-md-4">
            <h3 class="h5 mb-2" style="font-family: var(--fancy,'Playfair Display',serif);">Reach us</h3>
            <div class="small mb-1"><span class="text-muted">Phone:</span> 0000-0000000</div>
            <div class="small mb-1"><span class="text-muted">Email:</span> support@foodies.pk</div>
            <div class="small"><span class="text-muted">Address:</span> Melbourne, Aus</div>
          </div>
        </div>
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
</script>
