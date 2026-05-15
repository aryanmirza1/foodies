<?php
require_once("../assets/inc/admin-top.php");
require_once("../assets/inc/getData.php");
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
              <!-- Card Header with Brand Color Accent -->
              <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3" >
                <h3 class="h3 mb-0" style="color: #6E3B16;">
                  <i class="fas fa-comments me-2"></i>
                  <?php echo ucfirst(str_replace("-", " ", $pageName)); ?>
                </h3>
                <a href="manage-<?php echo $pageName; ?>" class="btn btn-sm px-3" 
                   style="background-color: #6E3B16; color: white; border: none; border-radius: 20px;">
                  <i class="fas fa-plus me-1"></i> Add New
                </a>
              </div>

              <div class="card-body">
                <?php if (mysqli_num_rows($result) > 0): ?>
                  <div class="table-responsive rounded">
                    <table class="table table-hover align-middle mb-0">
                      <thead>
                        <tr style="background-color: #6E3B16; color: white;">
                          <th>#</th>
                          <th>Name</th>
                          <th>Profession</th>
                          <th>Rating</th>
                          <th>Review</th>
                          <th>Date</th>
                          <th>User Icon</th>
                          <th>Created At</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sr = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                          <tr>
                            <td><?php echo $sr++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row["name"]); ?></strong></td>
                            <td><?php echo htmlspecialchars($row["profession"] ?? 'N/A'); ?></td>
                            <td>
                              <span class="badge text-dark">
                                ⭐ <?php echo number_format($row["rating"], 1); ?>
                              </span>
                            </td>
                            <td class="text-truncate" style="max-width: 200px;">
                              <?php 
                                echo strlen($row["review"]) > 80 
                                  ? substr(htmlspecialchars($row["review"]), 0, 80) . '...' 
                                  : htmlspecialchars($row["review"]); 
                              ?>
                            </td>
                            <td><?php echo date("M d, Y", strtotime($row["date"])); ?></td>
                            <td>
                              <?php 
                                $imgPath = "../assets/img/{$folderName}/{$row['image']}";
                                $src = file_exists($imgPath) ? $imgPath : '../assets/img/default-user.png';
                              ?>
                              <img 
                                src="<?php echo $src; ?>" 
                                alt="User Icon" 
                                class="rounded-circle" 
                                width="40" 
                                height="40" 
                                style="object-fit: cover;"
                              >
                            </td>
                            <td class="text-muted small">
                              <?php echo date("M d, Y H:i", strtotime($row["created_at"])); ?>
                            </td>
                            <td>
                              <?php if ($row["status"] == 1): ?>
                                <a href="?action=deactive&id=<?php echo $row["id"]; ?>" 
                                   class="badge text-decoration-none py-2 px-3" 
                                   style="background-color: #6E3B16; color: white;">
                                  Active
                                </a>
                              <?php else: ?>
                                <a href="?action=active&id=<?php echo $row["id"]; ?>" 
                                   class="badge bg-secondary text-decoration-none py-2 px-3">
                                  Inactive
                                </a>
                              <?php endif; ?>
                            </td>
                            <td>
                              <div class="btn-group" role="group">
                                <a 
                                  href="manage-<?php echo $pageName; ?>?id=<?php echo $row["id"]; ?>" 
                                  class="btn btn-sm" 
                                  style="background-color: #6E3B16; color: white; padding: 6px 10px;"
                                  title="Edit">
                                  <i class="fas fa-edit"></i>
                                </a>
                                <a 
                                  href="?action=delete&id=<?php echo $row["id"]; ?>" 
                                  onclick="return confirm('Are you sure you want to delete this review?');" 
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
                    <i class="fas fa-comments fa-3x" style="color: #6E3B16; opacity: 0.5;"></i>
                    <p class="text-muted mt-3 mb-0">No data available.</p>
                    <a href="manage-<?php echo $pageName; ?>" class="btn btn-sm mt-2" 
                       style="background-color: #6E3B16; color: white;">
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
</body>
</html>