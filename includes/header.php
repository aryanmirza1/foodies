<?php include __DIR__ . "/top.php"; ?>
<?php
$active  = basename($_SERVER['PHP_SELF']); // e.g. "index.php"
$current = $active;
function is_active($file){ global $active; return $active === $file ? 'active' : ''; }

// Try a few common session keys for logged-in state
$loggedIn = !empty($_SESSION['USER_LOGIN']) || !empty($_SESSION['user']) || !empty($_SESSION['user_id']);
?>

<header class="app-header shadow-sm">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between py-2">
      <!-- Left: Logo -->
      <a href="index.php" class="d-flex align-items-center text-decoration-none">
        <img src="images/logo.png" class="brand-logo me-2" alt="">
        <span class="brand-text">Foodies</span>
      </a>

      <!-- Desktop nav -->
      <ul class="d-none d-md-flex list-unstyled m-0 gap-4 align-items-center">
        <li><a class="nav-link <?= $current == 'index.php' ? 'active' : '' ?>" href="index.php">Home</a></li>
        <li><a class="nav-link <?= $current == 'products.php' ? 'active' : '' ?>" href="products.php">Menu</a></li>
        <li><a class="nav-link <?= $current == 'about.php' ? 'active' : '' ?>" href="about.php">About</a></li>
        <li><a class="nav-link <?= $current == 'contact.php' ? 'active' : '' ?>" href="contact.php">Contact</a></li>
        <!-- Moved here: Account text link -->
        <li><a class="nav-link <?= $current == 'account.php' ? 'active' : '' ?>" href="account.php">Account</a></li>
      </ul>

      <!-- Right: Cart + Auth (Login/Register dropdown OR Logout) + Hamburger (mobile only) -->
      <div class="d-flex align-items-center gap-3">
        <!-- Cart -->
        <a href="cart.php" class="position-relative text-dark">
          <i class="bi bi-bag fs-4"></i>
          <span class="cart-badge" id="cartCount">
            <?php
            $cnt = 0;
            if (!empty($_SESSION['cart'])) {
              foreach ($_SESSION['cart'] as $it) { $cnt += (int)$it['qty']; }
            }
            echo $cnt;
            ?>
          </span>
        </a>

        <!-- Auth area: if not logged in, show dropdown icon; if logged in, show logout icon -->
        <?php if (!$loggedIn): ?>
          <div class="dropdown d-none d-md-block">
            <button class="btn p-0 border-0 bg-transparent" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open auth menu">
              <i class="bi bi-caret-down-fill fs-5"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="login.php">Login</a></li>
              <li><a class="dropdown-item" href="register.php">Register</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="logout.php" class="text-decoration-none d-none d-md-inline-block text-danger" title="Logout">
            <i class="bi bi-box-arrow-right fs-4"></i>
          </a>
        <?php endif; ?>

        <!-- Mobile hamburger -->
        <button type="button" class="hamburger d-inline-flex d-md-none" id="hamburger" aria-label="Open menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </div>

  <!-- Offcanvas for small screens only -->
  <div class="offcanvas-wrap d-md-none" id="offcanvas" style="z-index: 999;">
    <div class="offcanvas-panel">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center">
          <img src="images/logo.png" class="brand-logo me-2" alt="">
          <strong>Foodies</strong>
        </div>
        <button class="btn-close" id="menuClose"></button>
      </div>

      <nav class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action <?= $current == 'index.php' ? 'active' : '' ?>" href="index.php"><i class="bi bi-house me-2"></i>Home</a>
        <a class="list-group-item list-group-item-action <?= $current == 'products.php' ? 'active' : '' ?>" href="products.php"><i class="bi bi-grid-3x3-gap me-2"></i>Menu</a>
        <a class="list-group-item list-group-item-action <?= $current == 'about.php' ? 'active' : '' ?>" href="about.php"><i class="bi bi-info-circle me-2"></i>About</a>
        <a class="list-group-item list-group-item-action <?= $current == 'contact.php' ? 'active' : '' ?>" href="contact.php"><i class="bi bi-telephone me-2"></i>Contact</a>
        <a class="list-group-item list-group-item-action <?= $current == 'account.php' ? 'active' : '' ?>" href="account.php"><i class="bi bi-person-circle me-2"></i>Account</a>

        <?php if (!$loggedIn): ?>
          <a class="list-group-item list-group-item-action <?= $current == 'login.php' ? 'active' : '' ?>" href="login.php"><i class="bi bi-box-arrow-in-right me-2"></i>Login</a>
          <a class="list-group-item list-group-item-action <?= $current == 'register.php' ? 'active' : '' ?>" href="register.php"><i class="bi bi-person-plus me-2"></i>Register</a>
        <?php else: ?>
          <a class="list-group-item list-group-item-action" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        <?php endif; ?>
      </nav>
    </div>
    <div class="offcanvas-backdrop" id="offcanvasBackdrop"></div>
  </div>
</header>

<!-- Keep: refresh cart badge -->
<script>
  async function refreshCartBadge() {
    try {
      const res = await fetch('cart_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'summary' })
      });
      const data = await res.json();
      if (data && typeof data.count !== 'undefined') {
        const el = document.getElementById('cartCount');
        if (el) el.textContent = data.count;
      }
    } catch (err) {
      console.error('Cart badge update failed:', err);
    }
  }
  document.addEventListener('DOMContentLoaded', refreshCartBadge);
</script>
