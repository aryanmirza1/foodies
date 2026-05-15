<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/tables.php");

$title = "";
$imagePath = ""; // stored in DB column `image`
$btnName = "Add";
$id = null;

if (isset($_GET["id"]) && $_GET["id"] != "" && $_GET["id"] > 0) {
  $btnName = "Update";
  $id = getSaveValue($conn, $_GET["id"]);
  $res = mysqli_query($conn, "SELECT * FROM $table WHERE id ='$id' LIMIT 1");
  if ($row = mysqli_fetch_assoc($res)) {
    $title     = $row["title"] ?? "";
    $imagePath = $row["image"]  ?? ""; // reuse `image` column to store image path
  }
}

if (isset($_POST["btn-submit"])) {
  $title = getSaveValue($conn, $_POST["title"] ?? "");

  // --- handle image upload (optional) ---
  $newImage = $imagePath; // default keep old on update

  if (!empty($_FILES['image']['name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
      // uploads folder (relative to this file). Change if you like.
      $uploadDir = "../assets/img/$folderName/$image";
      if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
      }
      $base = 'img_'.time().'_'.mt_rand(1000,9999).'.'.$ext;
      $dest = rtrim($uploadDir,'/').'/'.$base;

      if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        // path to save in DB relative to admin root
        $newImage = $dest;
      } else {
        $_SESSION["msg"] = '<div class="alert alert-warning msg"><strong>Warning:</strong> Image upload failed.</div>';
      }
    } else {
      $_SESSION["msg"] = '<div class="alert alert-warning msg"><strong>Warning:</strong> Invalid image type. Allowed: jpg, jpeg, png, webp.</div>';
    }
  }

  if (!empty($_GET["id"]) && (int)$_GET["id"] > 0 && $id) {
    // UPDATE (title + imagePath in `image`)
    $titleSql = mysqli_real_escape_string($conn, $title);
    $imgSql   = mysqli_real_escape_string($conn, $newImage);
    $res = mysqli_query($conn, "UPDATE $table SET title='$titleSql', image='$imgSql' WHERE id='$id'");
    if ($res) { $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success:</strong> Data Updated</div>'; header("location:$folderName"); exit(); }
    else      { echo '<div class="alert alert-warning msg"><strong>Warning:</strong> Data Not Updated</div>'; }
  } else {
    // INSERT
    $titleSql = mysqli_real_escape_string($conn, $title);
    $imgSql   = mysqli_real_escape_string($conn, $newImage);
    $res = mysqli_query($conn, "INSERT INTO $table (title, image) VALUES ('$titleSql', '$imgSql')");
    if ($res) { $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success:</strong> Data Inserted</div>'; header("location:$folderName"); exit(); }
    else      { echo '<div class="alert alert-warning msg"><strong>Warning:</strong> Data Not Inserted</div>'; }
  }
}
?>

<body>
  <!-- Side Navbar -->
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>

  <div class="page">
    <!-- Navbar -->
    <?php require_once("../assets/inc/admin-header.php"); ?>

    <!-- Form Section -->
    <section class="py-5">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card shadow-sm border-0">
              <!-- Header -->
              <div class="card-header bg-white border-bottom" style="border-color:#e0dcd7!important;">
                <h3 class="h3 mb-0" style="color:#6E3B16;">
                  <i class="fas fa-cogs me-2"></i>
                  <?php echo htmlspecialchars($btnName); ?> <?php echo htmlspecialchars(ucfirst(str_replace("-", " ", $folderName))); ?>
                </h3>
              </div>

              <div class="card-body py-5">
                <form method="post" enctype="multipart/form-data" class="row g-4" novalidate autocomplete="off">
                  
                  <!-- Title -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?php echo htmlspecialchars($title); ?>" required placeholder="Enter Title">
                  </div>

                  <!-- Image Upload -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="image">Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted d-block mt-1">Allowed: JPG, PNG, WEBP. Optional but recommended.</small>
                  </div>

                  <!-- Preview (existing or selected) -->
                  <div class="col-12">
                    <div class="d-flex align-items-center gap-3">
                      <div>
                        <div class="small text-muted mb-1">Preview</div>
                        <img id="imgPreview"
                             src="<?php echo $imagePath ? htmlspecialchars($imagePath) : 'https://via.placeholder.com/120x90?text=No+Image'; ?>"
                             alt="Preview"
                             style="width: 160px; height: 120px; object-fit: cover; border:1px solid #eee; border-radius:8px;">
                      </div>
                      <?php if ($imagePath): ?>
                        <div class="small text-muted">Current file:<br><span class="text-break"><?php echo htmlspecialchars($imagePath); ?></span></div>
                      <?php endif; ?>
                    </div>
                  </div>

                  <!-- Submit -->
                  <div class="col-12 mt-4">
                    <button type="submit" name="btn-submit" class="btn px-4"
                            style="background-color:#6E3B16; color:white; border-radius:8px;">
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

    <!-- Footer -->
    <?php require_once("../assets/inc/admin-footer.php"); ?>
  </div>

  <!-- JavaScript -->
  <?php require_once("../assets/inc/admin-bottom.php"); ?>

  <!-- Live preview for selected image -->
  <script>
    const fileInput = document.getElementById('image');
    const preview   = document.getElementById('imgPreview');

    if (fileInput && preview) {
      fileInput.addEventListener('change', () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        const url = URL.createObjectURL(file);
        preview.src = url;
        preview.onload = () => URL.revokeObjectURL(url);
      });
    }
  </script>
</body>
