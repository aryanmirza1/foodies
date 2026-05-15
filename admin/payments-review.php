<?php
// admin/payments-review.php — list orders waiting for admin decision (NEW/PENDING for both COD & Online)
require_once("../assets/inc/admin-top.php");
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn,'utf8mb4'); }

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function media_url($p){
  $p = trim((string)$p); if ($p==='') return '';
  if (preg_match('#^(?:https?:)?//#i', $p)) return $p;       // absolute http(s)
  $p = str_replace('\\','/',$p); $p = ltrim($p,'/');         // normalize
  return (strpos($p,'../')===0) ? $p : '../'.$p;             // this file lives in /admin
}

/* Only NEW / PENDING orders (any payment method) */
$sql = "SELECT id, contact_name, contact_email, contact_phone, payment_method, status, payment_proof, created_at
        FROM `order`
        WHERE status IN ('new','pending')
        ORDER BY created_at DESC";
$q = mysqli_query($conn, $sql);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>New Orders</title>
  <style>
    .thumb{width:40px;height:40px;border:1px solid #ddd;border-radius:6px;object-fit:cover}
    .dt-empty-row td{ color:#6c757d; text-align:center; padding:1.25rem 0; }
    th.select-col, td.select-col { width: 36px; }
    .btn-bulk{ white-space:nowrap }

    /* Toolbar (aligned to the right like your Manage Orders page) */
    .review-toolbar{
      display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-bottom:.5rem;
    }
    .review-toolbar .spacer{ flex:1; }
    .actions-right{ display:inline-flex; gap:.5rem; align-items:center; }
    .actions-right .form-check-label{ display:inline-flex; align-items:center; gap:.35rem; margin:0; }
    .dt-search-slot{ display:inline-flex; margin-left:.5rem; }
  </style>
</head>
<body>
<?php require_once("../assets/inc/admin-sidebar.php"); ?>
<div class="page">
  <?php require_once("../assets/inc/admin-header.php"); ?>

  <section class="py-5">
    <div class="container-fluid">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h3 class="h3 m-0" style="color:#6E3B16;">
            <i class="fas fa-shield-check me-2"></i> New Orders
          </h3>
        </div>

        <div class="card-body">
          <?php if (!empty($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>

          <!-- Toolbar row: pushed to the right; Search sits at the far right -->
          <div class="review-toolbar">
            <div class="spacer"></div>
            <div class="actions-right">
              <label class="form-check-label me-2">
                <input type="checkbox" id="chkAllTop" class="form-check-input">
                <span>Select all</span>
              </label>
              <button type="button" id="bulkApprove" class="btn btn-success btn-sm btn-bulk">Approve Selected</button>
              <button type="button" id="bulkReject"  class="btn btn-outline-danger btn-sm btn-bulk">Reject Selected</button>
            </div>
            <div class="dt-search-slot" id="dtSearchSlot"></div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="ordersTable">
              <thead style="background:#6E3B16;color:#fff">
                <tr>
                  <th class="select-col text-center"><input type="checkbox" id="chkAll"></th>
                  <th>Order</th>
                  <th>Customer</th>
                  <th>Method</th>
                  <th>Proof</th>
                  <th>Placed</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
              <?php if ($q && mysqli_num_rows($q)): ?>
                <?php while($r = mysqli_fetch_assoc($q)):
                  $id    = (int)$r['id'];
                  $pm    = strtolower($r['payment_method'] ?? '');
                  $proof = trim((string)($r['payment_proof'] ?? ''));
                ?>
                  <tr data-id="<?= $id ?>">
                    <td class="select-col text-center">
                      <input type="checkbox" class="row-check" value="<?= $id ?>">
                    </td>
                    <td><span class="badge bg-secondary">#<?= $id ?></span></td>
                    <td>
                      <strong><?= e($r['contact_name'] ?? '—') ?></strong><br>
                      <small class="text-muted"><?= e($r['contact_phone'] ?? '—') ?></small>
                      <?php if (!empty($r['contact_email'])): ?>
                        <div class="text-muted small"><?= e($r['contact_email']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td><?= ($pm === 'online' ? 'Online' : 'COD') ?></td>
                    <td>
                      <?php if ($pm==='online' && $proof!==''): $u = media_url($proof); ?>
                        <a href="<?= e($u) ?>" target="_blank" title="View proof"><img class="thumb" src="<?= e($u) ?>" alt="proof"></a>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-muted small">
                      <?= !empty($r['created_at']) ? e(date('M d, Y H:i', strtotime($r['created_at']))) : '—' ?>
                    </td>
                    <td class="text-end">
                      <form action="payment-review-action.php" method="post" class="d-inline frm-approve">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="approve">
                        <button class="btn btn-sm btn-success">Approve</button>
                      </form>
                      <form action="payment-review-action.php" method="post" class="d-inline frm-reject"
                            onsubmit="return confirm('Reject & delete this order? This cannot be undone.')">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="reject">
                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <!-- keep td count = th count -->
                <tr class="dt-empty-row">
                  <td>—</td>
                  <td>No orders awaiting review.</td>
                  <td>—</td>
                  <td>—</td>
                  <td>—</td>
                  <td>—</td>
                  <td>—</td>
                </tr>
              <?php endif; ?>
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
// ---------- checkbox sync ----------
const headerChk = document.getElementById('chkAll');
const topChk    = document.getElementById('chkAllTop');
const rowChecks = () => Array.from(document.querySelectorAll('.row-check'));
const selectedIds = () => rowChecks().filter(c => c.checked).map(c => c.value);

function syncMasters(){
  const rows = rowChecks();
  const on   = rows.filter(c=>c.checked).length;
  [headerChk, topChk].forEach(el=>{
    if (!el) return;
    el.indeterminate = on>0 && on<rows.length;
    el.checked = rows.length>0 && on===rows.length;
  });
}
headerChk?.addEventListener('change', ()=>{
  rowChecks().forEach(c => c.checked = headerChk.checked);
  if (topChk){ topChk.checked = headerChk.checked; topChk.indeterminate = false; }
});
topChk?.addEventListener('change', ()=>{
  rowChecks().forEach(c => c.checked = topChk.checked);
  if (headerChk){ headerChk.checked = topChk.checked; headerChk.indeterminate = false; }
});
document.addEventListener('change', e=>{
  if (e.target.classList?.contains('row-check')) syncMasters();
});

// ---------- bulk actions ----------
async function bulkAction(kind){
  const ids = selectedIds();
  if (!ids.length) { alert('Select at least one order.'); return; }
  if (kind==='reject' && !confirm('Reject & delete selected orders? This cannot be undone.')) return;

  const approveBtn = document.getElementById('bulkApprove');
  const rejectBtn  = document.getElementById('bulkReject');
  [approveBtn, rejectBtn].forEach(b=>b && (b.disabled = true));

  for (const id of ids){
    const fd = new FormData();
    fd.append('id', id);
    fd.append('action', kind); // 'approve' or 'reject'
    try {
      await fetch('payment-review-action.php', { method:'POST', body: fd, credentials:'same-origin' });
    } catch(e) { console.error('Bulk action failed for', id, e); }
  }
  location.reload();
}
document.getElementById('bulkApprove')?.addEventListener('click', ()=>bulkAction('approve'));
document.getElementById('bulkReject') ?.addEventListener('click', ()=>bulkAction('reject'));

// ---------- move the DataTables Search into our toolbar (far right) ----------
(function moveDtSearch(attempt=0){
  const slot   = document.getElementById('dtSearchSlot');
  const search = document.querySelector('#ordersTable_filter'); // default DT id
  if (slot && search) { slot.appendChild(search); return; }
  if (attempt < 40) setTimeout(()=>moveDtSearch(attempt+1), 150);
})();
</script>
</body>
</html>
