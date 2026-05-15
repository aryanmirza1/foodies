<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/getData.php");

if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 2) {
  $_SESSION["msg"] = '<div class="alert alert-danger msg"><strong>Warning</strong> Record Not Exist.</div>';
  header("location:dashboard"); exit();
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
        <div class="row">
          <div class="col-lg-12">
            <div class="card shadow-sm border-0">

              <!-- Card Header with Brand Accent -->
              <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h3 class="h3 mb-0" style="color:#6E3B16;">
                  <i class="fas fa-list me-2"></i>
                  <?php echo htmlspecialchars(ucfirst(str_replace("-", " ", $pageName))); ?>
                </h3>
                <a href="manage-<?php echo htmlspecialchars($pageName); ?>" class="btn btn-sm px-3"
                   style="background-color:#6E3B16; color:#fff; border-radius:20px;">
                  <i class="fas fa-plus me-1"></i> Add New
                </a>
              </div>

              <div class="card-body">
                <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-capitalize">
                      <thead style="background-color:#6E3B16; color:#fff;">
                        <tr>
                          <th>#</th>
                          <th>ID</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Password</th>
                          <th>Image</th>
                          <th>Role</th> 
                          <th>Created At</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sr=1; while($row=mysqli_fetch_assoc($result)): ?>
                          <?php
                            $id        = (int)$row["id"];
                            $name      = htmlspecialchars($row["name"] ?? "");
                            $username     = htmlspecialchars($row["username"] ?? "");
                            $password  = htmlspecialchars($row["password"] ?? "");
                            $image     = htmlspecialchars($row["image"] ?? "");
                            $role      = (int)($row["role"] ?? 2);
                            $status    = (int)($row["status"] ?? 0);
                            $createdAt = !empty($row["created_at"]) ? date("M d, Y H:i", strtotime($row["created_at"])) : "";
                            $imgPath   = "../assets/img/".htmlspecialchars($folderName)."/".($image ?: "dummy.jpg");
                          ?>
                          <tr>
                            <td><?php echo $sr++; ?></td>
                            <td><span class="badge bg-secondary"><?php echo $id; ?></span></td>
                            <td><strong><?php echo $name; ?></strong></td>
                            <td><?php echo $username; ?></td>

                            <!-- Masked password with tooltip showing full hash -->
                            <td>
                              <span class="text-monospace"
                                    data-bs-toggle="tooltip"
                                    title="<?php echo $password; ?>">
                                ••••••••
                              </span>
                            </td>

                            <td>
                              <img src="<?php echo $imgPath; ?>"
                                   alt="<?php echo $name; ?>"
                                   style="width:42px; height:42px; object-fit:cover; border-radius:50%; border:1px solid #eee;">
                            </td>

                            <td>
                              <?php if ($role === 1): ?>
                                <a href="?action=manager&id=<?php echo $id; ?>"
                                   class="badge text-decoration-none py-2 px-3"
                                   style="background-color:#6E3B16; color:#fff;">
                                  Admin
                                </a>
                              <?php else: ?>
                                <a href="?action=admin&id=<?php echo $id; ?>"
                                   class="badge bg-warning text-dark text-decoration-none py-2 px-3">
                                  Manager
                                </a>
                              <?php endif; ?>
                            </td>

                            <td class="text-muted small"><?php echo $createdAt; ?></td>

                            <td>
                              <?php if ($status === 1): ?>
                                <a href="?action=deactive&id=<?php echo $id; ?>"
                                   class="badge text-decoration-none py-2 px-3"
                                   style="background-color:#6E3B16; color:#fff;">
                                  Active
                                </a>
                              <?php else: ?>
                                <a href="?action=active&id=<?php echo $id; ?>"
                                   class="badge bg-secondary text-decoration-none py-2 px-3">
                                  Inactive
                                </a>
                              <?php endif; ?>
                            </td>

                            <td>
                              <div class="btn-group" role="group">
                                <a href="manage-<?php echo htmlspecialchars($pageName); ?>?id=<?php echo $id; ?>"
                                   class="btn btn-sm"
                                   style="background-color:#6E3B16; color:#fff; padding:6px 10px;"
                                   title="Edit">
                                  <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $id; ?>"
                                   onclick="return confirm('Are you sure you want to delete this user?');"
                                   class="btn btn-sm btn-outline-danger"
                                   title="Delete">
                                  <i class="fas fa-trash-alt"></i>
                                </a>
                              </div>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-center py-5">
                    <i class="fas fa-users fa-3x" style="color:#6E3B16; opacity:0.5;"></i>
                    <p class="text-muted mt-3 mb-0">No users found.</p>
                    <a href="manage-<?php echo htmlspecialchars($pageName); ?>" class="btn btn-sm mt-2"
                       style="background-color:#6E3B16; color:#fff;">
                      <i class="fas fa-plus"></i> Add One
                    </a>
                  </div>
                <?php endif; ?>
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

  <!-- Enable Bootstrap tooltips for password hover -->
  <script>
    (function() {
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        if (window.bootstrap && bootstrap.Tooltip) {
          new bootstrap.Tooltip(tooltipTriggerEl);
        }
      });
    })();
  </script>
</body>
