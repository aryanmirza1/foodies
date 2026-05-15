<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/tables.php");

// Initialize variables
$image = "";
$name = "";
$profession = "";
$rating = "";
$review = "";
$date = "";
$btnName = "Add";
$required = "required";

// Edit Mode: Load existing data
if (isset($_GET["id"]) && $_GET["id"] != "" && $_GET["id"] > 0) {
  $btnName = "Update";
  $required = "";
  $id = getSaveValue($conn, $_GET["id"]);
  $res = mysqli_query($conn, "SELECT * FROM $table WHERE id = '$id'");
  if ($row = mysqli_fetch_array($res)) {
    $name = $row["name"];
    $image = $row["image"];
    $profession = $row["profession"];
    $rating = $row["rating"];
    $review = $row["review"];
    $date = $row["date"];
  }
}

// Form Submission
if (isset($_POST["btn-submit"])) {
  $name = getSaveValue($conn, $_POST["name"]);
  $profession = getSaveValue($conn, $_POST["profession"]);
  $rating = getSaveValue($conn, $_POST["rating"]);
  $review = getSaveValue($conn, $_POST["review"]);
  $date = getSaveValue($conn, $_POST["date"]);

  // Handle image upload
  if (!empty($_FILES["image"]["name"])) {
    $image = time() . "_" . basename($_FILES["image"]["name"]);
    $imageTmp = $_FILES["image"]["tmp_name"];
    $uploadPath = "../assets/img/$folderName/$image";
    move_uploaded_file($imageTmp, $uploadPath);
  } else {
    $image = isset($_POST["old_image"]) ? $_POST["old_image"] : "dummy.jpg";
  }

  // Insert or Update
  if (isset($_GET["id"]) && $_GET["id"] > 0) {
    $res = mysqli_query($conn, "UPDATE $table SET name='$name', image='$image', profession='$profession', date='$date', review='$review', rating='$rating' WHERE id='$id'");
  } else {
    $res = mysqli_query($conn, "INSERT INTO $table (name, image, profession, date, review, rating) VALUES ('$name', '$image', '$profession', '$date', '$review', '$rating')");
  }

  if ($res) {
    $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success</strong> Data ' . ($btnName == "Update" ? "Updated" : "Inserted") . '</div>';
    header("Location: $folderName");
    exit();
  } else {
    echo '<div class="alert alert-warning msg"><strong>Warning</strong> Operation Failed</div>';
  }
}
?>

<body>
  <!-- Side Navbar -->
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>

  <div class="page">
    <!-- Navbar -->
    <?php require_once("../assets/inc/admin-header.php"); ?>

    <!-- Main Form Section -->
    <section class="py-5">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card shadow-sm border-0">
              <!-- Header -->
              <div class="card-header bg-white border-bottom" style="border-color: #e0dcd7 !important;">
                <h3 class="h3 mb-0" style="color: #6E3B16;">
                  <i class="fas fa-edit me-2"></i>
                  <?php echo $btnName ?> <?php echo ucfirst(str_replace("-", " ", $folderName)); ?>
                </h3>
              </div>

              <div class="card-body">
                <form method="post" enctype="multipart/form-data" class="row g-4" novalidate>
                  
                  <!-- Name -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="name">Name</label>
                    <input type="text" 
                           class="form-control" 
                           id="name" 
                           name="name" 
                           value="<?php echo htmlspecialchars($name); ?>" 
                           <?php echo $required; ?> 
                           placeholder="Enter full name">
                  </div>

                  <!-- Profession -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="profession">Profession</label>
                    <input type="text" 
                           class="form-control" 
                           id="profession" 
                           name="profession" 
                           value="<?php echo htmlspecialchars($profession); ?>" 
                           <?php echo $required; ?> 
                           placeholder="e.g. Chef, Customer">
                  </div>

                  <!-- Date -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="date">Review Date</label>
                    <input type="date" 
                           class="form-control" 
                           id="date" 
                           name="date" 
                           value="<?php echo $date; ?>" 
                           <?php echo $required; ?>>
                  </div>

                  <!-- Rating -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="rating">Rating</label>
                    <select name="rating" id="rating" class="form-control">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($rating == $i) ? "selected" : ""; ?>>
                          <?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?>
                        </option>
                      <?php endfor; ?>
                    </select>
                  </div>

                  <!-- Image Upload -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="image">User Icon</label>
                    <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($image); ?>">
                    <input type="file" 
                           class="form-control" 
                           id="image" 
                           name="image" 
                           <?php echo $required; ?> 
                           accept=".jpg,.png,.jpeg">
                    <small class="text-muted">JPG/PNG only, max 2MB</small>
                  </div>

                  <!-- Review Textarea -->
                  <div class="col-12">
                    <label class="form-label text-muted small" for="review">Review</label>
                    <textarea class="form-control" 
                              id="review" 
                              name="review" 
                              rows="5" 
                              <?php echo $required; ?> 
                              placeholder="Write a detailed review..."><?php echo htmlspecialchars($review); ?></textarea>
                  </div>

                  <!-- Submit Button -->
                  <div class="col-12 mt-3">
                    <button type="submit" 
                            name="btn-submit" 
                            class="btn px-4" 
                            style="background-color: #6E3B16; color: white; border-radius: 8px;">
                      <i class="fas fa-save me-1"></i> <?php echo $btnName ?> Record
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

  <!-- JavaScript -->
  <?php require_once("../assets/inc/admin-bottom.php"); ?>

  <!-- Optional: Image preview script -->
  <script>
    document.getElementById('image').onchange = function (e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
          // Optional: Show preview
          // document.getElementById('preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
      }
    };
  </script>
</body>
</html>