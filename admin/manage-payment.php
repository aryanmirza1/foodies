<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/tables.php");

/*
  Assumptions from tables.php for this page:
  - $table      = 'payment'
  - $folderName = 'payment'
*/

$btnName = "Add";
$id      = null;

// Defaults
$bank    = "";
$title   = "";
$no      = "";       // text
$contact = "";       // text
$status  = 1;        // keep enabled by default

// Edit mode: load row
if (isset($_GET["id"]) && (int)$_GET["id"] > 0) {
  $btnName = "Update";
  $id = getSaveValue($conn, $_GET["id"]);
  $res = mysqli_query($conn, "SELECT * FROM $table WHERE id='$id' LIMIT 1");
  if ($row = mysqli_fetch_assoc($res)) {
    $bank    = $row["bank"]    ?? "";
    $title   = $row["title"]   ?? "";
    $no      = (string)($row["no"] ?? "");         // keep as text
    $contact = (string)($row["contact"] ?? "");    // keep as text
    $status  = (int)($row["status"] ?? 1);
  }
}

// Handle submit
if (isset($_POST["btn-submit"])) {
  // Read as strings (TEXT)
  $bank    = getSaveValue($conn, $_POST["bank"]    ?? "");
  $title   = getSaveValue($conn, $_POST["title"]   ?? "");
  $no      = getSaveValue($conn, $_POST["no"]      ?? "");      // text, not int
  $contact = getSaveValue($conn, $_POST["contact"] ?? "");      // text, not int

  // Keep status as-is (no checkbox in form)
  $status = 1;

  if (!empty($_GET["id"]) && (int)$_GET["id"] > 0 && $id) {
    // UPDATE (quote text fields)
    $sql = sprintf(
      "UPDATE %s SET bank='%s', title='%s', no='%s', contact='%s', status=%d WHERE id='%s'",
      $table,
      mysqli_real_escape_string($conn, $bank),
      mysqli_real_escape_string($conn, $title),
      mysqli_real_escape_string($conn, $no),
      mysqli_real_escape_string($conn, $contact),
      $status,
      mysqli_real_escape_string($conn, $id)
    );
    $ok = mysqli_query($conn, $sql);
    if ($ok) {
      $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success:</strong> Payment method updated.</div>';
      header("location:$folderName");
      exit;
    } else {
      $_SESSION["msg"] = '<div class="alert alert-warning msg"><strong>Warning:</strong> Could not update record.</div>';
    }
  } else {
    // INSERT (quote text fields)
    $sql = sprintf(
      "INSERT INTO %s (bank, title, no, contact, status) VALUES ('%s','%s','%s','%s',%d)",
      $table,
      mysqli_real_escape_string($conn, $bank),
      mysqli_real_escape_string($conn, $title),
      mysqli_real_escape_string($conn, $no),
      mysqli_real_escape_string($conn, $contact),
      $status
    );
    $ok = mysqli_query($conn, $sql);
    if ($ok) {
      $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success:</strong> Payment method added.</div>';
      header("location:$folderName");
      exit;
    } else {
      $_SESSION["msg"] = '<div class="alert alert-warning msg"><strong>Warning:</strong> Could not insert record.</div>';
    }
  }
}
?>
<body>
  <!-- Side Navbar -->
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>

  <div class="page">
    <!-- Navbar -->
    <?php require_once("../assets/inc/admin-header.php"); ?>

    <section class="py-5">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card shadow-sm border-0">
              <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h3 class="h3 mb-0" style="color:#6E3B16;">
                  <i class="fas fa-university me-2"></i>
                  <?php echo htmlspecialchars($btnName); ?> Payment Method
                </h3>
                <a href="<?php echo htmlspecialchars($folderName); ?>" class="btn btn-sm"
                   style="background:#6E3B16;color:#fff;border-radius:20px;">
                  <i class="fas fa-arrow-left me-1"></i> Back
                </a>
              </div>

              <div class="card-body py-5">
                <?php if (!empty($_SESSION["msg"])) { echo $_SESSION["msg"]; unset($_SESSION["msg"]); } ?>

                <form method="post" class="row g-4" novalidate autocomplete="off">
                  <!-- Bank -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="bank">Bank</label>
                    <input type="text" class="form-control" id="bank" name="bank"
                           value="<?php echo htmlspecialchars($bank); ?>" required
                           placeholder="e.g., Meezan Bank">
                  </div>

                  <!-- Account Title -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="title">Account Title</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?php echo htmlspecialchars($title); ?>" required
                           placeholder="e.g., Foodies Pvt Ltd">
                  </div>

                  <!-- Account Number (TEXT) -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="no">Account / IBAN / No</label>
                    <input type="text" class="form-control" id="no" name="no"
                           value="<?php echo htmlspecialchars($no); ?>" required
                           placeholder="Enter account number (text)">
                  </div>

                  <!-- Contact (TEXT) -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="contact">Contact</label>
                    <input type="text" class="form-control" id="contact" name="contact"
                           value="<?php echo htmlspecialchars($contact); ?>"
                           placeholder="e.g., helpline / WhatsApp">
                  </div>

                  <div class="col-12 mt-2">
                    <button type="submit" name="btn-submit" class="btn px-4"
                            style="background-color:#6E3B16; color:#fff; border-radius:8px;">
                      <i class="fas fa-save me-1"></i>
                      <?php echo htmlspecialchars($btnName); ?> Record
                    </button>
                  </div>
                </form>
              </div>

            </div>
          </div>
        </div>
      </div>
    </section>

    <?php require_once("../assets/inc/admin-footer.php"); ?>
  </div>

  <?php require_once("../assets/inc/admin-bottom.php"); ?>
</body>
