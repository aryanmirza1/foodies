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

              <!-- Card Header (brand style) -->
              <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <h3 class="h3 mb-0" style="color:#6E3B16;">
                  <i class="fas fa-list me-2"></i>
                  <?php echo htmlspecialchars(ucfirst(str_replace("-", " ", $pageName))); ?>
                </h3>
                <!-- No "Add New" for contact messages -->
              </div>

              <div class="card-body">
                <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-capitalize">
                      <thead style="background-color:#6E3B16; color:#fff;">
                        <tr>
                          <th>#</th>
                          <th>Name</th>
                          <th>Email</th>
                          <th>Contact No</th>
                          <th>Message</th>
                          <th>Created At</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $sr = 1; while ($row = mysqli_fetch_assoc($result)): ?>
                          <?php
                            $id        = (int)$row["id"];
                            $name      = htmlspecialchars($row["name"] ?? "");
                            $email     = htmlspecialchars($row["email"] ?? "");
                            $subject   = htmlspecialchars($row["subject"] ?? "");
                            $message   = htmlspecialchars($row["message"] ?? "");
                            $createdAt = !empty($row["created_at"]) ? date("M d, Y H:i", strtotime($row["created_at"])) : "";

                            // Truncated display
                            $subShort = (mb_strlen($subject) > 60) ? htmlspecialchars(mb_substr($subject, 0, 60))."…" : $subject;
                            $msgShort = (mb_strlen($message) > 120) ? htmlspecialchars(mb_substr($message, 0, 120))."…" : $message;
                          ?>
                          <tr>
                            <td><?php echo $sr++; ?></td>
                            <td><strong><?php echo $name; ?></strong></td>
                            <td class="text-dark text-lowercase">
                                <?php echo $email; ?>
                            </td>
                            <td class="text-truncate" style="max-width:260px;"
                                data-bs-toggle="tooltip" title="<?php echo $subject; ?>">
                              <?php echo $subShort; ?>
                            </td>
                            <td class="text-truncate" style="max-width:360px;"
                                data-bs-toggle="tooltip" title="<?php echo $message; ?>">
                              <?php echo $msgShort; ?>
                            </td>
                            <td class="text-muted small"><?php echo $createdAt; ?></td>
                            <td>
                              <div class="btn-group" role="group">
                                <a href="?action=delete&id=<?php echo $id; ?>"
                                   onclick="return confirm('Are you sure you want to delete this message?');"
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
                    <i class="fas fa-inbox fa-3x" style="color:#6E3B16; opacity:0.5;"></i>
                    <p class="text-muted mt-3 mb-0">No messages found.</p>
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

  <!-- Enable Bootstrap tooltips for subject/message hover -->
  <script>
    (function() {
      var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
      tooltipTriggerList.forEach(function (el) {
        if (window.bootstrap && bootstrap.Tooltip) {
          new bootstrap.Tooltip(el);
        }
      });
    })();
  </script>
</body>
