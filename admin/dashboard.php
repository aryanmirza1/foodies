<?php require_once("../assets/inc/admin-top.php");
$pageName = basename($_SERVER["PHP_SELF"], '.php');


if (isset($_SESSION["msg"])) {
  echo $_SESSION["msg"];
  unset($_SESSION["msg"]);
}
// --- helpers (put near the top of the dashboard file or above this card) ---
function count_new_orders(mysqli $conn): int
{
  $sql = "SELECT COUNT(*) FROM `order` WHERE status IN ('new','pending')";
  $rs  = mysqli_query($conn, $sql);
  $row = $rs ? mysqli_fetch_row($rs) : [0];
  return (int)($row[0] ?? 0);
}
$__newOrders = count_new_orders($conn);
?>
<style>
  .fa {
    font-size: 30px !important;
  }

  .text-muted {
    font-size: 15px;
  }

  .card:hover {
    box-shadow: 0px 1px 8px -2px black !important;
  }

  .badge {
    font-weight: 600;
    font-size: 1.2em;
  }
</style>

<body>
  <!-- Side Navbar -->
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>

  <div class="page">
    <!-- navbar-->
    <?php require_once("../assets/inc/admin-header.php"); ?>
    <!-- Counts Section -->
    <!-- Statistics Section-->
    <section class="py-4">
      <div class="container-fluid">
        <div class="row g-3">

          <!-- Menu -->
          <a href="menu" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Menu</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'menu', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'menu', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-list-ul fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>
          <!-- New Orders / Payment Review -->
          <a href="payments-review.php" id="newOrdersLink" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100 position-relative">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">New Orders</h1>
                    <div class="text-muted small">Awaiting admin review (COD & Online)</div>
                    <div class="fs-4 fw-bold mt-2 text-danger">
                      <span id="newOrdersCount "><?= $__newOrders ?></span>
                    </div>
                  </div>

                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm position-relative"
                    style="width:100px;height:100px;">
                    <i class="fa fa-solid fa-certificate me-1 fs-3 text-light"></i>

                    <!-- red badge on the icon -->
                    <span id="newOrdersBadge"
                      class="position-absolute translate-middle badge rounded-pill bg-danger"
                      style="top:0px; right:-30px;">
                      <?= $__newOrders ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </a>

          <!-- Orders -->
          <a href="manage-orders" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Orders</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Active</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, '`order`', 'fulfillment_status', 'processing') ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Completed</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, '`order`', 'fulfillment_status', 'delivered') ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px; height:100px;">
                    <i class="fa fa-solid fa-table-cells-large fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>

          <!-- Categories -->
          <a href="categories" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Categories</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'categories', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'categories', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-table-cells-large fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>
          <!-- Services -->
          <a href="services" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Services</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'services', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'services', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-screwdriver-wrench fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>
          <!-- Reviews -->
          <a href="reviews" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Reviews</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'reviews', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'reviews', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-star fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>
          <!-- User -->
          <a href="user" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Users</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'user', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'user', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-users fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>
          <!-- Managers -->
          <a href="managers" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Managers</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'admins', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'admins', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-user-shield fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>
          <!-- Contacts -->
          <a href="contacts" class="col-12 col-md-6 col-lg-4 text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 h-100">
              <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="me-3">
                    <h1 class="mb-2 fw-semibold text-dark">Contacts</h1>
                    <div class="d-flex gap-4 small">
                      <div>
                        <div class="text-muted">Activated</div>
                        <div class="fs-4 fw-bold text-primary">
                          <?= getStatusCount($conn, 'contacts', 'status', 1) ?>
                        </div>
                      </div>
                      <div class="text-end">
                        <div class="text-muted">Deactivated</div>
                        <div class="fs-4 fw-bold text-danger">
                          <?= getStatusCount($conn, 'contacts', 'status', 0) ?>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="d-inline-flex align-items-center justify-content-center bg-dark rounded-3 shadow-sm"
                    style="width:100px;height:100px;">
                    <i class="fa-solid fa fa-address-book fs-3 text-light"></i>
                  </div>
                </div>
              </div>
            </div>
          </a>

        </div>
      </div>
    </section>



    <?php require_once("../assets/inc/admin-footer.php"); ?>
  </div>
  <!-- JavaScript files-->
  <?php require_once("../assets/inc/admin-bottom.php"); ?>

  <script>
    // On click: zero the badge immediately (visual) and continue to payments-review.php
    (function() {
      const link = document.getElementById('newOrdersLink');
      const badge = document.getElementById('newOrdersBadge');
      const number = document.getElementById('newOrdersCount');
      if (!link) return;

      link.addEventListener('click', () => {
        if (badge) badge.textContent = '0';
        if (number) number.textContent = '0';

        // Optional (keeps badge hidden for the rest of the session):
        // fetch('payments-review-badge-clear.php', {method:'POST'}).catch(()=>{});
        // You can implement that endpoint to set a session flag if you like.
      });

      // Hide badge if already zero
      if (badge && parseInt(badge.textContent || '0', 10) === 0) {
        badge.style.display = 'none';
      }
    })();
  </script>