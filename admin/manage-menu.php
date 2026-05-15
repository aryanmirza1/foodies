<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/tables.php");

$title       = "";
$category_id = "";
$imageName   = "";  // DB stores just the filename (works with your list page)
$description = "";
$price       = "";
$btnName     = "Add";
$id          = null;

if (isset($_GET["id"]) && $_GET["id"] != "" && (int)$_GET["id"] > 0) {
  $btnName = "Update";
  $id      = getSaveValue($conn, $_GET["id"]);
  $res     = mysqli_query($conn, "SELECT * FROM $table WHERE id ='$id' LIMIT 1");
  if ($row = mysqli_fetch_assoc($res)) {
    $title       = $row["title"]        ?? "";
    $category_id = $row["category_id"]  ?? "";
    $imageName   = $row["image"]        ?? "";
    $description = $row["description"]  ?? "";
    $price       = $row["price"]        ?? "";
  }
}

if (isset($_POST["btn-submit"])) {
  $title       = getSaveValue($conn, $_POST["title"] ?? "");
  $category_id = getSaveValue($conn, $_POST["category_id"] ?? "");
  $description = getSaveValue($conn, $_POST["description"] ?? "");
  $price       = getSaveValue($conn, $_POST["price"] ?? "");

  // ---- image upload (optional) ----
  $newImage = $imageName; // keep existing by default on update

  if (!empty($_FILES["image"]["name"]) && is_uploaded_file($_FILES["image"]["tmp_name"])) {
    $allowed = ['jpg','jpeg','png','webp'];
    $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
      $uploadDir = "../assets/img/{$folderName}"; // keep your existing structure
      if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

      $base = time() . "_" . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $_FILES["image"]["name"]);
      $dest = rtrim($uploadDir, '/').'/'.$base;

      if (move_uploaded_file($_FILES["image"]["tmp_name"], $dest)) {
        $newImage = $base; // store only filename in DB
      } else {
        $_SESSION["msg"] = '<div class="alert alert-warning msg"><strong>Warning:</strong> Image upload failed.</div>';
      }
    } else {
      $_SESSION["msg"] = '<div class="alert alert-warning msg"><strong>Warning:</strong> Invalid image type. Allowed: jpg, jpeg, png, webp.</div>';
    }
  } else {
    // no new upload
    if (!empty($_GET["id"]) && (int)$_GET["id"] > 0) {
      // keep old image from hidden field
      $newImage = $_POST["old_image"] ?? $imageName;
    } else {
      // new record without upload
      $newImage = "dummy.jpg";
    }
  }

  // ---- Insert / Update ----
  if (!empty($_GET["id"]) && (int)$_GET["id"] > 0 && $id) {
    $sql = sprintf(
      "UPDATE %s SET title='%s', category_id='%s', image='%s', description='%s', price='%s' WHERE id='%d'",
      $table,
      mysqli_real_escape_string($conn, $title),
      mysqli_real_escape_string($conn, $category_id),
      mysqli_real_escape_string($conn, $newImage),
      mysqli_real_escape_string($conn, $description),
      mysqli_real_escape_string($conn, $price),
      (int)$id
    );
    $res = mysqli_query($conn, $sql);
    $_SESSION["msg"] = $res
      ? '<div class="alert alert-success msg"><strong>Success:</strong> Data Updated</div>'
      : '<div class="alert alert-warning msg"><strong>Warning:</strong> Data Not Updated</div>';
    header("location:$folderName"); exit();
  } else {
    $sql = sprintf(
      "INSERT INTO %s (title, category_id, image, description, price) VALUES ('%s','%s','%s','%s','%s')",
      $table,
      mysqli_real_escape_string($conn, $title),
      mysqli_real_escape_string($conn, $category_id),
      mysqli_real_escape_string($conn, $newImage),
      mysqli_real_escape_string($conn, $description),
      mysqli_real_escape_string($conn, $price)
    );
    $res = mysqli_query($conn, $sql);
    $_SESSION["msg"] = $res
      ? '<div class="alert alert-success msg"><strong>Success:</strong> Data Inserted</div>'
      : '<div class="alert alert-warning msg"><strong>Warning:</strong> Data Not Inserted</div>';
    header("location:$folderName"); exit();
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
                <form method="post" enctype="multipart/form-data" class="row g-4 needs-validation" novalidate autocomplete="off">

                  <!-- Title -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="title">Title</label>
                    <input type="text" class="form-control" id="title" name="title"
                           value="<?php echo htmlspecialchars($title); ?>" required placeholder="Enter Title">
                    <div class="invalid-feedback">Title is required.</div>
                  </div>

                  <!-- Category -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="category_id">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                      <option value="" disabled <?php echo $category_id===''?'selected':''; ?>>Select Category</option>
                      <?php
                        $categoryResult = mysqli_query($conn, "SELECT id, title FROM categories ORDER BY title ASC");
                        while ($category = mysqli_fetch_assoc($categoryResult)) {
                          $cid = (int)$category['id'];
                          $sel = ($cid == (int)$category_id) ? 'selected' : '';
                          echo '<option value="'.$cid.'" '.$sel.'>'.htmlspecialchars($category['title']).'</option>';
                        }
                      ?>
                    </select>
                    <div class="invalid-feedback">Please select a category.</div>
                  </div>

                  <!-- Image Upload -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="image">Image</label>
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($imageName); ?>">
                    <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
                    <small class="text-muted d-block mt-1">Allowed: JPG, PNG, WEBP. Optional.</small>
                  </div>

                  <!-- Image Preview -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small d-block">Preview</label>
                    <?php
                      $currentPath = $imageName
                        ? "../assets/img/".htmlspecialchars($folderName)."/".htmlspecialchars($imageName)
                        : "https://via.placeholder.com/160x120?text=No+Image";
                    ?>
                    <img id="imgPreview"
                         src="<?php echo $currentPath; ?>"
                         alt="Preview"
                         style="width:160px;height:120px;object-fit:cover;border:1px solid #eee;border-radius:8px;">
                    <?php if ($imageName): ?>
                      <div class="small text-muted mt-2">Current file: <span class="text-break"><?php echo htmlspecialchars($imageName); ?></span></div>
                    <?php endif; ?>
                  </div>

                  <!-- Description -->
                  <div class="col-12">
                    <label class="form-label text-muted small" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"
                              placeholder="Enter description"><?php echo htmlspecialchars($description); ?></textarea>
                  </div>

                  <!-- Price -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="price">Price</label>
                    <input type="number" step="0.01" class="form-control" id="price"
                           name="price" value="<?php echo htmlspecialchars($price); ?>" required placeholder="Enter price">
                    <div class="invalid-feedback">Price is required.</div>
                  </div>

                  <!-- Submit -->
                  <div class="col-12 mt-3">
                    <button type="submit" name="btn-submit" class="btn px-4"
                            style="background-color:#6E3B16; color:#fff; border-radius:8px;">
                      <i class="fas fa-save me-1"></i> <?php echo htmlspecialchars($btnName); ?> Menu Item
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

  <!-- Form validation + live image preview -->
  <script>
    (function () {
      const form = document.querySelector('.needs-validation');
      if (form) {
        form.addEventListener('submit', function (e) {
          if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
          }
          form.classList.add('was-validated');
        });
      }

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
    })();
  </script>
</body>
