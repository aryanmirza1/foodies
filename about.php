<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
?>
<?php include __DIR__ . "/includes/loader.php";  ?>

<?php
// DB (mysqli)
require __DIR__ . '/includes/db.php';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Fetch from DB (reviews)
$reviews = [];
$q1 = mysqli_query($conn, "
  SELECT name, profession, rating, review, image
  FROM reviews
  WHERE status=1
  ORDER BY created_at DESC, id DESC
");
if ($q1) {
  while ($row = mysqli_fetch_assoc($q1)) $reviews[] = $row;
}

// Fetch from DB (services)
$services = [];
$q2 = mysqli_query($conn, "
  SELECT title, description, icon
  FROM services
  WHERE status=1
  ORDER BY created_at DESC, id DESC
");
if ($q2) {
  while ($row = mysqli_fetch_assoc($q2)) $services[] = $row;
}
?>

<style>
  /* Keep it subtle; most styling via Bootstrap utilities */
  :root {
    --bg: #fbf3ea; /* same soft beige as cart */
    --fancy: "Playfair Display", ui-serif, Georgia, serif;
  }
  .object-fit-cover { object-fit: cover; }
</style>

<main class="py-4" style="background: var(--bg);">
  <div class="container">
    <!-- Section header (matches Cart vibe) -->
    <h2 class="section-title mb-1">About Foodies</h2>

    <!-- Primary card -->
    <div class="rounded-4 bg-white shadow-sm p-3 p-md-4 mb-4">
      <div class="row g-4 align-items-center">
        <!-- Image -->
        <div class="col-md-5">
          <div class="ratio ratio-4x3 rounded-4 overflow-hidden">
            <video src="images/abt-vdo.mp4" autoplay loop muted class="w-100 h-100 object-fit-cover"></video>
          </div>
        </div>

        <!-- Copy -->
        <div class="col-md-7">
          <img src="images/logo.png" alt="" height="50" width="50">
          <p><strong style="font-size: 27x;">Foodies</strong></p>

          <p class="mb-3 text-secondary">
            We cook fresh meal every day in Australia. Good ingredients, clean prep,
            and friendly service—simple as that.
          </p>

          <!-- Quick points -->
          <ul class="list-unstyled mb-3">
            <li class="d-flex align-items-start gap-2 mb-2">
              <i class="bi bi-check2-circle"></i>
              <span>Quality ingredients from trusted suppliers</span>
            </li>
            <li class="d-flex align-items-start gap-2 mb-2">
              <i class="bi bi-check2-circle"></i>
              <span>Hygienic kitchen and temperature-controlled delivery</span>
            </li>
            <li class="d-flex align-items-start gap-2">
              <i class="bi bi-check2-circle"></i>
              <span>Fair prices with frequent chef specials</span>
            </li>
          </ul>

          <!-- Badges / meta -->
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-geo-alt me-1"></i>Australia</span>
            <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-truck me-1"></i>Fast Delivery</span>
            <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-shield-check me-1"></i>Hygiene First</span>
          </div>

          <!-- CTA -->
          <div class="d-flex flex-wrap gap-2">
            <a href="products.php" class="btn col-5 col-md-3 my-md-4 py-2 btn-sm btn-outline-success rounded-pill">
              <i class="bi bi-egg-fried me-1"></i> Explore Menu
            </a>
            <a href="contact.php" class="btn col-5 col-md-3 my-md-4 py-2 btn-sm btn-outline-success rounded-pill">
              <i class="bi bi-telephone me-1"></i> Contact Us
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Reviews -->
    <h3 class="section-title mt-4 text-uppercase">What clients say</h3>
    <div class="owl-carousel owl-theme" id="reviewsOwl">
      <?php if ($reviews): foreach ($reviews as $r):
        // DB has only filename (e.g., "client1.jpg")
        $revFile = trim((string)$r['image']);
        $revImg  = $revFile !== '' ? "assets/img/reviews/$revFile" : "assets/img/user.png";
      ?>
        <div class="item">
          <div class="review-card">
            <span class="review-quote"></span>

            <div class="d-flex align-items-center mb-2">
              <img src="<?= e($revImg) ?>" class="review-avatar rounded-circle me-3" alt="">
              <div>
                <strong class="review-name"><?= e($r['name']) ?></strong>
                <?php if (!empty($r['profession'])): ?>
                  <div class="small text-muted"><?= e($r['profession']) ?></div>
                <?php endif; ?>
                <div class="review-stars">
                  <?php for ($i=0; $i < (int)$r['rating']; $i++) echo "★"; ?>
                </div>
              </div>
            </div>

            <p class="review-text mb-0"><?= e($r['review']) ?></p>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="item"><div class="review-card">No reviews yet.</div></div>
      <?php endif; ?>
    </div>

    <!-- Services cards -->
    <h3 class="section-title my-3 text-uppercase" data-aos="fade-up">Our services</h3>
    <div class="row">
      <?php if ($services): foreach ($services as $s):
        // icon: DB stores like "fa-truck" -> add "fas" prefix for FA5; fallback to Bootstrap check
        $ico = trim((string)$s['icon']);
        $iconClass = $ico !== '' ? ('fas ' . $ico) : 'bi bi-check2-circle';
      ?>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
          <div class="service-card premium">
            <span class="svc-glow"></span>
            <div class="svc-icon">
              <i class="<?= e($iconClass) ?>"></i>
            </div>
            <div class="fw-semibold mt-2"><?= e($s['title']) ?></div>
            <div class="small-muted"><?= e($s['description']) ?></div>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="col-12"><div class="alert alert-light border">No services added yet.</div></div>
      <?php endif; ?>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
