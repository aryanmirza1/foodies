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

              <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h3 class="h3 mb-0" style="color:#6E3B16;">
                  <i class="fas fa-list me-2"></i>
                  <?php echo htmlspecialchars(ucfirst(str_replace("-", " ", $pageName))); ?>
                </h3>
                <a href="manage-<?php echo htmlspecialchars($pageName); ?>" class="btn btn-sm px-3"
                   style="background-color:#6E3B16;color:#fff;border-radius:20px;">
                  <i class="fas fa-plus me-1"></i> Add New
                </a>
              </div>

              <div class="card-body">
                <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                      <thead style="background-color:#6E3B16;color:#fff;">
                        <tr>
                          <th>#</th>
                          <th>Bank</th>
                          <th>Account Title</th>
                          <th>Account No</th>
                          <th>Contact</th>
                          <th>Created At</th>
                          <th>Status</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sr = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                          <?php
                            $id        = (int)($row["id"] ?? 0);
                            $bank      = htmlspecialchars($row["bank"] ?? "");
                            $title     = htmlspecialchars($row["title"] ?? "");
                            $no        = htmlspecialchars((string)($row["no"] ?? ""));
                            $contact   = htmlspecialchars((string)($row["contact"] ?? ""));
                            $status    = (int)($row["status"] ?? 0);
                            $createdAt = !empty($row["created_at"]) ? date("M d, Y H:i", strtotime($row["created_at"])) : "-";
                          ?>
                          <tr>
                            <td><?php echo $sr++; ?></td>
                            <td><strong><?php echo $bank; ?></strong></td>
                            <td><?php echo $title; ?></td>
                            <td class="text-monospace"><?php echo $no !== "" ? $no : "-"; ?></td>
                            <td class="text-monospace"><?php echo $contact !== "" ? $contact : "-"; ?></td>
                            <td class="text-muted small"><?php echo $createdAt; ?></td>

                            <td>
                              <?php if ($status === 1): ?>
                                <a href="?action=deactive&id=<?php echo $id; ?>"
                                   class="badge text-decoration-none py-2 px-3"
                                   style="background-color:#6E3B16;color:#fff;">
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
                                   style="background-color:#6E3B16;color:#fff;padding:6px 10px;"
                                   title="Edit">
                                  <i class="fas fa-edit"></i>
                                </a>
                                <a href="?action=delete&id=<?php echo $id; ?>"
                                   onclick="return confirm('Are you sure you want to delete this item?');"
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
                    <i class="fas fa-list-alt fa-3x" style="color:#6E3B16;opacity:.5;"></i>
                    <p class="text-muted mt-3 mb-0">No data available.</p>
                    <a href="manage-<?php echo htmlspecialchars($pageName); ?>" class="btn btn-sm mt-2"
                       style="background-color:#6E3B16;color:#fff;">
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

  <?php require_once("../assets/inc/admin-bottom.php"); ?>
</body>
