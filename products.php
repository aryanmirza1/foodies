<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/page-hero.php';
?>
<?php include __DIR__ . "/includes/loader.php";  ?>

<?php
// Use shared mysqli connection
require __DIR__ . '/includes/db.php'; // provides $conn (mysqli)

/* ---------- STORE HOURS + OPEN/CLOSE ---------- */
$STORE_TZ = 'Australia/Sydney';
$STORE_HOURS = [
  'mon' => ['00:00','24:00'],
  'tue' => ['00:00','24:00'],
  'wed' => ['00:00','24:00'],
  'thu' => ['00:00','24:00'],
  'fri' => ['00:00','24:00'],
  'sat' => ['00:00','24:00'],
  'sun' => ['00:00','24:00'],
];
function dow_key(DateTime $dt){ return strtolower(substr($dt->format('D'),0,3)); }
function make_dt_in_tz($tz){ return new DateTime('now', new DateTimeZone($tz)); }
function dt_from_hm(DateTime $base, string $hm): DateTime {
  [$h,$m] = array_map('intval', explode(':', $hm . ':0'));
  $copy = clone $base; $copy->setTime($h,$m,0,0); return $copy;
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
    $probe = clone $now;
    for ($i=0; $i<7; $i++) {
      $key = dow_key($probe);
      if (!empty($hours[$key][0]) && !empty($hours[$key][1])) {
        $o = dt_from_hm($probe, $hours[$key][0]);
        if ($i === 0) {
          if ($now < $o) { $openAt = $o; break; }
        } else {
          $openAt = $o; break;
        }
      }
      $probe->modify('+1 day');
    }
  }

  return [
    'is_open'   => $isOpen,
    'next_open' => $openAt ? $openAt->format(DateTime::ATOM) : null,
    'close_iso' => $closeAt ? $closeAt->format(DateTime::ATOM) : null,
    'tz'        => $tz,
  ];
}

$OW = compute_order_window($STORE_HOURS, $STORE_TZ);
$nextOpenStr = $OW['next_open'] ? (new DateTime($OW['next_open']))->format('D g:i A') : 'soon';
$closeStr    = $OW['close_iso'] ? (new DateTime($OW['close_iso']))->format('g:i A') : '';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* ---------- Fetch categories (for pills) ---------- */
$categories = [];
if ($res = mysqli_query($conn, "
  SELECT id, title
  FROM categories
  WHERE status = 1
  ORDER BY created_at DESC, id DESC
")) {
  while ($row = mysqli_fetch_assoc($res)) $categories[] = $row;
  mysqli_free_result($res);
}

/* ---------- Fetch ALL products (no limit; paging handled in JS) ---------- */
$products = [];
if ($res = mysqli_query($conn, "
  SELECT m.id, m.title, m.image, m.price, m.created_at, c.title AS category
  FROM menu m
  LEFT JOIN categories c ON c.id = m.category_id
  WHERE m.status = 1
  ORDER BY m.created_at DESC, m.id DESC
")) {
  while ($row = mysqli_fetch_assoc($res)) $products[] = $row;
  mysqli_free_result($res);
}
?>

<style>
  /* --- Moving orders window ticker (same as index) --- */
  .order-window {margin-top:6px;margin-bottom:18px}
  .ow-ticker{position:relative;overflow:hidden;}
  .ow-track{display:flex;gap:48px;white-space:nowrap;animation:ow-marquee 18s linear infinite;padding:.55rem 1rem;font-weight:600}
  @keyframes ow-marquee { from{ transform: translateX(0) } to{ transform: translateX(-50%) } }
  .ow-open { background: #e8f7ed; color:#176d35; border:1px solid #cfeedd; }
  .ow-closed{ background: #fff6e6; color:#975a00; border:1px solid #ffe9c2; }
  .ow-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:8px;background:currentColor;opacity:.7}

  /* Fade/disable order buttons when closed (same as index) */
  .order-disabled, .order-disabled:disabled { opacity:.5 !important; pointer-events:none !important; filter:grayscale(.3) }

  /* Existing page styles */
  .anim-item { transition: transform .2s ease, opacity .2s ease; }
  .anim-hide { opacity: 0 !important; transform: translateY(6px) scale(.98) !important; pointer-events: none; }
  .prod-col.anim-hidden { display: none !important; }
  #noResults h5 { color: var(--secondary, #6e3b16); }
  .cart-qty { min-width: 48px; text-align: center; }
  .input-group .btn { flex: 0 0 auto; }
  #noResults { min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
</style>

<main class="py-3" style="background:var(--bg)">
     <!-- ORDERS WINDOW (ticker below hero) -->
    <?php $isOpen = !empty($OW['is_open']); $tz = $OW['tz']; ?>
    <section class="order-window" id="orderWindow"
      data-open="<?= $isOpen ? '1':'0' ?>"
      data-tz="<?= e($tz) ?>"
      data-next-open="<?= e($nextOpenStr) ?>"
      data-close-at="<?= e($closeStr) ?>"
      data-hours='<?= e(json_encode($STORE_HOURS)) ?>'>
      <div class="ow-ticker <?= $isOpen ? 'ow-open':'ow-closed' ?>">
        <div class="ow-track" aria-live="polite">
          <?php if ($isOpen): for($i=0;$i<6;$i++): ?>
            <div><span class="ow-dot"></span> We’re taking orders now — closes today at <strong><?= e($closeStr) ?></strong> (<?= e($tz) ?>)</div>
          <?php endfor; else: for($i=0;$i<6;$i++): ?>
            <div><span class="ow-dot"></span> Orders are closed — opens at <strong><?= e($nextOpenStr) ?></strong> (<?= e($tz) ?>)</div>
          <?php endfor; endif; ?>
        </div>
      </div>
    </section>
  <div class="container">

 

    <!-- Title -->
    <div class="flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-2">
      <h3 class="section-title m-0">Our Latest Items</h3>
    </div>

    <!-- Search -->
    <div class="">
      <form role="search" onsubmit="return false;">
        <div class="input-group pb-3 border-2 border-dark">
          <input id="search" class="form-control form-control-md rounded-4"
            type="search" placeholder="Search dishes, e.g., burger, biryani…" aria-label="Search">
        </div>
      </form>
    </div>

    <!-- Category pills -->
    <div class="pills ctg-pls mb-3 d-flex justify-content-center" id="catChips">
      <button class="pill px-4 active" data-cat="All">All</button>
      <?php if ($categories): foreach ($categories as $c): ?>
        <button class="pill px-4" data-cat="<?= e($c['title']) ?>"><?= e($c['title']) ?></button>
      <?php endforeach; endif; ?>
    </div>

    <!-- Products grid -->
    <div id="productsGrid" class="row g-3">
      <?php if ($products): foreach ($products as $i => $p):
        $fname = trim((string)$p['image']);
        $img   = $fname !== '' ? "assets/img/menu/$fname" : "assets/img/placeholder-prod.jpg";
        $title = $p['title'];
        $cat   = $p['category'] ?: 'General';
        $price = (int)($p['price'] ?? 0);
        $index = !empty($p['created_at']) ? strtotime($p['created_at']) : (int)$p['id'];
        $id    = 'p' . (int)$p['id'];
      ?>
        <div class="col-6 col-md-3 prod-col" style="cursor:pointer;"
          data-index="<?= e($index) ?>"
          data-cat="<?= e($cat) ?>"
          data-title="<?= e($title) ?>"
          data-price="<?= e($price) ?>">
          <div class="product-card h-100 anim-item">
            <img src="<?= e($img) ?>" class="product-thumb" alt="">
            <div class="p-2">
              <div class="d-flex justify-content-between">
                <div class="fw-semibold"><?= e($title) ?></div>
                <div class="price">$ <?= number_format($price) ?></div>
              </div>
              <div class="small text-muted"><?= e($cat) ?></div>
              <div class="d-flex gap-2 mt-2 px-2">
                <button
                  class="btn add-btn text-light btn-sm flex-grow-1 js-add-cart col-6"
                  data-id="<?= e($id) ?>"
                  data-name="<?= e($title) ?>"
                  data-price="<?= e($price) ?>"
                  data-image="<?= e($img) ?>"
                  data-category="<?= e($cat) ?>">
                  <i class="bi bi-bag-plus me-1"></i> Add
                </button>

                <button class="btn view-btn btn-sm flex-grow-1 js-view-product col-6"
                  data-id="<?= e($id) ?>"
                  data-title="<?= e($title) ?>"
                  data-price="<?= e($price) ?>"
                  data-old-price=""
                  data-img="<?= e($img) ?>"
                  data-cat="<?= e($cat) ?>"
                  data-desc="Handmade, served hot.">
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <!-- No results message -->
    <div id="noResults" class="text-center py-5 d-none">
      <img src="images/empty-cart.png" alt="" style="width:80px;height:80px;opacity:.7">
      <h5 class="mt-3 mb-1">No products found</h5>
      <p class="text-muted small mb-0">Try a different search or category.</p>
    </div>

    <div class="text-center mt-3">
      <button id="loadMore" class="btn col-3 my-4 py-2 btn-sm btn-outline-success rounded-pill">Load more</button>
    </div>

  </div>
</main>

<!-- Product Modal -->
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
          <img id="pmImg" src="assets/img/placeholder-prod.jpg" class="w-100 h-100 object-fit-cover rounded-3" alt="">
        </div>

        <div class="d-flex align-items-center justify-content-between">
          <h5 id="pmTitle" class="mb-1 fw-semibold">Item</h5>
          <div class="text-warning small">
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            <i class="bi bi-star"></i>
          </div>
        </div>

        <div class="d-flex align-items-baseline gap-2 mb-2">
          <span id="pmNew" class="fw-bold fs-5 text-danger">$ 0</span>
          <small id="pmOld" class="text-secondary text-decoration-line-through"></small>
        </div>

        <div class="mb-2">
          <span class="me-2 text-muted">Category:</span>
          <span id="pmCat" class="badge text-bg-light border">General</span>
        </div>

        <p id="pmDesc" class="text-muted small mb-3">—</p>

        <!-- STATIC alert (only visible when closed) -->
        <div id="owNote" class=" p-2 small mb-2 <?= $isOpen ? 'd-none' : '' ?>" style="background: #fff6e6; color:#975a00;">
          Ordering is closed right now. Opens at <strong class="ow-next"><?= e($nextOpenStr) ?></strong>.
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

<script>
  // Expose order window state for this page (same as index)
  window.ORDER_WINDOW = {
    isOpen: <?= $OW['is_open'] ? 'true':'false' ?>,
    nextOpenLabel: "<?= e($nextOpenStr) ?>",
    closeLabel: "<?= e($closeStr) ?>"
  };
</script>

<script>
  (() => {
    if (window.__productsPageInit) return;
    window.__productsPageInit = true;

    document.addEventListener('DOMContentLoaded', () => {
      const grid = document.getElementById('productsGrid');
      const searchInput = document.getElementById('search');
      const sortSel = document.getElementById('sort'); // optional if you add it later
      const catChips = document.getElementById('catChips');
      const loadMoreBtn = document.getElementById('loadMore');
      const noResults = document.getElementById('noResults');
      const modalEl = document.getElementById('productModal');

      const rs = n => Number(n || 0).toLocaleString('en-US');
      const getAllCols = () => Array.from(grid.querySelectorAll('.prod-col'));

      /* ---------- Opening window UI (fade buttons + guards) ---------- */
      function applyOrderWindowUI(open) {
        document.querySelectorAll('.js-add-cart').forEach(b => b.classList.toggle('order-disabled', !open));
        const pmAdd = document.getElementById('pmAdd');
        const pmOrder = document.getElementById('pmOrder');
        const note = document.getElementById('owNote');
        if (pmAdd) pmAdd.classList.toggle('order-disabled', !open);
        if (pmOrder) pmOrder.classList.toggle('order-disabled', !open);
        if (note) {
          if (open) note.classList.add('d-none');
          else {
            note.classList.remove('d-none');
            note.querySelector('.ow-next') && (note.querySelector('.ow-next').textContent = (window.ORDER_WINDOW?.nextOpenLabel || 'soon'));
          }
        }
      }
      applyOrderWindowUI(!!window.ORDER_WINDOW?.isOpen);

      // Block Add/Order actions when closed
      document.addEventListener('click', (e) => {
        if (window.ORDER_WINDOW?.isOpen) return;
        if (e.target.closest('.js-add-cart') || e.target.closest('#pmOrder')) {
          e.preventDefault();
          const when = window.ORDER_WINDOW?.nextOpenLabel || 'soon';
          if (window.bootstrap) {
            const n = document.createElement('div');
            n.className = 'alert alert-warning position-fixed top-0 start-50 translate-middle-x mt-2 py-2 px-3 shadow';
            n.style.zIndex = 1080;
            n.textContent = `Ordering is closed right now. Opens at ${when}.`;
            document.body.appendChild(n);
            setTimeout(()=>n.remove(), 2200);
          } else {
            alert(`Ordering is closed right now. Opens at ${when}.`);
          }
        }
      });

      /* ---------- Anim helpers (existing) ---------- */
      function syncHiddenClass(col) {
        const card = col.querySelector('.anim-item');
        const shouldHide = col.classList.contains('pg-hidden') || col.classList.contains('flt-hidden');
        if (shouldHide) {
          card && card.classList.add('anim-hide');
          col.classList.add('anim-hidden');
        } else {
          col.classList.remove('anim-hidden');
          requestAnimationFrame(() => card && card.classList.remove('anim-hide'));
        }
      }

      let activeCat = 'All';
      let searchTerm = '';

      /* ---------- Filter ---------- */
      function applyFilters() {
        const term = (searchTerm || '').trim().toLowerCase();
        const active = (activeCat || 'All').toLowerCase();

        let visibleCount = 0;

        getAllCols().forEach(col => {
          const title = (col.dataset.title || '').toLowerCase();
          const cat = (col.dataset.cat || 'all').toLowerCase();

          const byCat = (active === 'all') || (cat === active);
          const bySearch = !term || title.includes(term);
          const match = byCat && bySearch;

          if (match) col.classList.remove('flt-hidden');
          else col.classList.add('flt-hidden');

          if (!col.classList.contains('pg-hidden') && !col.classList.contains('flt-hidden')) visibleCount++;
          syncHiddenClass(col);
        });

        if (noResults) {
          const h = noResults.querySelector('h5');
          const p = noResults.querySelector('p');
          if (visibleCount === 0) {
            if ((activeCat || 'All').toLowerCase() !== 'all' && !searchTerm) {
              h && (h.textContent = 'No products available in this category');
              p && (p.textContent = '');
            } else if ((activeCat || 'All').toLowerCase() !== 'all' && searchTerm) {
              h && (h.textContent = 'No products match your search in this category');
              p && (p.textContent = '');
            } else if (searchTerm) {
              h && (h.textContent = 'No products match your search');
              p && (p.textContent = 'Try different keywords.');
            } else {
              h && (h.textContent = 'No products found');
              p && (p.textContent = 'Try a different search or category.');
            }
            noResults.classList.remove('d-none');
          } else {
            noResults.classList.add('d-none');
          }
        }
      }

      /* ---------- Sort (optional) ---------- */
      function applySort() {
        const val = (sortSel?.value) || 'pop';
        const sorted = [...getAllCols()];
        if (val === 'low') sorted.sort((a, b) => (+a.dataset.price) - (+b.dataset.price));
        if (val === 'high') sorted.sort((a, b) => (+b.dataset.price) - (+a.dataset.price));
        if (val === 'new') sorted.sort((a, b) => (+b.dataset.index) - (+a.dataset.index));
        if (val === 'pop') sorted.sort((a, b) => (+a.dataset.index) - (+b.dataset.index));
        sorted.forEach(el => grid.appendChild(el));
      }

      /* ---------- Load More (paging only) ---------- */
      const INITIAL_SHOW = 12;
      const PAGE_SIZE = 12;
      const SHOW_BTN_THRESHOLD = 12;

      const getPgHidden = () => getAllCols().filter(col => col.classList.contains('pg-hidden'));

      function updateLoadMoreVisibility() {
        const total = getAllCols().length;
        const pgHiddenCount = getPgHidden().length;
        if (loadMoreBtn) {
          const shouldShow = (total > SHOW_BTN_THRESHOLD) && (pgHiddenCount > 0);
          loadMoreBtn.classList.toggle('d-none', !shouldShow);
        }
      }

      function initPaging() {
        const cols = getAllCols();
        cols.forEach((col, i) => {
          const card = col.querySelector('.anim-item');
          if (i < INITIAL_SHOW) {
            col.classList.remove('pg-hidden');
            if (card) card.classList.add('anim-hide');
            setTimeout(() => card && card.classList.remove('anim-hide'), 12 + i * 12);
          } else {
            col.classList.add('pg-hidden');
          }
          col.classList.remove('flt-hidden');
          syncHiddenClass(col);
        });
        updateLoadMoreVisibility();
      }

      loadMoreBtn && loadMoreBtn.addEventListener('click', () => {
        const next = getPgHidden().slice(0, PAGE_SIZE);
        next.forEach((col, i) => {
          const card = col.querySelector('.anim-item');
          col.classList.remove('pg-hidden');
          if (card) card.classList.add('anim-hide');
          syncHiddenClass(col);
          setTimeout(() => card && card.classList.remove('anim-hide'), 12 + i * 12);
        });
        updateLoadMoreVisibility();
      });

      /* ---------- Category pills ---------- */
      const chipsWrap = document.getElementById('catChips');
      if (chipsWrap) {
        chipsWrap.addEventListener('click', (e) => {
          const btn = e.target.closest('.pill');
          if (!btn) return;
          chipsWrap.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
          btn.classList.add('active');
          activeCat = btn.dataset.cat || 'All';
          applyFilters();
          updateLoadMoreVisibility();
        });

        const urlCat = new URLSearchParams(location.search).get('cat');
        if (urlCat) {
          activeCat = urlCat;
          const match = Array.from(chipsWrap.querySelectorAll('.pill'))
            .find(p => (p.dataset.cat || '').toLowerCase() === urlCat.toLowerCase());
          if (match) {
            chipsWrap.querySelectorAll('.pill').forEach(p => p.classList.remove('active'));
            match.classList.add('active');
          }
        }
      }

      /* ---------- Search ---------- */
      let t;
      const debouncedFilter = () => {
        clearTimeout(t);
        t = setTimeout(() => {
          applyFilters();
          updateLoadMoreVisibility();
        }, 150);
      };
      if (searchInput) {
        searchInput.addEventListener('input', () => {
          searchTerm = searchInput.value || '';
          debouncedFilter();
        });
      }

      /* ---------- Modal wiring ---------- */
      const pmModal = modalEl && window.bootstrap ? (window.bootstrap.Modal.getOrCreateInstance ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : new window.bootstrap.Modal(modalEl)) : null;

      const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
      const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };

      window.fillModal = (p) => {
        setVal('pmId', p.id);
        const pmImg = document.getElementById('pmImg');
        if (pmImg) pmImg.src = p.image || 'assets/img/placeholder-prod.jpg';
        setVal('pmImgRaw', p.image || '');
        setTxt('pmTitle', p.title || '');
        setTxt('pmDesc', p.desc || '');
        setTxt('pmCat', p.category || 'General');
        setVal('pmCatRaw', p.category || '');
        setTxt('pmNew', '$ ' + rs(p.price));
        setVal('pmPriceRaw', p.price || 0);
        setTxt('pmOld', (p.oldPrice && Number(p.oldPrice) > Number(p.price)) ? ('$ ' + rs(p.oldPrice)) : '');
        setVal('pmQty', 1);

        // Reflect open/closed in modal note + buttons
        const open = !!window.ORDER_WINDOW?.isOpen;
        const note = document.getElementById('owNote');
        const pmAdd = document.getElementById('pmAdd');
        const pmOrder = document.getElementById('pmOrder');
        if (note) {
          if (open) note.classList.add('d-none');
          else {
            note.classList.remove('d-none');
            note.querySelector('.ow-next') && (note.querySelector('.ow-next').textContent = (window.ORDER_WINDOW?.nextOpenLabel || 'soon'));
          }
        }
        pmAdd?.classList.toggle('order-disabled', !open);
        pmOrder?.classList.toggle('order-disabled', !open);

        pmModal && pmModal.show();
      };

      // Open modal from the Details button
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.js-view-product');
        if (!btn) return;
        fillModal({
          id: btn.dataset.id,
          title: btn.dataset.title,
          price: Number(btn.dataset.price || 0),
          oldPrice: btn.dataset.oldPrice ? Number(btn.dataset.oldPrice) : null,
          category: btn.dataset.cat || '',
          image: btn.dataset.img || '',
          desc: btn.dataset.desc || ''
        });
      });

      // Make entire card open the modal (except Add/Details buttons)
      document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function (e) {
          if (e.target.closest('.add-btn') || e.target.closest('.view-btn')) return;
          const btn = card.querySelector('.js-view-product');
          if (!btn) return;
          const payload = {
            id: btn.dataset.id,
            title: btn.dataset.title,
            price: Number(btn.dataset.price || 0),
            oldPrice: btn.dataset.oldPrice ? Number(btn.dataset.oldPrice) : null,
            category: btn.dataset.cat || '',
            image: btn.dataset.img || '',
            desc: btn.dataset.desc || ''
          };
          if (typeof window.fillModal === 'function') window.fillModal(payload);
          if (modalEl && window.bootstrap && window.bootstrap.Modal) {
            const instance = window.bootstrap.Modal.getOrCreateInstance
              ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
              : new window.bootstrap.Modal(modalEl);
            instance.show();
          }
        });
      });

      // Grid add-to-cart
      document.addEventListener('click', async (e) => {
        const add = e.target.closest('.js-add-cart');
        if (!add) return;
        if (!window.ORDER_WINDOW?.isOpen) return; // guard: closed
        if (add.dataset.busy === '1') return;
        add.dataset.busy = '1';
        add.disabled = true;
        const old = add.innerHTML;
        try {
          const r = await fetch('cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              action: 'add',
              item: {
                id: add.dataset.id,
                name: add.dataset.name,
                price: Number(add.dataset.price || 0),
                image: add.dataset.image || '',
                category: add.dataset.category || '',
                qty: 1
              }
            })
          });
          const d = await r.json();
          if (!d.ok) throw new Error(d.error || 'Failed');
          const badge = document.getElementById('cartCount');
          if (badge && d.summary) badge.textContent = d.summary.count;
          add.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Added';
          setTimeout(() => add.innerHTML = old, 900);
        } catch (err) {
          alert(err.message);
        } finally {
          add.disabled = false;
          add.dataset.busy = '0';
        }
      });

      /* ----- Init ----- */
      if (getAllCols().length === 0) {
        noResults && noResults.classList.remove('d-none');
        loadMoreBtn && loadMoreBtn.classList.add('d-none');
        return;
      }
      applySort();
      initPaging();
      applyFilters();
      updateLoadMoreVisibility();
    });
  })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
