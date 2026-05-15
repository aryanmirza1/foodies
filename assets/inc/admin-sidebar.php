<?php
require_once("../assets/inc/admin-top.php");
// Helper — avoid redeclare if you already added it on the dashboard
if (!function_exists('count_new_orders')) {
      function count_new_orders(mysqli $conn): int
      {
            $rs  = mysqli_query($conn, "SELECT COUNT(*) FROM `order` WHERE status IN ('new','pending')");
            $row = $rs ? mysqli_fetch_row($rs) : [0];
            return (int)($row[0] ?? 0);
      }
}
$__newOrdersSidebar = count_new_orders($conn);
?>
<style>
      /* Rail */
      .side-navbar {
            background: #2d3034;
      }

      .side-navbar-inner {
            height: 100%;
            overflow-y: auto;
      }

      /* Link */
      .side-navbar .sidebar-link {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .65rem .8rem;
            border-radius: .8rem;
            color: #cfd6dd;
            text-decoration: none;
      }

      .side-navbar .sidebar-link:hover {
            background: transparent;
            color: #fff !important;

      }

      /* Icon square */
      .side-navbar .icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .6rem;
            background: #1f2225;
            color: #b9c0c7;
      }

      /* Active (green like ref theme) */
      .side-navbar .active-menu {
            background: transparent;
            box-shadow: none;
            color: #fff;
            border-radius: 1%;
      }

      .side-navbar .active-menu .icon {
            /* background: #875b19; */
            color: #fff;
      }

      /* Underline animation */
      .side-navbar .sidebar-link {
            position: relative;
            overflow: hidden;
      }

      .side-navbar .sidebar-link::after {
            content: "";
            position: absolute;
            left: .8rem;
            right: .8rem;
            bottom: 4px;
            height: 3px;
            border-radius: 2px;
            background: rgb(128 76 26);
            transform: scaleX(0);
            transform-origin: right;
            /* start from right */
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      }

      /* Hover animation (right → left) */
      .side-navbar .sidebar-link:hover::after {
            transform: scaleX(1);
            transform-origin: left;
            /* end on left */
      }

      /* Keep it visible for active menu */
      .side-navbar .active-menu::after {
            transform: scaleX(1);
            transform-origin: left;
      }

      .badge-new-orders {
            position: absolute;
            left: 2%;
            top: 30%;
            transform: translateY(-50%);
            background: #dc3545;
            color: #fff;
            font-size: .78rem;
            font-weight: 700;
            line-height: 1;
            padding: .25rem .45rem;
            border-radius: 999px;
            min-width: 1.25rem;
            text-align: center;
      }
</style>

<nav class="side-navbar">
      <div class="side-navbar-inner">

            <!-- Header -->
            <div class="sidebar-header d-flex align-items-center justify-content-center p-3 mb-2">
                  <div class="sidenav-header-inner text-center">
                        <img class="img-fluid rounded-circle avatar mb-3" src="../assets/img/managers/<?php
                                                                                                      echo empty($_SESSION["ADMIN_LOGIN"]["ADMIN_IMAGE"]) ? "dummy.jpg" : $_SESSION["ADMIN_LOGIN"]["ADMIN_IMAGE"];
                                                                                                      ?>" alt="person" style="width:72px;height:72px;object-fit:cover;">
                        <h2 class="h6 text-white text-uppercase mb-0"><?php echo $_SESSION["ADMIN_LOGIN"]["ADMIN_NAME"] ?></h2>
                        <p class="text-sm mb-0 text-secondary">Role: <?php echo ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 1) ? 'Admin' : 'Editor'; ?></p>
                  </div>
            </div>


            <ul class="list-unstyled px-2 mb-4">

                  <!-- Dashboard -->
                  <li class="sidebar-item mb-2">
                        <a href="dashboard" class="sidebar-link <?php echo ($pageName == 'dashboard') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-gauge-high"></i></span>
                              <span>Dashboard</span>
                        </a>
                  </li>

                        <!-- New Orders  -->
                  <li class="sidebar-item mb-2 position-relative">
                        <a href="payments-review"
                              class="sidebar-link <?php echo ($pageName == 'payments-review') ? 'active-menu' : ''; ?>"
                              id="sidebarNewOrdersLink">
                              <span class="icon"><i class="fa-solid fa-certificate me-1"></i></span>
                              <span>New Orders</span>

                              <?php if ($__newOrdersSidebar > 0): ?>
                                    <span id="sidebarNewOrdersBadge" class="badge-new-orders">
                                          <?php echo $__newOrdersSidebar; ?>
                                    </span>
                              <?php endif; ?>
                        </a>
                  </li>

                   <!-- manage-orders -->
                  <li class="sidebar-item mb-2">
                        <a href="manage-orders" class="sidebar-link <?php echo ($pageName == 'manage-orders' || $pageName == 'order-view') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-table-cells-large"></i></span>
                              <span>Orders</span>
                        </a>
                  </li>
                  <!--pr -->

                  <!-- Ordre Dash -->
                  <li class="sidebar-item mb-2">
                        <a href="orders-dashboard" class="sidebar-link <?php echo ($pageName == 'orders-dashboard') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-tasks"></i></span>
                              <span>Orders Dashbord</span>
                        </a>
                  </li>

                  <!-- Categories -->
                  <li class="sidebar-item mb-2">
                        <a href="categories" class="sidebar-link <?php echo ($pageName == 'categories' || $pageName == 'manage-categories') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-table-cells-large"></i></span>
                              <span>Categories</span>
                        </a>
                  </li>
            

                  <!-- Menu -->
                  <li class="sidebar-item mb-2">
                        <a href="menu" class="sidebar-link <?php echo ($pageName == 'menu' || $pageName == 'manage-menu') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-list-ul"></i></span>
                              <span>Menu</span>
                        </a>
                  </li>

                  <!-- Menu -->
                  <li class="sidebar-item mb-2">
                        <a href="payment" class="sidebar-link <?php echo ($pageName == 'payment' || $pageName == 'manage-payment') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-money-bill-wave"></i></span>
                              <span>Payment Details</span>
                        </a>
                  </li>

                  <!-- Reviews -->
                  <li class="sidebar-item mb-2">
                        <a href="reviews" class="sidebar-link <?php echo ($pageName == 'reviews' || $pageName == 'manage-reviews') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-comments"></i></span>
                              <span>Reviews</span>
                        </a>
                  </li>

                  <!-- Services -->
                  <li class="sidebar-item mb-2">
                        <a href="services" class="sidebar-link <?php echo ($pageName == 'services' || $pageName == 'manage-services') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-screwdriver-wrench"></i></span>
                              <span>Services</span>
                        </a>
                  </li>
                  
                  <!-- user -->
                  <li class="sidebar-item mb-2">
                        <a href="user" class="sidebar-link <?php echo ($pageName == 'user' || $pageName == 'manage-user') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-user-group"></i></span>
                              <span>Users</span>
                        </a>
                  </li>

                  <!-- Managers (admin only) -->
                  <?php if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 1) { ?>
                        <li class="sidebar-item mb-2">
                              <a href="managers" class="sidebar-link <?php echo ($pageName == 'managers' || $pageName == 'manage-managers') ? 'active-menu' : '' ?>">
                                    <span class="icon"><i class="fa-solid fa-user-shield"></i></span>
                                    <span>Site Managers</span>
                              </a>
                        </li>
                  <?php } ?>

                  <!-- Contacts -->
                  <li class="sidebar-item mb-2">
                        <a href="contacts" class="sidebar-link <?php echo ($pageName == 'contacts') ? 'active-menu' : '' ?>">
                              <span class="icon"><i class="fa-solid fa-address-book"></i></span>
                              <span>Contacts</span>
                        </a>
                  </li>

            </ul>
      </div>
</nav>

<script>
      // Zero the sidebar badge immediately when navigating to the review page
      (function() {
            const link = document.getElementById('sidebarNewOrdersLink');
            const badge = document.getElementById('sidebarNewOrdersBadge');
            if (!link) return;
            link.addEventListener('click', () => {
                  if (badge) badge.textContent = '0';
            });
      })();
</script>