// Loader fade (secondary to inline safety)
window.addEventListener("load", function () {
  var l = document.getElementById("app-loader");
  if (l) {
    l.style.opacity = "0";
    setTimeout(function () {
      l.style.display = "none";
    }, 250);
  }
});

// Init AOS safely
try {
  AOS && AOS.init({ duration: 500, easing: "ease-out", once: true });
} catch (e) {}

// Offcanvas (mobile)
(function () {
  var hamburger = document.getElementById("hamburger");
  var wrap = document.getElementById("offcanvas");
  var closeBtn = document.getElementById("menuClose");
  var backdrop = document.getElementById("offcanvasBackdrop");

  function close() {
    hamburger && hamburger.classList.remove("active");
    wrap && wrap.classList.remove("show");
  }
  function open() {
    hamburger && hamburger.classList.add("active");
    wrap && wrap.classList.add("show");
  }

  if (hamburger && wrap) {
    hamburger.addEventListener("click", function () {
      wrap.classList.contains("show") ? close() : open();
    });
  }
  if (closeBtn) closeBtn.addEventListener("click", close);
  if (backdrop) backdrop.addEventListener("click", close);
})();

// Quick View modal (jQuery optional)
document.addEventListener("click", function (e) {
  var btn = e.target.closest(".js-quick-view");
  if (!btn) return;
  var data = btn.dataset;
  document.getElementById("qvTitle").textContent = data.title || "";
  document.getElementById("qvImg").src = data.img || "";
  document.getElementById("qvPrice").textContent = data.price || "";
  document.getElementById("qvDesc").textContent =
    data.desc || "Tasty and fresh, prepared on order.";
  new bootstrap.Modal(document.getElementById("quickViewModal")).show();
});

// Add to cart badge
document.addEventListener("click", function (e) {
  var add = e.target.closest(".js-add-cart");
  if (!add) return;
  var badge = document.getElementById("cart-count");
  var n = parseInt(badge.textContent, 10) || 0;
  badge.textContent = n + 1;
  badge.classList.add("pop");
  setTimeout(() => badge.classList.remove("pop"), 220);
});
// PRODUCTS PAGE ONLY
(function () {
  var grid = document.getElementById("productsGrid");
  var loadBtn = document.getElementById("loadMore");
  if (!grid || !loadBtn) return;

  var offset = 12; // already rendered
  loadBtn.addEventListener("click", function () {
    loadBtn.disabled = true;
    loadBtn.textContent = "Loading...";
    fetch("products-data.php?offset=" + offset)
      .then((r) => r.text())
      .then((html) => {
        var div = document.createElement("div");
        div.innerHTML = html.trim();
        div.querySelectorAll(".col-6").forEach((el) => grid.appendChild(el));
        offset += 8;
      })
      .catch(() => alert("Failed to load more items."))
      .finally(() => {
        loadBtn.disabled = false;
        loadBtn.textContent = "Load more";
      });
  });

  // visual active state on category chips (mock filter)
  document.querySelectorAll("#catChips .pill").forEach((btn) => {
    btn.addEventListener("click", () => {
      document
        .querySelectorAll("#catChips .pill")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      // TODO: call server with ?cat=... and re-render
    });
  });

  // sort select (mock)
  var sort = document.getElementById("sort");
  if (sort) {
    sort.addEventListener("change", () => {
      // TODO: call server with ?sort=...; demo: simple shuffle
      [...grid.children]
        .sort(() => Math.random() - 0.5)
        .forEach((c) => grid.appendChild(c));
    });
  }
})();

$("#reviewsOwl").owlCarousel({
  loop: true,
  autoplay: true,
  autoplayTimeout: 3500,
  autoplayHoverPause: true,
  smartSpeed: 550,
  margin: 16,
  stagePadding: 0, // no peeking for text cards
  dots: true,
  nav: true,
  navText: [
    '<i class="bi bi-chevron-left"></i>',
    '<i class="bi bi-chevron-right"></i>',
  ],
  autoHeight: true, // adapt to quote length
  responsive: {
    0: { items: 1 },
    768: { items: 2 },
    1200: { items: 3 },
  },
  animateOut: "fadeOut",
  animateIn: "fadeInUp",
});

document.addEventListener("DOMContentLoaded", function () {
  var $owl = $("#reviewsOwl");
  if (!$owl.length || !$.fn.owlCarousel) return;

  $owl.on("initialized.owl.carousel changed.owl.carousel", function (e) {
    // apply entrance class to the active slides
    var idx = e.item && e.item.index;
    $owl.find(".owl-item").removeClass("is-active");
    $owl
      .find(".owl-item")
      .eq(idx)
      .addClass("is-active")
      .find(".review-card")
      .addClass("review-appear");
    // remove class after animation so it can replay next time
    setTimeout(
      () => $owl.find(".review-card").removeClass("review-appear"),
      600
    );
  });

  $owl.owlCarousel({
    loop: true,
    autoplay: true,
    autoplayTimeout: 3500,
    autoplayHoverPause: true,
    smartSpeed: 550,
    margin: 16,
    dots: true,
    nav: true,
    navText: [
      '<i class="bi bi-chevron-left"></i>',
      '<i class="bi bi-chevron-right"></i>',
    ],
    autoHeight: true,
    responsive: { 0: { items: 1 }, 768: { items: 2 }, 1200: { items: 3 } },
  });
});

$(function () {
  $("#heroOwl").owlCarousel({
    items: 1,
    loop: true,
    dots: true,
    nav: false,
    autoplay: true,
    autoplayTimeout: 3500,
    smartSpeed: 650,
    touchDrag: true,
    mouseDrag: true,
    pullDrag: true,
    animateOut: "fadeOut", // smooth transition; remove if you prefer slide
  });
});

// helpers
const rs = (n) => Number(n || 0).toLocaleString("en-PK");
async function api(body) {
  const r = await fetch("cart_api.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body),
  });
  return r.json();
}
function setText(id, v) {
  const el = document.getElementById(id);
  if (el) el.textContent = v;
}
function setVal(id, v) {
  const el = document.getElementById(id);
  if (el) el.value = v;
}

// Quantity
document
  .getElementById("pmPlus")
  .addEventListener("click", () =>
    setVal(
      "pmQty",
      Math.max(1, Number(document.getElementById("pmQty").value || 1) + 1)
    )
  );
document
  .getElementById("pmMinus")
  .addEventListener("click", () =>
    setVal(
      "pmQty",
      Math.max(1, Number(document.getElementById("pmQty").value || 1) - 1)
    )
  );

// Add
document.getElementById("pmAdd").addEventListener("click", async (e) => {
  e.preventDefault();
  const btn = e.currentTarget;
  btn.disabled = true;
  try {
    const item = {
      id: document.getElementById("pmId").value,
      name: document.getElementById("pmTitle").textContent.trim(),
      price: Number(document.getElementById("pmPriceRaw").value || 0),
      image: document.getElementById("pmImgRaw").value,
      category: document.getElementById("pmCatRaw").value,
      qty: Math.max(1, Number(document.getElementById("pmQty").value || 1)),
    };
    const d = await api({ action: "add", item });
    if (!d.ok) throw new Error(d.error || "Add failed");
    // feedback
    const old = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Added';
    setTimeout(() => (btn.innerHTML = old), 1200);
    // badge
    const badge = document.getElementById("cartCount");
    if (badge && d.summary) badge.textContent = d.summary.count;
  } catch (err) {
    alert(err.message);
  } finally {
    btn.disabled = false;
  }
});

// Order now
document.getElementById("pmOrder").addEventListener("click", async (e) => {
  e.preventDefault();
  document.getElementById("pmAdd").click();
  setTimeout(() => (location.href = "cart.php"), 250);
});

// Fill modal from a product card (call this on your "View" button)
window.fillModal = (p) => {
  setVal("pmId", p.id);
  setText("pmTitle", p.title);
  document.getElementById("pmImg").src = p.image;
  setVal("pmImgRaw", p.image);
  setText("pmDesc", p.desc || "");
  setText("pmCat", p.category || "");
  setVal("pmCatRaw", p.category || "");
  setText("pmNew", "Rs " + rs(p.price));
  setVal("pmPriceRaw", p.price);
  if (p.oldPrice && Number(p.oldPrice) > p.price) {
    setText("pmOld", "Rs " + rs(p.oldPrice));
  } else {
    setText("pmOld", "");
  }
  setVal("pmQty", 1);
};

document.addEventListener("click", (e) => {
  const openBtn = e.target.closest("#hamburger");
  const closeBtn = e.target.closest("#menuClose, #offcanvasBackdrop");

  if (openBtn) {
    document.getElementById("offcanvas")?.classList.add("show");
    document.getElementById("offcanvasBackdrop")?.classList.add("show");
  }
  if (closeBtn) {
    document.getElementById("offcanvas")?.classList.remove("show");
    document.getElementById("offcanvasBackdrop")?.classList.remove("show");
  }
});
