<?php $assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : '../assets'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="<?= $assetsUrl ?>/js/bootstrap.bundle.min.js"></script>
<!-- <script src="../assets/js/Chart.min.js"></script> -->
<script src="<?= $assetsUrl ?>/js/just-validate.min.js"></script>
<script src="<?= $assetsUrl ?>/js/choices.min.js"></script>
<script src="<?= $assetsUrl ?>/js/OverlayScrollbars.min.js"></script>
<!-- <script src="../assets/js/charts-home.js"></script> -->
<!-- Main File-->
<script src="<?= $assetsUrl ?>/js/front.js"></script>
<script src="https://cdn.datatables.net/2.1.3/js/dataTables.min.js"></script>
<script>
  $(".msg").fadeTo(3500, 0.9).fadeOut(3500);
  let table = new DataTable('.table');
  // Example starter JavaScript for disabling form submissions if there are invalid fields
  (() => {
    'use strict'

    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    const forms = document.querySelectorAll('.needs-validation')

    // Loop over them and prevent submission
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
  })()
  // ------------------------------------------------------- //
  //   Inject SVG Sprite - 
  //   see more here 
  //   https://css-tricks.com/ajaxing-svg-sprite/
  // ------------------------------------------------------ //
  function injectSvgSprite(path) {

    var ajax = new XMLHttpRequest();
    ajax.open("GET", path, true);
    ajax.send();
    ajax.onload = function(e) {
      var div = document.createElement("div");
      div.className = 'd-none';
      div.innerHTML = ajax.responseText;
      document.body.insertBefore(div, document.body.childNodes[0]);
    }
  }
  const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
</script>

<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css">
</body>

</html>
