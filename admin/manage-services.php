<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/tables.php");

// Font Awesome food/service icons
$serviceIcons = [
  "fa-truck",             
  "fa-user",              
  "fa-utensils",          
  "fa-blender",           
  "fa-clipboard-list",    
  "fa-concierge-bell",    
  "fa-chair",             
  "fa-shopping-basket",   
  "fa-hands-helping",     
  "fa-cash-register",     
  "fa-store",             
  "fa-users",             
  "fa-box",               
  "fa-comments",          
  "fa-calendar-alt",      
  "fa-wine-glass-alt",    
  "fa-coffee",            
  "fa-birthday-cake",     
  "fa-handshake",         
  "fa-car",               
  "fa-wine-bottle",       
  "fa-cookie-bite",       
  "fa-leaf",              
  "fa-seedling",          
  "fa-phone",             
];

$title = "";
$description = "";
$icon = "";
$btnName = "Add";

if (isset($_GET["id"]) && $_GET["id"] != "" && $_GET["id"] > 0) {
  $btnName = "Update";
  $id = getSaveValue($conn, $_GET["id"]);
  $res = mysqli_query($conn, "SELECT * FROM $table WHERE id = '$id'");
  if ($row = mysqli_fetch_assoc($res)) {
    $title = $row["title"];
    $description = $row["description"];
    $icon = $row["icon"];
  }
}

if (isset($_POST["btn-submit"])) {
  $title = getSaveValue($conn, $_POST["title"]);
  $description = getSaveValue($conn, $_POST["description"]);
  $icon = getSaveValue($conn, $_POST["icon"]);

  if (isset($_GET["id"]) && $_GET["id"] > 0) {
    $res = mysqli_query($conn, "UPDATE $table SET title='$title', description='$description', icon='$icon' WHERE id='$id'");
  } else {
    $res = mysqli_query($conn, "INSERT INTO $table (title, description, icon) VALUES ('$title', '$description', '$icon')");
  }

  if ($res) {
    $_SESSION["msg"] = '<div class="alert alert-success msg"><strong>Success:</strong> Data ' . ($btnName == "Update" ? "Updated" : "Inserted") . '</div>';
    header("Location: $folderName");
    exit();
  } else {
    echo '<div class="alert alert-warning msg"><strong>Warning:</strong> Operation Failed</div>';
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
                  <i class="fas fa-cogs me-2"></i>
                  <?php echo $btnName ?> <?php echo ucfirst(str_replace("-", " ", $folderName)); ?>
                </h3>
              </div>

              <div class="card-body py-5">
                <form method="post" enctype="multipart/form-data" class="row g-4" novalidate>
                  
                  <!-- Title -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="title">Service Title</label>
                    <input type="text" 
                           class="form-control" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($title); ?>" 
                           required 
                           placeholder="e.g. Home Delivery">
                  </div>

                  <!-- Description -->
                  <div class="col-md-6">
                    <label class="form-label text-muted small" for="description">Description</label>
                    <input type="text" 
                           class="form-control" 
                           id="description" 
                           name="description" 
                           value="<?php echo htmlspecialchars($description); ?>" 
                           placeholder="Brief description (optional)">
                  </div>

                  <!-- Icon Selector -->
                  <div class="col-md-12">
                    <label class="form-label text-muted small" for="icon">Choose Icon</label>
                    <div class="dropdown">
                      <button class="btn btn-light dropdown-toggle w-100 text-start" 
                              type="button" 
                              id="iconDropdown" 
                              data-bs-toggle="dropdown" 
                              aria-expanded="false" 
                              style="border: 1px solid #ddd;">
                        <?php if ($icon): ?>
                          <i class="fa <?php echo $icon; ?> me-2" style="color: #6E3B16;"></i>
                          <?php echo ucfirst(str_replace("fa-", "", $icon)); ?>
                        <?php else: ?>
                          <span class="text-muted">Select an icon...</span>
                        <?php endif; ?>
                      </button>

                      <!-- Scrollable Icon Grid -->
                      <div class="dropdown-menu w-100 p-3" style="max-height: 280px; overflow-y: auto;">
                        <div class="d-flex flex-wrap gap-2">
                          <?php foreach ($serviceIcons as $svcIcon): ?>
                            <a class="dropdown-item p-2 text-center rounded" 
                               href="#" 
                               data-value="<?php echo $svcIcon; ?>" 
                               style="width: 75px; border: 1px solid #eee; background-color: #fdfcfc;">
                              <i class="fa <?php echo $svcIcon; ?> fa-lg" 
                                 style="color: #6E3B16;"></i>
                              <div class="small text-muted mt-1">
                                <?php echo ucfirst(str_replace(["fa-", "-"], ["", " "], $svcIcon)); ?>
                              </div>
                            </a>
                          <?php endforeach; ?>
                        </div>
                      </div>

                      <!-- Hidden Input -->
                      <input type="hidden" id="icon" name="icon" value="<?php echo htmlspecialchars($icon); ?>">
                    </div>
                    <small class="text-muted">Select an icon to represent this service.</small>
                  </div>

                  <!-- Submit Button -->
                  <div class="col-12 mt-4">
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

  <!-- Icon Selector Script -->
  <script>
    document.querySelectorAll('.dropdown-item').forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();
        const value = this.getAttribute('data-value');
        const label = value.replace('fa-', '').replace(/-/g, ' ');
        const capitalized = label.charAt(0).toUpperCase() + label.slice(1);

        // Update button
        document.getElementById('iconDropdown').innerHTML = `
          <i class="fa ${value} me-2" style="color: #6E3B16;"></i>
          ${capitalized}
        `;
        // Update hidden input
        document.getElementById('icon').value = value;
      });
    });
  </script>
</body>
</html>