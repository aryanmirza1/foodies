<!-- Bottom app nav (fixed) -->
<nav class="bottom-nav d-md-none" style=" background: rgba(255, 255, 255, 0.15);  /* transparent white */
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.3); /* subtle border */
        color: black;
    font-weight: 600;">
  <a class="<?= is_active('index.php') ?>" href="index.php"><i class="bi bi-house"></i><small>Home</small></a>
  <a class="<?= is_active('products.php') ?>" href="products.php"><i class="bi bi-grid-1x2"></i><small>Menu</small></a>
  <a class="<?= is_active('cart.php') ?>" href="cart.php" class="center-fab"><i class="bi bi-bag"></i><small>Cart</small></a>
  <a class="<?= is_active('about.php') ?>" href="about.php"><i class="bi bi-book-half"></i><small>About Us</small></a>
  <a class="<?= is_active('account.php') ?>" href="account.php"><i class="bi bi-person"></i><small>Account</small></a>
</nav>

<footer class="app-footer px-2 mt-3">
  <div class="container py-5 pb-md-1">
    <div class="row g-4">
      <div class="col-12 col-lg-6">
        <div class="d-flex align-items-center mb-2">
          <img src="images/logo.png" class="brand-logo me-2" alt="">
          <h5 class="m-0">Foodies</h5>
        </div>
        <p class="small text-muted">
          Fresh dishes, fast delivery, and a smooth ordering experience.
        </p>
        <div class="d-flex gap-3">
          <i class="bi bi-facebook"></i>
          <i class="bi bi-instagram"></i>
          <i class="bi bi-twitter-x"></i>
        </div>
      </div>

      <div class="col-6 col-lg-3 d-none d-md-block">
        <h6 class="footer-title">Explore</h6>
        <ul class="list-unstyled small footer-links">
          <li><a href="products.php">Menu</a></li>
          <li><a href="offers.php">Offers</a></li>
          <li><a href="faq.php">FAQ</a></li>
          <li><a href="policy.php">Privacy</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-3">
        <h6 class="footer-title">Contact</h6>
        <ul class="list-unstyled small footer-links">
          <li><i class="bi bi-telephone me-1"></i> 0000-0000000</li>
          <li><i class="bi bi-envelope me-1"></i> support@foodies.aus</li>
          <li><i class="bi bi-geo-alt me-1"></i> Melbourne, Aus</li>
        </ul>
      </div>


    </div>

    <hr class="my-1">
    <div class="footer-bottom d-flex flex-wrap justify-content-between small text-muted py-3 pb-4">
      <span class="footer-copy">© <?= date('Y'); ?> Foodies. All rights reserved.</span>
      <span class="footer-dev">Design & Developed by <strong>Leo Developer's</strong></span>
    </div>

  </div>
</footer>

<?php include __DIR__ . "/bottom.php"; ?>
<script>
  window.addEventListener('load', () => {
    const el = document.getElementById('siteLoader');
    if (!el) return;
    // Quick exit so it feels snappy
    setTimeout(() => el.classList.add('is-hidden'), 900);
    el.addEventListener('transitionend', () => el.remove());
  });
</script>