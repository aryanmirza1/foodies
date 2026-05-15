<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/tables.php");
if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 1) { 

$name = "";
$username = "";
$password = "";   // hashed or empty placeholder on update
$image = "";
$role = "";
$btnName = "Add";
$required = "required";
$id = null;

// If editing, enforce self-edit for Manager (role=2), then load record
if (!empty($_GET["id"]) && (int)$_GET["id"] > 0) {
  if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 2) {
    if ((int)$_GET["id"] !== (int)$_SESSION["ADMIN_LOGIN"]["ADMIN_ID"]) {
      $_SESSION["msg"] = '<div class="alert alert-danger msg"><strong>Warning</strong> Record Not Exist.</div>';
      header("location:dashboard"); exit();
    }
  }

  $required = "";
  $btnName = "Update";
  $id = getSaveValue($conn, $_GET["id"]);
  $res = mysqli_query($conn, "SELECT * FROM $table WHERE id ='$id' LIMIT 1");
  if ($row = mysqli_fetch_assoc($res)) {
    $name     = $row["name"]     ?? "";
    $username    = $row["username"]    ?? "";
    $password = $row["password"] ?? ""; // keep hashed in hidden field
    $image    = $row["image"]    ?? "";
    $role     = $row["role"]     ?? "";
  }
}

if (isset($_POST["btn-submit"])) {
  $name  = getSaveValue($conn, $_POST["name"]  ?? "");
  $username = getSaveValue($conn, $_POST["username"] ?? "");
  $role  = getSaveValue($conn, $_POST["role"]  ?? $role); // if Manager edits self, no role select shown

  // Password handling
  $rawPassword = getSaveValue($conn, $_POST["password"] ?? "");
  if (!empty($_GET["id"]) && (int)$_GET["id"] > 0 && $id) {
    if ($rawPassword === "") {
      $password = getSaveValue($conn, $_POST["old_password"] ?? ""); // keep existing hash
    } else {
      $password = password_hash($rawPassword, PASSWORD_DEFAULT);
    }
  } else {
    $password = password_hash($rawPassword, PASSWORD_DEFAULT);
  }

  // Image handling
  if (empty($_FILES["image"]["name"])) {
    $image = (!empty($_GET["id"]) && (int)$_GET["id"] > 0) ? ($_POST["old_image"] ?? "dummy.jpg") : "dummy.jpg";
  } else {
    $safeName = preg_replace("/[^A-Za-z0-9._-]/", "_", $_FILES["image"]["name"]);
    $image = time() . "_" . $safeName;
    $imageTmp = $_FILES["image"]["tmp_name"];
    @move_uploaded_file($imageTmp, "../assets/img/$folderName/$image");
  }

  // Update / Insert
  if (!empty($_GET["id"]) && (int)$_GET["id"] > 0 && $id) {
    $res = mysqli_query($conn, "UPDATE $table 
                                SET name='$name', username='$username', password='$password', role='$role', image='$image'
                                WHERE id ='$id'");
    if ($res) {
      if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 2) { $folderName = "dashboard"; }
      $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success</strong> Data Updated</div>';
      header("location:$folderName"); exit();
    } else {
      echo '<div class="alert alert-warning msg"><strong>Warning</strong> Data Not Updated</div>';
    }
  } else {
    $res = mysqli_query($conn, "INSERT INTO $table (name,username,password,image,role) 
                                VALUES ('$name','$username','$password','$image','$role')");
    if ($res) {
      $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success</strong> Data Inserted</div>';
      header("location:$folderName"); exit();
    } else {
      echo '<div class="alert alert-warning msg"><strong>Warning</strong> Data Not Inserted</div>';
    }
  }
}
?>

<body>
  <!-- Side Navbar -->
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>

  <div class="page">
    <!-- navbar -->
    <?php require_once("../assets/inc/admin-header.php"); ?>

    <section class="py-5">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card shadow-sm border-0">
              <!-- Header (brand style) -->
              <div class="card-header bg-white border-bottom" style="border-color:#e0dcd7!important;">
                <h3 class="h3 mb-0" style="color:#6E3B16;">
                  <i class="fas fa-cogs me-2"></i>
                  <?php echo htmlspecialchars($btnName); ?> <?php echo htmlspecialchars(ucfirst(str_replace("-", " ", $folderName))); ?>
                </h3>
              </div>

              <div class="card-body py-5">
                <form method="post" enctype="multipart/form-data" class="row g-4" novalidate autocomplete="off">
                  <!-- Name -->
                  <div class="col-md-4">
                    <label class="form-label text-muted small" for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?php echo htmlspecialchars($name); ?>" required placeholder="Enter Name">
                  </div>

                  <!-- username -->
                  <div class="col-md-4">
                    <label class="form-label text-muted small" for="username">Email</label>
                    <input type="email" class="form-control" id="username" name="username"
                           value="<?php echo htmlspecialchars($username); ?>" required placeholder="Enter username">
                  </div>

                  <!-- Password (blank = keep old when updating) -->
                  <div class="col-md-4">
                    <label class="form-label text-muted small" for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           <?php echo $required; ?> placeholder="<?php echo ($btnName === 'Update') ? 'Leave blank to keep existing' : 'Enter Password'; ?>">
                    <input type="hidden" name="old_password" value="<?php echo htmlspecialchars($password); ?>">
                  </div>

                  <!-- Image + preview -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="image">Profile Image</label>
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($image); ?>">
                    <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted d-block mt-1">Accepted: JPG, PNG, WEBP. Max ~2–3MB recommended.</small>

                    <div class="mt-3">
                      <?php if (!empty($image)): ?>
                        <img id="preview" src="../assets/img/<?php echo htmlspecialchars($folderName); ?>/<?php echo htmlspecialchars($image); ?>"
                             alt="Preview" style="width:110px; height:110px; object-fit:cover; border-radius:8px; border:1px solid #eee;">
                      <?php else: ?>
                        <img id="preview" src="../assets/img/<?php echo htmlspecialchars($folderName); ?>/dummy.jpg"
                             alt="Preview" style="width:110px; height:110px; object-fit:cover; border-radius:8px; border:1px solid #eee;">
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Role (only visible to Admin role=1) -->
                  <?php if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 1): ?>
                    <div class="col-md-6">
                      <label class="form-label text-muted small" for="role">Role</label>
                      <select name="role" id="role" class="form-select">
                        <option value="1" <?php echo ((int)$role === 1) ? "selected" : ""; ?>>Admin</option>
                        <option value="2" <?php echo ((int)$role === 2) ? "selected" : ""; ?>>Manager</option>
                      </select>
                    </div>
                  <?php else: ?>
                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($role ?: 2); ?>">
                  <?php endif; ?>

                  <!-- Submit -->
                  <div class="col-12 mt-4">
                    <button type="submit" name="btn-submit" class="btn px-4"
                            style="background-color:#6E3B16; color:#fff; border-radius:8px;">
                      <i class="fas fa-save me-1"></i> <?php echo htmlspecialchars($btnName); ?> Record
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

  <!-- JavaScript files -->
  <?php require_once("../assets/inc/admin-bottom.php"); ?>

  <script>
    // Live image preview
    (function () {
      const input = document.getElementById('image');
      const preview = document.getElementById('preview');
      if (!input || !preview) return;

      input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
      });
    })();
  </script>
</body>

                  <?php } 
                  else{
      header("location:dashboard"); exit();

                  }?>
