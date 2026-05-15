<?php require_once("logout.php")?>
<header class="header mb-5 pb-3" style="background:#2d3034;">
  <nav class="nav navbar fixed-top" style="background:#2d3034; padding:.5rem 1rem;">
    <div class="container-fluid d-flex align-items-center">

      <!-- Left: Toggle Button -->
      <div class="d-flex align-items-center d-md-none">
        <a class="menu-btn d-flex align-items-center justify-content-center p-2" id="toggle-btn" href="#"
           style="background:transparent; color:white; border-radius:.6rem; width:38px; height:38px;">
          <span class="fas fa-bars"></span>
        </a>
      </div>

      <!-- Right: Settings Menu (always on right) -->
      <ul class="nav-menu mb-0 list-unstyled d-flex flex-md-row align-items-md-center ms-auto">

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle text-white text-sm" id="languages" rel="nofollow"
             data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
             style="color:#cfd6dd; font-size:.9rem;">
            <span class="d-none d-sm-inline-block ms-2" style="cursor:pointer;">Setting</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end mt-sm-3 shadow-sm"
              style=" cursor:pointer; border-radius:.6rem; font-size:.9rem; min-width:150px; overflow:hidden;">
                
              <?php if ($_SESSION["ADMIN_LOGIN"]["ADMIN_ROLE"] == 1) { ?>
           
              <li>
              <a class="dropdown-item" rel="nofollow"
                 href="manage-managers?id=<?php echo $_SESSION['ADMIN_LOGIN']['ADMIN_ID']?>"
                 style="padding:.5rem 1rem;">
                <i class="fa-solid fa-user me-2 text-secondary"></i> Profile
              </a>
            </li>
                  <?php } ?>
           
            <li>
              <a class="dropdown-item" rel="nofollow" href="?action=logout"
                 style="padding:.5rem 1rem;">
                <i class="fa-solid fa-right-from-bracket me-2 text-danger"></i> Logout
              </a>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </nav>
</header>
