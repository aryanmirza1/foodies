<?php
/* ============================================================
   MANAGE ORDERS — confirmed (paid) only
   ============================================================ */

/* -------- JSON short-circuit (delete / bulk_set) --------
   IMPORTANT: This must run BEFORE any HTML/layout is printed.
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['op'])) {
    require_once(__DIR__ . '/../includes/db.php'); // DB only; no layout
    if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn,'utf8mb4'); }
    header('Content-Type: application/json; charset=utf-8');

    $op = $_POST['op'];

    // Delete a single order
    if ($op === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['ok'=>false,'msg'=>'Invalid id']); exit; }

        $st = mysqli_prepare($conn, "DELETE FROM `order` WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($st, "i", $id);
        $ok  = mysqli_stmt_execute($st);
        $err = mysqli_error($conn);
        mysqli_stmt_close($st);

        echo json_encode(['ok'=>(bool)$ok, 'error'=>$ok?null:$err]); exit;
    }

    // Bulk set fulfillment for many IDs
    if ($op === 'bulk_set') {
        $target = strtolower((string)($_POST['fulfillment'] ?? ''));
        if (!in_array($target, ['processing','delivered'], true)) {
            echo json_encode(['ok'=>false,'msg'=>'Bad status']); exit;
        }
        $raw = is_array($_POST['ids']) ? $_POST['ids'] : explode(',', (string)($_POST['ids'] ?? ''));
        $ids = array_values(array_unique(array_filter(array_map('intval', $raw))));
        if (!$ids) { echo json_encode(['ok'=>false,'msg'=>'No ids']); exit; }

        $in  = implode(',', $ids);
        $sql = "UPDATE `order`
                SET fulfillment_status='".mysqli_real_escape_string($conn,$target)."'
                WHERE id IN ($in)";
        $ok  = mysqli_query($conn, $sql);

        echo json_encode([
            'ok'    => (bool)$ok,
            'count' => mysqli_affected_rows($conn),
            'error' => $ok ? null : mysqli_error($conn)
        ]);
        exit;
    }

    echo json_encode(['ok'=>false,'msg'=>'Unknown op']); exit;
}
/* -------- end JSON short-circuit -------- */

require_once("../assets/inc/admin-top.php");
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

/* ---------- helpers ---------- */
function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

/* Convert stored relative file path to a usable URL from /admin/ */
function media_url($path){
  $p = trim((string)$path);
  if ($p === '') return '';
  if (preg_match('#^(?:https?:)?//#i', $p) || strncmp($p,'data:',5)===0) return $p; // absolute/data
  $p = str_replace('\\','/',$p);
  if (strpos($p,'./')===0) $p = substr($p,2);
  $p = ltrim($p,'/');
  if (strpos($p,'../')===0) return $p;
  return '../'.$p;
}

/* Compact preview */
function items_preview($json){
  if (!$json) return '—';
  $arr = json_decode($json, true);
  if (!is_array($arr)) return '—';
  $out=[]; $shown=0;
  foreach ($arr as $it) {
    $name = $it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item';
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    $out[] = e($name).' × '.$qty;
    if (++$shown >= 2) break;
  }
  $more = max(0, count($arr) - $shown);
  return $out ? implode(', ', $out) . ($more ? " +{$more} more" : "") : '—';
}

/* Full list (for popover) */
function items_full_html($json){
  $arr = json_decode($json, true);
  if (!is_array($arr) || !$arr) return '<div class="text-muted">No items.</div>';
  $h = '<div style="min-width:220px"><ul class="list-unstyled m-0">';
  foreach ($arr as $it) {
    $name = e($it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item');
    $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
    if ($qty < 0) $qty = 0;
    $h .= '<li class="d-flex justify-content-between border-bottom py-1"><span>'
       .  $name . '</span><span class="fw-semibold">× ' . $qty . '</span></li>';
  }
  return $h.'</ul></div>';
}

/* Badges: we only show confirmed (paid) orders here */
function payment_badge($method){
  // Show Paid for both, but style COD darker
  return ($method==='online') ? ['Paid','bg-success'] : ['cod','bg-dark'];
}

/* ---------- Filters (GET) ---------- */
$payment_method = isset($_GET['payment_method']) ? trim($_GET['payment_method']) : '';
$fulfill        = isset($_GET['status']) ? trim($_GET['status']) : ''; // fulfillment_status
$date_from      = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to        = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$isAjax         = isset($_GET['ajax']) && $_GET['ajax'] === '1';

/* ---------- ONLY show confirmed orders ---------- */
$where = ["status='paid'"]; // confirmed by Payment Review
if ($payment_method !== '') $where[] = "payment_method='".mysqli_real_escape_string($conn,$payment_method)."'";
if ($fulfill !== '')        $where[] = "fulfillment_status='".mysqli_real_escape_string($conn,$fulfill)."'";
if ($date_from !== '')      $where[] = "DATE(created_at) >= '".mysqli_real_escape_string($conn,$date_from)."'";
if ($date_to !== '')        $where[] = "DATE(created_at) <= '".mysqli_real_escape_string($conn,$date_to)."'";
$whereSql = 'WHERE '.implode(' AND ', $where);

/* ---------- Query ---------- */
$sql = "
  SELECT id, contact_name, contact_phone, address_city, items_json,
         total, payment_method, status, payment_proof, fulfillment_status, created_at
  FROM `order`
  {$whereSql}
  ORDER BY created_at DESC
";
$res = mysqli_query($conn, $sql);

/* ---------- Row renderer ---------- */
function render_rows($res){
  $sr = 1; ob_start();
  if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
      $id        = (int)$row['id'];
      $name      = $row['contact_name'] ?? '';
      $phone     = $row['contact_phone'] ?? '';
      $city      = $row['address_city'] ?? '';
      $itemsPrev = items_preview($row['items_json']);
      $itemsFull = items_full_html($row['items_json']);
      $total     = money($row['total'] ?? 0);
      $pm        = ($row['payment_method'] === 'online') ? 'online' : 'cod';
      $created   = !empty($row['created_at']) ? date('M d, Y H:i', strtotime($row['created_at'])) : '';
      $fstat     = $row['fulfillment_status'] ?: 'processing';

      $btnLbl = ($fstat === 'delivered') ? 'Delivered' : 'Processing';
      $btnCls = ($fstat === 'delivered') ? 'btn-success' : 'btn-outline-primary';
      [$payText, $payClass] = payment_badge($pm);

      $proofThumb = '';
      if ($pm==='online' && !empty($row['payment_proof'])) {
        $u = media_url($row['payment_proof']);
        $proofThumb = '<a href="'.e($u).'" target="_blank" class="ms-1" title="View proof">'
                    . '<img src="'.e($u).'" alt="proof" class="pay-proof-thumb"></a>';
      } ?>
      <tr data-id="<?= $id ?>">
        <td><input type="checkbox" class="row-check" value="<?= $id ?>"></td>
        <td><?= $sr++ ?></td>
        <td><span class="badge bg-secondary">#<?= $id ?></span></td>
        <td>
          <strong><?= e($name ?: '—') ?></strong><br>
          <small class="text-muted"><?= e($phone ?: '—') ?></small>
        </td>
        <td><?= e($city ?: '—') ?></td>
        <td>
          <span class="order-items text-decoration-underline" role="button" tabindex="0">
            <?= $itemsPrev ?>
          </span>
          <div class="d-none items-popover"><?= $itemsFull ?></div>
        </td>
        <td><strong>$ <?= $total ?></strong></td>
        <td><span class="badge <?= $payClass ?>"><?= $payText ?></span><?= $proofThumb ?></td>
        <td class="text-muted small"><?= e($created) ?></td>
        <td class="text-end">
          <div class="btn-group" role="group">
            <button class="btn btn-sm <?= $btnCls ?> js-toggle-fulfillment"><?= $btnLbl ?></button>
            <a href="order-view.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary" title="View">
              <i class="fas fa-eye"></i>
            </a>
            <a href="print-order.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print">
              <i class="fas fa-print"></i>
            </a>
            <button class="btn btn-sm btn-outline-danger js-delete-order" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    <?php }
  }
  return ob_get_clean();
}

/* ---------- AJAX short-circuit for rows only ---------- */
if ($isAjax) { echo render_rows($res); exit; }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Orders</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .order-items{ cursor:pointer; }
    .pay-proof-thumb{ width:28px; height:28px; object-fit:cover; border-radius:4px; border:1px solid #ddd; vertical-align:middle; }
  </style>
</head>
<body>
  <?php require_once("../assets/inc/admin-sidebar.php"); ?>
  <div class="page">
    <?php require_once("../assets/inc/admin-header.php"); ?>

    <section class="py-5">
      <div class="container-fluid">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="h3 mb-0" style="color:#6E3B16;">
              <i class="fas fa-receipt me-2"></i> Manage Orders
            </h3>

            <form id="filters" class="row g-2 align-items-end" method="get" autocomplete="off">
              <input type="hidden" name="ajax" value="1">
              <div class="col-auto">
                <label class="form-label small text-muted">Payment</label>
                <select name="payment_method" class="form-select form-select-sm">
                  <?php
                    $pms = [""=>"All", "cod"=>"COD", "online"=>"Online"];
                    foreach ($pms as $val=>$label) {
                      $sel = ($payment_method===$val)?'selected':'';
                      echo '<option value="'.e($val).'" '.$sel.'>'.e($label).'</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label small text-muted">Fulfillment</label>
                <select name="status" class="form-select form-select-sm">
                  <?php
                    $opts = [""=>"All", "processing"=>"Processing", "delivered"=>"Delivered"];
                    foreach ($opts as $val=>$label) {
                      $sel = ($fulfill===$val)?'selected':'';
                      echo '<option value="'.e($val).'" '.$sel.'>'.e($label).'</option>';
                    }
                  ?>
                </select>
              </div>
              <div class="col-auto">
                <label class="form-label small text-muted">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($date_from) ?>">
              </div>
              <div class="col-auto">
                <label class="form-label small text-muted">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($date_to) ?>">
              </div>
              <div class="col-auto d-flex gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(basename($_SERVER['PHP_SELF'])) ?>">Reset Filters</a>
                <a id="printFiltered" class="btn btn-sm btn-outline-secondary" href="#" target="_blank">
                  <i class="fas fa-print me-1"></i> Print / Download
                </a>
              </div>
            </form>
          </div>

          <div class="card-body">
            <?php if (!empty($_SESSION["msg"])) { echo $_SESSION["msg"]; unset($_SESSION["msg"]); } ?>

            <!-- Bulk toolbar -->
            <div class="d-flex justify-content-end align-items-center gap-2 mb-2">
              <div class="form-check me-2">
                <input class="form-check-input" type="checkbox" id="selAll">
                <label for="selAll" class="form-check-label small">Select all</label>
              </div>
              <button id="bulkDelivered" class="btn btn-sm btn-success">Mark Delivered</button>
              <button id="bulkProcessing" class="btn btn-sm btn-outline-primary">Mark Processing</button>
            </div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead style="background-color:#6E3B16; color:#fff;">
                  <tr>
                    <th></th>
                    <th>#</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>City</th>
                    <th>Items</th>
                    <th>Total ($)</th>
                    <th>Payment</th>
                    <th>Created</th>
                    <th class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="orders-body">
                  <?= render_rows($res) ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php require_once("../assets/inc/admin-footer.php"); ?>
  </div>

  <?php require_once("../assets/inc/admin-bottom.php"); ?>

  <script>
    const f = document.getElementById('filters');
    const tbody = document.getElementById('orders-body');
    const selAll = document.getElementById('selAll');
    const btnDelivered = document.getElementById('bulkDelivered');
    const btnProcessing = document.getElementById('bulkProcessing');

    function selectedIds(){
      return Array.from(document.querySelectorAll('#orders-body .row-check:checked'))
        .map(el => el.value);
    }

    function bindRowCheckboxes(){
      if (!selAll) return;
      selAll.checked = false;
      selAll.addEventListener('change', () => {
        document.querySelectorAll('#orders-body .row-check').forEach(cb => cb.checked = selAll.checked);
      });
    }

    // Bootstrap popovers for items
    function initItemPopovers(){
      document.querySelectorAll('#orders-body .order-items').forEach(el => {
        const html = el.parentElement.querySelector('.items-popover')?.innerHTML || '';
        const existing = bootstrap.Popover.getInstance(el);
        if (existing) existing.dispose();
        if (!html) return;
        new bootstrap.Popover(el, {
          container: 'body',
          html: true,
          trigger: 'hover focus click',
          placement: 'auto',
          content: html
        });
      });
    }

    // Reload table with current filters
    function reloadTable(ev){
      if (ev) ev.preventDefault();
      const qs = new URLSearchParams(new FormData(f)).toString();
      fetch('<?= e(basename($_SERVER["PHP_SELF"])) ?>?' + qs)
        .then(r => r.text())
        .then(html => {
          tbody.innerHTML = html;
          initItemPopovers();
          bindRowCheckboxes();
          bindRowActions();
        })
        .catch(() => {});
    }
    f.addEventListener('submit', reloadTable);
    f.querySelectorAll('select,input[type="date"]').forEach(el => el.addEventListener('change', reloadTable));

    // Print / Download using exactly on-screen IDs
    document.getElementById('printFiltered').addEventListener('click', function (e) {
      e.preventDefault();
      const ids = Array.from(document.querySelectorAll('#orders-body tr[data-id]'))
        .map(tr => tr.getAttribute('data-id'))
        .filter(Boolean);
      if (!ids.length) return;
      window.open('orders-print-bulk.php?ids=' + encodeURIComponent(ids.join(',')), '_blank');
    });

    // Per-row fulfillment toggle (existing endpoint)
    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.js-toggle-fulfillment');
      if (!btn) return;

      const tr = btn.closest('tr');
      const id = tr?.getAttribute('data-id');
      if (!id) return;

      btn.disabled = true;
      btn.textContent = 'Updating…';
      btn.classList.remove('btn-outline-primary','btn-success');
      btn.classList.add('btn-secondary');

      fetch('ajax-fulfillment-toggle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + encodeURIComponent(id),
        cache: 'no-store'
      })
      .then(() => reloadTable())
      .catch(() => reloadTable());
    });

    // Bulk mark helpers (with confirm)
    function bulkSet(target){
      const ids = selectedIds();
      if (!ids.length) { alert('Select at least one order.'); return; }
      if (!confirm('Are you sure you want to mark the selected orders as "' + target + '"?')) return;

      const fd = new FormData();
      fd.append('op', 'bulk_set');
      fd.append('fulfillment', target);
      fd.append('ids', ids.join(','));

      fetch('<?= e(basename($_SERVER["PHP_SELF"])) ?>', { method:'POST', body: fd, cache:'no-store' })
        .then(r => r.json())
        .then(j => {
          if (!j || !j.ok) throw new Error(j?.msg || j?.error || 'Failed');
          reloadTable();
        })
        .catch(err => alert(err.message || 'Error'));
    }
    btnDelivered?.addEventListener('click', () => bulkSet('delivered'));
    btnProcessing?.addEventListener('click', () => bulkSet('processing'));

    // Row delete buttons
    function bindRowActions(){
      document.querySelectorAll('#orders-body .js-delete-order').forEach(btn => {
        btn.addEventListener('click', function(){
          const tr = this.closest('tr');
          const id = tr?.getAttribute('data-id');
          if (!id) return;
          if (!confirm('Delete this order permanently?')) return;

          const fd = new FormData();
          fd.append('op','delete');
          fd.append('id', id);

          fetch('<?= e(basename($_SERVER["PHP_SELF"])) ?>', { method:'POST', body: fd, cache:'no-store' })
            .then(r => r.json())
            .then(j => {
              if (!j || !j.ok) throw new Error(j?.error || 'Delete failed');
              tr.remove();
            })
            .catch(err => alert(err.message || 'Failed to delete.'));
        });
      });
    }

    // Initial binds
    initItemPopovers();
    bindRowCheckboxes();
    bindRowActions();
  </script>
</body>
</html>
