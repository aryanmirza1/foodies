<?php
// ---- Auto page meta (can be overridden before including this file) ----
$script = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); // e.g., products.php
$map = [
  'index.php'        => ['Home',                'images/hero-home.jpg',     '#e86a10'],
  'products.php'     => ['Menu',                'images/hero-menu.jpg',     '#e86a10'],
  'cart.php'         => ['Your Cart',           'images/hero-cart.jpg',     '#8a3b10'],
  'checkout.php'     => ['Checkout',            'images/hero-checkout.jpg', '#0e7a3a'],
  'reviews.php'      => ['Reviews',             'images/hero-reviews.jpg',  '#0e7a3a'],
  'about.php'        => ['About Us',            'images/hero-about.jpg',    '#0e7a3a'],
  'contact.php'      => ['Contact',             'images/hero-contact.jpg',  '#0e7a3a'],
  'login.php'        => ['Login',               'images/hero-auth.jpg',     '#374151'],
  'register.php'     => ['Register',            'images/hero-auth.jpg',     '#374151'],
  // fallback for detail pages
  'product-details.php' => ['Product Details',  'images/hero-burger.jpg',   '#0e7a3a'],
  'product.php'         => ['Product Details',  'images/hero-burger.jpg',   '#0e7a3a'],
];

// 1) Title (priority: explicit $heroTitle > special cases > map > filename)
if (!isset($heroTitle)) {
  $heroTitle = $map[$script][0] ?? ucwords(str_replace(['-', '_', '.php'], [' ', ' ', ''], $script));
  // products.php?cat=Pizza -> "Menu — Pizza"
  if ($script === 'products.php' && !empty($_GET['cat'])) {
    $heroTitle .= ' — ' . htmlspecialchars($_GET['cat']);
  }
  // product-details?id=... & name=... -> use name if provided
  if (in_array($script, ['product-details.php','product.php']) && !empty($_GET['name'])) {
    $heroTitle = htmlspecialchars($_GET['name']);
  }
}

// 2) Image & tone (brand-ish orange/green)
$heroImage = $heroImage ?? ($map[$script][1] ?? 'images/hero-generic.jpg');
$heroTone  = $heroTone  ?? ($map[$script][2] ?? '#e86a10');

// 3) Subtitle (optional; can set $heroSubtitle before include)
if (!isset($heroSubtitle)) {
  $heroSubtitle = [
    'index.php'    => 'Order fresh. Delivered fast.',
    'products.php' => 'Explore popular picks, categories, and deals.',
    'cart.php'     => 'Review your items before checkout.',
    'checkout.php' => 'Almost there—confirm your order details.',
  ][$script] ?? null;
}

// 4) Breadcrumbs (Home > Page > Optional leaf)
if (!isset($heroTrail)) {
  $heroTrail = [['Home','index.php']];
  if ($script !== 'index.php') $heroTrail[] = [$map[$script][0] ?? $heroTitle, null];
  // Make leaf linked for products list
  if ($script === 'product-details.php' || $script === 'product.php') {
    $heroTrail = [['Home','index.php'], ['Products','products.php'], [$heroTitle, null]];
  }
}

// 5) Eyebrow kicker (tiny label)
$heroKicker = $heroKicker ?? 'Foodies';

// ---- Render ----
?>
<section class="video-hero">
  <video autoplay muted loop playsinline preload="auto" class="hero-video">
    <source src="images/page-hero.mp4" type="video/mp4">
  </video>

  <div class="hero-overlay"></div>

  <div class="container hero-content">
    <div class="hero-text">
      <h1 class=""><?= htmlspecialchars($heroTitle ?? 'Your Cart') ?></h1>
      <!-- <p class="text-white col-6"><?= htmlspecialchars($heroSubtitle ?? 'Review your items before checkout.') ?></p> -->
      <nav class="hero-breadcrumb">
        <a href="index.php" class="text-dark">Home</a> <span class="tt">›</span>
        <span class="tt"><?= htmlspecialchars($heroTitle ?? 'Your Cart') ?></span>
      </nav>
    </div>
  </div>
</section>


