
  <style>
:root { --brand:#DB7D31; }

/* Glass overlay */
.loader-overlay{
  position:fixed; inset:0; z-index:9999;
  display:flex; align-items:center; justify-content:center;
  backdrop-filter: blur(12px) saturate(120%);
  -webkit-backdrop-filter: blur(12px) saturate(120%);
  background: rgba(255,255,255,.35);
  transition: opacity .35s ease, visibility .35s ease;
}
.loader-overlay.is-hidden{ opacity:0; visibility:hidden; }

.loader-content{ text-align:center; }

/* Logo: quick fade, no shadow */
.loader-logo{
  width:120px; max-width:38vw;
  height:120px; max-height:38vw;
  opacity:0; transform:scale(.98);
  animation: logoIn .5s ease forwards;
}

/* Fast line bars under logo */
.loader-bars{
  margin-top:12px; height:20px; display:flex; gap:6px; justify-content:center;
}
.loader-bars span{
  width:6px; height:100%; border-radius:4px;
  background: linear-gradient(180deg, var(--brand), #bf6a28);
  transform-origin:50% 100%;
  animation: barPulse .45s linear infinite; /* fast */
}
.loader-bars span:nth-child(1){ animation-delay: 0s;     }
.loader-bars span:nth-child(2){ animation-delay: .05s;   }
.loader-bars span:nth-child(3){ animation-delay: .10s;   }
.loader-bars span:nth-child(4){ animation-delay: .15s;   }
.loader-bars span:nth-child(5){ animation-delay: .20s;   }

/* Animations */
@keyframes logoIn { to { opacity:1; transform:scale(1); } }
@keyframes barPulse {
  0%   { transform: scaleY(.4); opacity:.8; }
  50%  { transform: scaleY(1.25); opacity:1; }
  100% { transform: scaleY(.55); opacity:.9; }
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce){
  .loader-logo, .loader-bars span { animation: none; opacity:1; transform:none; }
}

  </style>
<!-- Loader -->
<div id="siteLoader" class="loader-overlay" aria-live="polite" aria-busy="true">
  <div class="loader-content">
    <img src="images/logo.png" alt="Foodies" class="loader-logo">
    <div class="loader-bars" aria-hidden="true">
      <span></span><span></span><span></span><span></span><span></span>
    </div>
  </div>
</div>

<script>
(function () {
  var KEY = "siteLoaderSeen";         // localStorage flag
  var loaderId = "siteLoader";        // matches your div id
  var hideDelay = 250;                // fade-out duration (ms)
  var safetyTimeout = 2500;           // hard stop in case other JS fails

  function getLoader() {
    return document.getElementById(loaderId);
  }

  function hideLoader(setSeenFlag) {
    var l = getLoader();
    if (!l) return;

    // fade out
    l.style.transition = 'opacity .25s ease';
    l.style.opacity = '0';
    setTimeout(function () {
      l.classList.add('is-hidden');   // uses your CSS (opacity/visibility)
      l.style.display = 'none';
      if (setSeenFlag) {
        try { localStorage.setItem(KEY, "1"); } catch(e) {}
      }
    }, hideDelay);
  }

  // If user has seen the loader before, hide immediately (no flash)
  try {
    if (localStorage.getItem(KEY) === "1") {
      var l = getLoader();
      if (l) {
        l.classList.add('is-hidden');
        l.style.display = 'none';
      }
      return; // done — never show again
    }
  } catch(e) {
    // If storage is blocked, we still show once this session and hide as usual.
  }

  // First visit: keep it visible, then hide on load and set the flag
  window.addEventListener('load', function () { hideLoader(true); });

  // Safety: ensure it disappears even if load never fires
  setTimeout(function(){ hideLoader(true); }, safetyTimeout);
})();
</script>
