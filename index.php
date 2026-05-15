<?php
include __DIR__ . "/includes/header.php";
include __DIR__ . "/includes/announcement-banner.php";
require __DIR__ . "/includes/db.php"; // mysqli $conn

/* escape */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- STORE HOURS (edit these) ---------- */
$STORE_TZ = 'Australia/Sydney';
$STORE_HOURS = [ // 24h "HH:MM"
  'mon' => ['00:00','24:00'],
  'tue' => ['00:00','24:00'],
  'wed' => ['00:00','24:00'],
  'thu' => ['00:00','24:00'],
  'fri' => ['00:00','24:00'],
  'sat' => ['00:00','24:00'], // handled below as end-of-day
  'sun' => ['00:00','24:00'],
];

/* helpers to compute open/close now + next open */
function dow_key(DateTime $dt){ return strtolower(substr($dt->format('D'),0,3)); } // mon..sun
function make_dt_in_tz($tz){ return new DateTime('now', new DateTimeZone($tz)); }
function dt_from_hm(DateTime $base, string $hm): DateTime {
  [$h,$m] = array_map('intval', explode(':', $hm . ':0'));
  $copy = clone $base;
  // Allow "24:00" to mean end-of-day
  if ($h === 24) { $copy->setTime(23,59,59,0); return $copy; }
  $copy->setTime(max(0,min(23,$h)), max(0,min(59,$m)), 0, 0);
  return $copy;
}
function compute_order_window(array $hours, string $tz): array {
  $now = make_dt_in_tz($tz);
  $todayKey = dow_key($now);
  $today = $hours[$todayKey] ?? null;

  $isOpen = false; $closeAt = null; $openAt = null;

  if ($today && $today[0] && $today[1]) {
    $open  = dt_from_hm($now, $today[0]);
    $close = dt_from_hm($now, $today[1]);
    if ($now >= $open && $now < $close) {
      $isOpen = true; $closeAt = $close;
    }
  }

  if (!$isOpen) {
    // If closed, find the next opening in the next 7 days
    $probe = clone $now;
    for ($i=0; $i<7; $i++) {
      $key = dow_key($probe);
      if (!empty($hours[$key][0]) && !empty($hours[$key][1])) {
        $o = dt_from_hm($probe, $hours[$key][0]);
        if ($i === 0) {
          if ($now < $o) { $openAt = $o; break; } // upcoming today
        } else {
          $openAt = $o; break;
        }
      }
      $probe->modify('+1 day');
    }
  }

  return [
    'is_open'    => $isOpen,
    'now_iso'    => $now->format(DateTime::ATOM),
    'next_open'  => $openAt ? $openAt->format(DateTime::ATOM) : null,
    'close_iso'  => $closeAt ? $closeAt->format(DateTime::ATOM) : null,
    'tz'         => $tz,
  ];
}

$OW = compute_order_window($STORE_HOURS, $STORE_TZ);

/* ---------- Fetch with mysqli ---------- */
$categories = [];
if ($r = mysqli_query($conn, "SELECT id,title,image FROM categories WHERE status=1 ORDER BY created_at DESC, id DESC")) {
  while ($row = mysqli_fetch_assoc($r)) $categories[] = $row;
  mysqli_free_result($r);
}

$products = [];
if ($r = mysqli_query($conn, "
  SELECT m.id, m.title, m.image, m.price, c.title AS category
  FROM menu m
  LEFT JOIN categories c ON c.id = m.category_id
  WHERE m.status=1
  ORDER BY m.created_at DESC, m.id DESC
  LIMIT 8
")) {
  while ($row = mysqli_fetch_assoc($r)) $products[] = $row;
  mysqli_free_result($r);
}

$services = [];
if ($r = mysqli_query($conn, "SELECT title,description,icon FROM services WHERE status=1 ORDER BY created_at DESC, id DESC")) {
  while ($row = mysqli_fetch_assoc($r)) $services[] = $row;
  mysqli_free_result($r);
}

$reviews = [];
if ($r = mysqli_query($conn, "SELECT name,profession,rating,review,image FROM reviews WHERE status=1 ORDER BY created_at DESC, id DESC")) {
  while ($row = mysqli_fetch_assoc($r)) $reviews[] = $row;
  mysqli_free_result($r);
}
?>

<style>
  .hf{position:relative;border-radius:20px;overflow:hidden;user-select:none;height:clamp(42vh,56vh,62vh)}
  .hf-slide{position:absolute;inset:0;opacity:0;pointer-events:none;transition:opacity var(--hf-speed,600ms) ease}
  .hf-slide img{width:100%;height:100%;object-fit:cover;display:block}
  .hf-slide.is-active{opacity:1;pointer-events:auto}
  .hf-dots{position:absolute;left:0;right:0;bottom:12px;display:flex;gap:8px;justify-content:center}
  .hf-dots button{width:8px;height:8px;border-radius:999px;border:0;background:rgba(255,255,255,.55);transition:width .2s,background .2s}
  .hf-dots button.is-active{width:22px;background:#fff}
  .hf.dragging{cursor:grabbing}
  @media (max-width:420px){
    .hf{height:200px}
    .product-card{cursor:pointer}
    .product-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.1)}
  }

  /* Orders window ticker */
  .order-window {margin-top:6px;margin-bottom:18px}
  .ow-ticker{position:relative;overflow:hidden;}
  .ow-track{display:flex;gap:48px;white-space:nowrap;animation:ow-marquee 18s linear infinite;padding:.55rem 1rem;font-weight:600}
  @keyframes ow-marquee { from{ transform: translateX(0) } to{ transform: translateX(-50%) } }
  .ow-open { background: #e8f7ed; color:#176d35; border:1px solid #cfeedd; }
  .ow-closed{ background: #fff6e6; color:#975a00; border:1px solid #ffe9c2; }
  .ow-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:8px;background:currentColor;opacity:.7}
  .ow-muted{opacity:.8;font-weight:500}

  /* Disable / fade order buttons when closed */
  .order-disabled, .order-disabled:disabled {opacity:.5 !important; pointer-events:none !important; filter:grayscale(.3)}
</style>

<?php include __DIR__ . "/includes/loader.php"; ?>

<main class="py-3" style="background:var(--bg)">
  <div class="container">

    <!-- HERO -->
    <section class="hero pt-4">
      <div id="heroCarousel" class="hf" data-interval="3500" data-speed="600">
        <div class="hf-slide"><img src="images/hero-1.jpg" alt=""></div>
        <div class="hf-slide"><img src="images/h2.jpg" alt=""></div>
        <div class="hf-slide"><img src="images/h3.jpg" alt=""></div>
        <div class="hf-dots"></div>
      </div>
    </section>

    <!-- ORDERS WINDOW (ticker between hero & categories) -->
    <?php
      $isOpen = !empty($OW['is_open']);
      $tz     = $OW['tz'];
      $nextOpenStr = $OW['next_open'] ? (new DateTime($OW['next_open']))->format('D g:i A') : '';
      $closeStr    = $OW['close_iso'] ? (new DateTime($OW['close_iso']))->format('g:i A') : '';
    ?>
    <section class="order-window" id="orderWindow"
      data-open="<?= $isOpen ? '1':'0' ?>"
      data-tz="<?= e($tz) ?>"
      data-next-open="<?= e($nextOpenStr) ?>"
      data-close-at="<?= e($closeStr) ?>"
      data-hours='<?= e(json_encode($STORE_HOURS)) ?>'>
      <div class="ow-ticker <?= $isOpen ? 'ow-open':'ow-closed' ?>">
        <div class="ow-track" aria-live="polite">
          <?php if ($isOpen): ?>
            <?php for($i=0;$i<6;$i++): ?>
              <div><span class="ow-dot"></span> We’re taking orders now — closes today at <strong><?= e($closeStr) ?></strong> (<?= e($tz) ?>)</div>
            <?php endfor; ?>
          <?php else: ?>
            <?php for($i=0;$i<6;$i++): ?>
              <div><span class="ow-dot"></span> Orders are closed — opens at <strong><?= e($nextOpenStr ?: 'soon') ?></strong> (<?= e($tz) ?>)</div>
            <?php endfor; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- Categories -->
    <h3 class="section-title text-uppercase" data-aos="fade-up">Categories</h3>
    <div class="row g-3 mb-2" data-aos="fade-up" data-aos-delay="50">
      <?php if ($categories): foreach ($categories as $c):
        $catImg = trim((string)$c['image']);
        if ($catImg !== '' && substr($catImg,0,3) === '../') $catImg = '.'.substr($catImg,2);
        if ($catImg === '') $catImg = 'assets/img/placeholder-cat.png';
      ?>
        <div class="col-4 col-md-2">
          <a
            href="products.php?cat_id=<?= (int)$c['id'] ?>&cat=<?= urlencode($c['title']) ?>"
            class="text-decoration-none text-dark"
          >
            <div class="category-card">
              <img src="<?= e($catImg) ?>" alt="">
              <div class="fw-semibold"><?= e($c['title']) ?></div>
              <div class="small-muted">View</div>
            </div>
          </a>
        </div>
      <?php endforeach; else: ?>
        <div class="col-12"><div class="alert alert-light border">No categories yet.</div></div>
      <?php endif; ?>
    </div>

    <!-- Products -->
    <div class="text-center mt-3">
      <h3 class="section-title m-0 text-uppercase">Our Menu</h3>
    </div>

    <!-- Search -->
    <div class="my-3">
      <input id="searchInput" class="form-control form-control-md rounded-4" type="search" placeholder="Search dishes">
    </div>

    <div id="productsGrid" class="row g-3 mt-1" data-aos="fade-up" data-aos-delay="50">
      <?php if ($products): foreach ($products as $p):
        $fname   = trim((string)$p['image']);
        $prodImg = $fname !== '' ? "assets/img/menu/$fname" : "assets/img/placeholder-prod.jpg";
        $title = $p['title'];
        $cat   = $p['category'] ?: 'General';
        $price = (int)($p['price'] ?? 0);
        $id    = 'p'.$p['id'];
      ?>
        <div class="col-6 col-md-3" style="cursor:pointer;" data-title="<?= e(strtolower($title)) ?>" data-cat="<?= e(strtolower($cat)) ?>">
          <div class="product-card h-100">
            <img src="<?= e($prodImg) ?>" class="product-thumb" alt="">
            <div class="p-2 px-3">
              <div class="d-flex justify-content-between">
                <div class="fw-semibold"><?= e($title) ?></div>
                <div class="price">$ <?= number_format($price) ?></div>
              </div>
              <div class="small text-muted"><?= e($cat) ?></div>
              <div class="d-flex gap-2 mt-2">
                <button
                  class="btn text-light add-btn btn-sm flex-grow-1 js-add-cart col-6"
                  data-id="<?= e($id) ?>"
                  data-name="<?= e($title) ?>"
                  data-price="<?= $price ?>"
                  data-image="<?= e($prodImg) ?>"
                  data-category="<?= e($cat) ?>">
                  <i class="bi bi-bag-plus me-1"></i> Add
                </button>
                <button class="btn view-btn col-6"
                  data-bs-toggle="modal" data-bs-target="#productModal"
                  onclick="fillModal({
                    id: '<?= e($id) ?>',
                    title: '<?= e($title) ?>',
                    price: <?= $price ?>,
                    oldPrice: null,
                    category: '<?= e($cat) ?>',
                    image: '<?= e($prodImg) ?>',
                    desc: ''
                  })">
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="col-12"><div class="alert alert-light border">No menu items yet.</div></div>
      <?php endif; ?>

      <!-- No results message (search only) -->
      <div id="searchEmpty" class="text-center py-5 d-none">
        <img src="images/empty-cart.png" alt="" style="width:80px;height:80px;opacity:.7">
        <h5 class="mt-3 mb-1">No products found</h5>
        <p class="text-muted small mb-0">Try a different search or category.</p>
      </div>

      <div class="col-12 text-center">
        <a href="products.php" class="btn col-3 my-4 py-2 btn-sm btn-outline-success rounded-pill">View All</a>
      </div>
    </div>

    <!-- Reviews -->
    <h3 class="section-title mt-4 text-uppercase">What clients say</h3>
    <div class="owl-carousel owl-theme" id="reviewsOwl">
      <?php if ($reviews): foreach ($reviews as $r):
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
                <div class="small text-muted"><?= e($r['profession']) ?></div>
                <div class="review-stars">
                  <?php for ($i=0;$i<(int)$r['rating'];$i++) echo "★"; ?>
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

    <!-- Services -->
    <h3 class="section-title my-3 text-uppercase" data-aos="fade-up">Our services</h3>
    <div class="row">
      <?php if ($services): foreach ($services as $s): ?>
        <?php $ico = trim((string)$s['icon']); ?>
        <div class="col-6 col-md-3 mb-3 mb-md-0">
          <div class="service-card premium">
            <span class="svc-glow"></span>
            <div class="svc-icon">
              <?php if ($ico !== ''): ?>
                <i class="fas <?= e($ico) ?>"></i>
              <?php else: ?>
                <i class="bi bi-check2-circle"></i>
              <?php endif; ?>
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

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <button type="button" class="btn-close ms-auto me-2 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-body pt-0">
        <input type="hidden" id="pmId">
        <input type="hidden" id="pmImgRaw">
        <input type="hidden" id="pmCatRaw">
        <input type="hidden" id="pmPriceRaw">

        <div class="ratio ratio-16x9 mb-3">
          <img id="pmImg" src="images/prod-1.jpg" class="w-100 h-100 object-fit-cover rounded-3" alt="">
        </div>

        <div class="d-flex align-items-center justify-content-between">
          <h5 id="pmTitle" class="mb-1 fw-semibold">Spicy Burger</h5>
          <div class="text-warning small">
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            <i class="bi bi-star"></i>
          </div>
        </div>

        <div class="d-flex align-items-baseline gap-2 mb-2">
          <span id="pmNew" class="fw-bold fs-5 text-danger">$ 300</span>
          <small id="pmOld" class="text-secondary text-decoration-line-through"></small>
        </div>

        <div class="mb-2">
          <span class="me-2 text-muted">Category:</span>
          <span id="pmCat" class="badge text-bg-light border">Burgers</span>
        </div>

        <p id="pmDesc" class="text-muted small mb-3">Juicy fillet, fresh lettuce, soft bun. Served hot.</p>

        <!-- STATIC NOTE: only visible when ordering is closed -->
        <?php
          $isOpen = !empty($OW['is_open']);
          $nextOpenStr = $OW['next_open'] ? (new DateTime($OW['next_open']))->format('D g:i A') : 'soon';
        ?>
        <div id="owNote" class=" rounded-2 p-2 small mb-2  <?= $isOpen ? 'd-none' : '' ?>" style="background: #fff6e6; color:#975a00;">
          Ordering is closed right now. Opens at <strong class="ow-next"><?= htmlspecialchars($nextOpenStr) ?></strong>.
        </div>

        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="input-group" style="max-width: 160px;">
            <button class="btn btn-outline-secondary" type="button" id="pmMinus"><i class="bi bi-dash"></i></button>
            <input id="pmQty" type="number" class="form-control text-center" value="1" min="1">
            <button class="btn btn-outline-secondary" type="button" id="pmPlus"><i class="bi bi-plus"></i></button>
          </div>
          <button id="pmAdd" class="btn add-btn flex-fill text-light">
            <i class="bi bi-bag-plus me-1"></i> Add to cart
          </button>
        </div>

        <div class="d-grid">
          <a id="pmOrder" href="#" class="btn view-btn btn-lg">
            <i class="bi bi-flash me-1"></i> Order now
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>

<script>
  // ---- Opening window data from PHP ----
  window.ORDER_WINDOW = {
    isOpen: <?= $OW['is_open'] ? 'true':'false' ?>,
    tz: "<?= e($STORE_TZ) ?>",
    nextOpenLabel: "<?= e($nextOpenStr) ?>",
    closeLabel: "<?= e($closeStr) ?>",
    hours: <?= json_encode($STORE_HOURS) ?>
  };
</script>

<script>
  // ---- Currency for modal prices ----
  const CURRENCY = '$';
  const nf = (n) => Number(n || 0).toLocaleString('en-US');

  async function cartApi(body) {
    const r = await fetch('cart_api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return r.json();
  }

  // Helpers for order open/closed UI (also updates product modal text when closed)
  function applyOrderWindowUI(open) {
    const addBtns = document.querySelectorAll('.js-add-cart');
    const pmAdd   = document.getElementById('pmAdd');
    const pmOrder = document.getElementById('pmOrder');
    const note    = document.getElementById('owNote');
    const orderWindow = document.getElementById('orderWindow');

    addBtns.forEach(b => b.classList.toggle('order-disabled', !open));
    if (pmAdd)   pmAdd.classList.toggle('order-disabled', !open);
    if (pmOrder) pmOrder.classList.toggle('order-disabled', !open);

    // Modal note: show ONLY when closed, with next-open time
    if (note) {
      if (!open) {
        note.classList.remove('d-none');
        const next = (orderWindow?.dataset?.nextOpen || window.ORDER_WINDOW?.nextOpenLabel || 'soon');
        const el = note.querySelector('.ow-next');
        if (el) el.textContent = next;
      } else {
        note.classList.add('d-none');
      }
    }
  }

  // Initial UI state
  applyOrderWindowUI(!!window.ORDER_WINDOW?.isOpen);

  // Guard add-to-cart & order actions when closed (both grid + modal)
  document.addEventListener('click', async (e) => {
    // If closed, block interactions + show feedback
    if (!window.ORDER_WINDOW?.isOpen) {
      const addBtn = e.target.closest('.js-add-cart');
      const orderLink = e.target.closest('#pmOrder');
      if (addBtn || orderLink) {
        e.preventDefault();
        const ow = document.getElementById('orderWindow');
        const when = ow?.dataset?.nextOpen || window.ORDER_WINDOW?.nextOpenLabel || 'soon';
        // Toast-style notice
        if (window.bootstrap) {
          const div = document.createElement('div');
          div.className = 'alert alert-warning position-fixed top-0 start-50 translate-middle-x mt-2 py-2 px-3 shadow';
          div.style.zIndex = 1080;
          div.textContent = `Ordering is closed right now. Opens at ${when}.`;
          document.body.appendChild(div);
          setTimeout(()=>div.remove(), 2200);
        } else {
          alert(`Ordering is closed right now. Opens at ${when}.`);
        }
        return;
      }
    }

    // Normal add-to-cart flow
    const btn = e.target.closest('.js-add-cart');
    if (!btn) return;
    btn.disabled = true;
    const oldHTML = btn.innerHTML;
    try {
      const item = {
        id: btn.dataset.id,
        name: btn.dataset.name,
        price: Number(btn.dataset.price || 0),
        image: btn.dataset.image || '',
        category: btn.dataset.category || '',
        qty: 1
      };
      const d = await cartApi({ action: 'add', item });
      if (!d.ok) throw new Error(d.error || 'Failed');
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Added';
      const badge = document.getElementById('cartCount');
      if (badge && d.summary) badge.textContent = d.summary.count;
      setTimeout(() => btn.innerHTML = oldHTML, 1000);
    } catch (err) {
      alert(err.message);
    } finally {
      btn.disabled = false;
    }
  });

  // Card click guard (keeps card body opening Details)
  (function() {
    const cards = document.querySelectorAll('.product-card');
    if (!cards.length) return;
    cards.forEach(card => {
      card.addEventListener('click', (e) => {
        if (e.target.closest('.add-btn') || e.target.closest('.view-btn')) return;
        card.querySelector('.view-btn')?.click();
      });
    });
  })();

  // Modal fill + reflect order state
  (function() {
    const pmModal = document.getElementById('productModal') ? new bootstrap.Modal('#productModal') : null;
    const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
    const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };

    window.fillModal = (p) => {
      setVal('pmId', p.id);
      const img = document.getElementById('pmImg');
      if (img) img.src = p.image || 'assets/img/placeholder-prod.jpg';
      setVal('pmImgRaw', p.image || '');
      setTxt('pmTitle', p.title || '');
      setTxt('pmDesc', p.desc || '');
      setTxt('pmCat', p.category || '');
      setVal('pmCatRaw', p.category || '');
      setTxt('pmNew', CURRENCY + ' ' + nf(p.price));
      setVal('pmPriceRaw', p.price || 0);
      setTxt('pmOld', (p.oldPrice && Number(p.oldPrice) > Number(p.price)) ? (CURRENCY + ' ' + nf(p.oldPrice)) : '');
      setVal('pmQty', 1);

      // Apply open/closed state inside modal too
      applyOrderWindowUI(!!window.ORDER_WINDOW?.isOpen);

      pmModal && pmModal.show();
    };
  })();

  // Search with empty state
  (function() {
    const input = document.getElementById('searchInput');
    const empty = document.getElementById('searchEmpty');
    const rows = Array.from(document.querySelectorAll('#productsGrid > .col-6, #productsGrid > .col-md-3, #productsGrid > [data-title]'));
    if (!input || !rows.length) return;

    let t = null;
    const norm = s => (s || '').toString().toLowerCase().trim();

    function doFilter() {
      const q = norm(input.value);
      let shown = 0;
      rows.forEach(col => {
        const title = (col.getAttribute('data-title') || '').toLowerCase();
        const cat = (col.getAttribute('data-cat') || '').toLowerCase();
        const show = (!q || title.includes(q) || cat.includes(q));
        col.style.display = show ? '' : 'none';
        if (show) shown++;
      });
      if (empty) empty.classList.toggle('d-none', !(q && shown === 0));
    }

    input.addEventListener('input', () => {
      clearTimeout(t);
      t = setTimeout(doFilter, 120);
    });

    if (empty) empty.classList.add('d-none');
  })();

  // Hero fade carousel
  (function() {
    const root = document.getElementById('heroCarousel');
    if (!root) return;
    const slides = Array.from(root.querySelectorAll('.hf-slide'));
    const dotsWrap = root.querySelector('.hf-dots');
    const interval = Number(root.dataset.interval) || 2500;
    const speed = Number(root.dataset.speed) || 2000;
    root.style.setProperty('--hf-speed', speed + 'ms');

    const dots = slides.map((_, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(b);
      return b;
    });

    let idx = 0, timer = null, playing = true, locked = false;
    const setActive = i => { slides.forEach((s, k) => s.classList.toggle('is-active', k === i)); dots.forEach((d, k) => d.classList.toggle('is-active', k === i)); idx = i; };
    const next = () => goTo((idx + 1) % slides.length), prev = () => goTo((idx - 1 + slides.length) % slides.length);
    const goTo = i => { if (locked || i === idx) return; locked = true; setActive(i); setTimeout(() => locked = false, speed); };
    const start = () => { stop(); timer = setInterval(next, interval); playing = true; };
    const stop = () => { if (timer) clearInterval(timer); timer = null; playing = false; };
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', () => { if (!playing) start(); });

    let startX = 0, delta = 0, dragging = false;
    const down = x => { dragging = true; startX = x; delta = 0; stop(); root.classList.add('dragging'); };
    const move = x => { if (!dragging) return; delta = x - startX; };
    const up = () => { if (!dragging) return; dragging = false; root.classList.remove('dragging'); const th = root.clientWidth * 0.12; if (Math.abs(delta) > th) { delta < 0 ? next() : prev(); } else { start(); } };
    root.addEventListener('touchstart', e => down(e.touches[0].clientX), { passive: true });
    root.addEventListener('touchmove', e => move(e.touches[0].clientX), { passive: true });
    root.addEventListener('touchend', up);
    root.addEventListener('mousedown', e => down(e.clientX));
    window.addEventListener('mousemove', e => move(e.clientX));
    window.addEventListener('mouseup', up);
    slides.forEach(s => s.querySelector('img')?.addEventListener('dragstart', e => e.preventDefault()));

    Promise.all(slides.map(s => {
      const img = s.querySelector('img');
      return (img && !img.complete) ? new Promise(r => { img.addEventListener('load', r); img.addEventListener('error', r); }) : Promise.resolve();
    })).then(() => { setActive(0); start(); });
  })();
</script>
