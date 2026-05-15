<?php
// admin/orders-dashboard.php
session_start();

/* ---------- EARLY AJAX (no layout prints here) ---------- */
require_once(__DIR__ . '/../includes/db.php'); // must define $conn (mysqli)
if (function_exists('mysqli_set_charset')) {
    @mysqli_set_charset($conn, 'utf8mb4');
}

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 2); }

function parse_dt(?string $v): ?string {
    $v = trim((string)$v);
    if ($v === '') return null;
    $v = str_replace('T', ' ', $v);
    if (preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2})?$/', $v)) {
        if (strlen($v) === 10) $v .= ' 00:00';
        return $v . ':00';
    }
    return null;
}

/* ----- Items helpers ----- */
function items_preview($json){
    if (!$json) return '—';
    $arr = json_decode((string)$json, true);
    if (!is_array($arr)) return '—';
    $out = []; $shown = 0;
    foreach ($arr as $it) {
        $name = $it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item';
        $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
        $out[] = e($name) . ' × ' . $qty;
        if (++$shown >= 2) break;
    }
    $more = max(0, count($arr) - $shown);
    return $out ? implode(', ', $out) . ($more ? " +{$more} more" : "") : '—';
}

/* Full list HTML for popover (safe, escaped names) */
function items_full_html($json): string{
    $arr = json_decode((string)$json, true);
    if (!is_array($arr) || !$arr) return '<div class="text-muted">No items.</div>';
    $h = '<div style="min-width:220px"><ul class="list-unstyled m-0">';
    foreach ($arr as $it) {
        $name = e($it['name'] ?? $it['product_name'] ?? $it['title'] ?? 'Item');
        $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
        if ($qty < 0) $qty = 0;
        $h .= '<li class="d-flex justify-content-between border-bottom py-1">'
            . '<span>' . $name . '</span>'
            . '<span class="fw-semibold">× ' . $qty . '</span>'
            . '</li>';
    }
    return $h . '</ul></div>';
}

function payment_badge($pm, $status){
    $pm = strtolower((string)$pm);
    $st = strtolower((string)$status);
    if ($pm === 'cod')       return '<span class="badge bg-secondary">COD</span>';
    if ($st === 'paid')      return '<span class="badge bg-success">Paid</span>';
    if ($st === 'cancelled') return '<span class="badge bg-danger">Rejected</span>';
    return '<span class="badge bg-warning text-dark">Pending</span>';
}

/* Render table rows. Items column now includes a hidden <div> with full list for popover. */
function render_rows(array $rows): string{
    ob_start();
    $i = 1;
    foreach ($rows as $r) {
        $id   = (int)$r['id'];
        $name = $r['contact_name'] ?? '—';
        $ph   = $r['contact_phone'] ?? '—';
        $city = $r['address_city'] ?? '—';
        $tot  = money($r['total'] ?? 0);
        $pm   = $r['payment_method'] ?? 'cod';
        $st   = $r['status'] ?? '';
        $fs   = strtolower((string)($r['fulfillment_status'] ?? 'processing'));
        $fsBadge = ($fs === 'delivered')
            ? '<span class="badge bg-success">Delivered</span>'
            : '<span class="badge bg-primary">Processing</span>';
        $created = !empty($r['created_at']) ? date('M d, Y H:i', strtotime($r['created_at'])) : '—';

        $preview = items_preview($r['items_json'] ?? '');
        $full    = items_full_html($r['items_json'] ?? '');
        ?>
        <tr data-id="<?= $id ?>">
            <td><?= $i++ ?></td>
            <td><span class="badge bg-secondary">#<?= $id ?></span></td>
            <td><strong><?= e($name) ?></strong><br><small class="text-muted"><?= e($ph) ?></small></td>
            <td><?= e($city) ?></td>
            <td>
                <span class="order-items text-decoration-underline" role="button" tabindex="0">
                    <?= $preview ?>
                </span>
                <div class="d-none items-popover"><?= $full ?></div>
            </td>
            <td><strong>$ <?= $tot ?></strong></td>
            <td><?= payment_badge($pm, $st) ?></td>
            <td><?= $fsBadge ?></td>
            <td class="text-muted small"><?= e($created) ?></td>
        </tr>
        <?php
    }
    return ob_get_clean();
}

function items_totals(array $rows): array{
    $totalQty = 0; $by = [];
    foreach ($rows as $r) {
        $arr = json_decode($r['items_json'] ?? '[]', true);
        if (!is_array($arr)) continue;
        foreach ($arr as $it) {
            $name = trim((string)($it['name'] ?? $it['title'] ?? 'Item'));
            $qty  = (int)($it['qty'] ?? $it['quantity'] ?? 1);
            if ($qty < 0) $qty = 0;
            $totalQty += $qty;
            if ($name !== '') $by[$name] = ($by[$name] ?? 0) + $qty;
        }
    }
    arsort($by);
    return [$totalQty, $by];
}
function render_items_breakdown(array $by): string{
    if (!$by) return '<div class="text-muted">No data.</div>';
    $h = '<ul class="list-unstyled m-0">';
    foreach ($by as $name => $qty) {
        $h .= '<li class="d-flex justify-content-between border-bottom py-1"><span>' . e($name) . '</span><span class="fw-semibold">' . (int)$qty . '</span></li>';
    }
    return $h . '</ul>';
}

function build_where(mysqli $conn, array $f): string {
  $w = ["status='paid'"]; // show only confirmed orders

  // optional payment filter
  switch ($f['payment'] ?? 'all') {
    case 'cod':    $w[] = "(payment_method='cod')"; break;
    case 'online': $w[] = "(payment_method='online')"; break;
    default: /* all paid */ ;
  }

  // fulfillment filter
  switch ($f['fulfill'] ?? 'all') {
    case 'active':    $w[] = "(COALESCE(fulfillment_status,'processing')<>'delivered')"; break;
    case 'completed': $w[] = "(fulfillment_status='delivered')"; break;
  }

  if (!empty($f['from'])) $w[] = "created_at >= '" . mysqli_real_escape_string($conn, $f['from']) . "'";
  if (!empty($f['to']))   $w[] = "created_at <= '" . mysqli_real_escape_string($conn, $f['to']) . "'";
  return 'WHERE '.implode(' AND ', $w);
}
function get_rows(mysqli $conn, string $where): array{
    $rows = [];
    $sql = "SELECT id, contact_name, contact_phone, address_city, items_json, total, payment_method, status, fulfillment_status, created_at
            FROM `order` {$where} ORDER BY created_at DESC";
    if ($q = mysqli_query($conn, $sql)) {
        while ($r = mysqli_fetch_assoc($q)) $rows[] = $r;
        mysqli_free_result($q);
    }
    return $rows;
}

$ajax = (($_POST['ajax'] ?? $_GET['ajax'] ?? '') === '1');
if ($ajax) {
    $payment = $_POST['payment'] ?? $_GET['payment'] ?? 'all';
    $fulfill = $_POST['fulfill'] ?? $_GET['fulfill'] ?? 'active'; // default ACTIVE
    $from    = parse_dt($_POST['from'] ?? $_GET['from'] ?? '');
    $to      = parse_dt($_POST['to']   ?? $_GET['to']   ?? '');

    $where = build_where($conn, ['payment' => $payment, 'fulfill' => $fulfill, 'from' => $from, 'to' => $to]);
    $rows  = get_rows($conn, $where);

    // Active = not delivered
    $active = array_values(array_filter($rows, fn($r) =>
        strtolower((string)($r['fulfillment_status'] ?? 'processing')) !== 'delivered'
    ));
    [$itemsTotal, $byName] = items_totals($active);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'rows_html' => render_rows($rows),
        'active_orders' => count($active),
        'items_active_total' => (int)$itemsTotal,
        'items_html' => render_items_breakdown($byName),
        'count_filtered' => count($rows),
    ]);
    exit;
}

/* ---------- NORMAL PAGE RENDER ---------- */
require_once(__DIR__ . '/../assets/inc/admin-top.php'); // layout
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Orders Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root { --accent:#6E3B16; }
        .metric{background:#fff;border:1px solid #eee;border-radius:14px;padding:14px}
        .metric .label{font-size:12px;color:#6c757d}
        .metric .value{font-weight:800;font-size:26px;color:#222}
        .card-clean{border:1px solid #eee;border-radius:14px;overflow:hidden}
        .card-clean>.card-header{background:#fff}
        .order-items{cursor:pointer}
    </style>
</head>
<body>
<?php require_once(__DIR__ . '/../assets/inc/admin-sidebar.php'); ?>
<div class="page">
    <?php require_once(__DIR__ . '/../assets/inc/admin-header.php'); ?>

    <section class="py-4">
        <div class="container-fluid">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h3 m-0" style="color:var(--accent)"><i class="fas fa-chart-line me-2"></i>Orders Dashboard</h3>
                <a class="btn btn-sm btn-outline-secondary" href="manage-orders.php"><i class="fas fa-list me-1"></i> Manage Orders</a>
            </div>

            <!-- Filters -->
            <form id="filters" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small text-muted">Payment</label>
                    <select name="payment" class="form-select form-select-sm">
                        <option value="all">All (visible)</option>
                        <option value="cod">COD</option>
                        <option value="online_approved">Online • Approved</option>
                        <option value="online_pending">Online • Pending</option>
                        <option value="online_rejected">Online • Rejected</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted">Fulfillment</label>
                    <select name="fulfill" class="form-select form-select-sm">
                        <option value="all">All</option>
                        <option value="active" selected>Active</option><!-- default ACTIVE -->
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted">From (date &amp; time)</label>
                    <input type="datetime-local" name="from" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted">To (date &amp; time)</label>
                    <input type="datetime-local" name="to" class="form-control form-control-sm">
                </div>
                <div class="col-auto d-flex gap-2">
                    <button class="btn btn-sm btn-success" type="submit"><i class="fas fa-filter me-1"></i> Apply</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="btnReset">Reset</button>
                </div>
            </form>

            <!-- Metrics -->
            <div class="row g-3 my-3">
                <div class="col-md-4">
                    <div class="metric">
                        <div class="label">Active Orders (filtered by payment/date)</div>
                        <div class="value" id="mActive">0</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric">
                        <div class="label">Total Items in Active Orders</div>
                        <div class="value" id="mItems">0</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric">
                        <div class="label">Orders (table count)</div>
                        <div class="value" id="mCount">0</div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="card-clean">
                        <div class="card-header p-3 d-flex justify-content-between align-items-center">
                            <strong>Orders</strong>
                        </div>
                        <div class="card-body ">
                            <div class="table-responsive">
                                <table id="ordersTable" class="table align-middle mb-0" style="width:100%">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Order</th>
                                            <th>Customer</th>
                                            <th>City</th>
                                            <th>Items</th>
                                            <th>Total ($)</th>
                                            <th>Payment</th>
                                            <th>Fulfillment</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody><!-- start empty to keep DataTables happy --></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 ">
                    <div class="card-clean h-100">
                        <div class="card-header p-3"><strong>Items in Active Orders</strong></div>
                        <div class="card-body p-3" id="itemsBox">Loading…</div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <?php require_once(__DIR__ . '/../assets/inc/admin-footer.php'); ?>
</div>
<?php require_once(__DIR__ . '/../assets/inc/admin-bottom.php'); ?>

<script>
/* Requires jQuery + DataTables + Bootstrap (already in your admin bundle) */
(function($) {
    const $f = $('#filters');
    const $items = $('#itemsBox');
    const $mActive = $('#mActive'), $mItems = $('#mItems'), $mCount = $('#mCount');
    const $table = $('#ordersTable');
    let dt;

    function ensureDT() {
        if ($.fn.DataTable.isDataTable($table)) {
            dt = $table.DataTable();
            return dt;
        }
        dt = $table.DataTable({
            order: [[8, 'desc']], // by Created
            pageLength: 10,
            lengthMenu: [[10,25,50,100],[10,25,50,100]],
            autoWidth: false,
            responsive: true,
            language: { search: 'Search:', lengthMenu: 'Show _MENU_' }
        });
        // Re-init popovers when table redraws (paging, sort, search)
        $table.on('draw.dt', initItemPopovers);
        return dt;
    }

    function initItemPopovers(){
        document.querySelectorAll('#ordersTable .order-items').forEach(el => {
            const html = el.parentElement.querySelector('.items-popover')?.innerHTML || '';
            const existing = bootstrap.Popover.getInstance(el);
            if (existing) existing.dispose();
            if (!html) return;
            new bootstrap.Popover(el, {
                container: 'body',
                html: true,
                trigger: 'hover focus click', // hover or click
                placement: 'auto',
                content: html
            });
        });
    }

    function load() {
        $items.text('Loading…');
        const fd = new FormData($f[0]);
        fd.append('ajax', '1');

        fetch('orders-dashboard.php', { method: 'POST', body: fd })
          .then(r => r.json())
          .then(j => {
              if (!j || !j.ok) throw 0;

              // metrics + side card
              $mActive.text(j.active_orders ?? 0);
              $mItems.text(j.items_active_total ?? 0);
              $mCount.text(j.count_filtered ?? 0);
              $items.html(j.items_html || '<div class="text-muted">No data.</div>');

              // table via DataTables API
              const api = ensureDT();
              api.clear();

              // convert HTML rows → nodes then add
              const tmp = document.createElement('tbody');
              tmp.innerHTML = j.rows_html || '';
              $(tmp).children('tr').each(function(){ api.row.add(this); });
              api.draw(false);

              // popovers for newly inserted rows
              initItemPopovers();
          })
          .catch(() => {
              $items.html('<div class="text-danger">Failed to load.</div>');
              ensureDT().clear().draw();
              $mActive.text('0'); $mItems.text('0'); $mCount.text('0');
          });
    }

    // Apply + Reset + live reload on changes
    $f.on('submit', function(e){ e.preventDefault(); load(); });
    $('#btnReset').on('click', function(){ $f[0].reset(); load(); });
    $f.find('select,input[type="datetime-local"]').on('change', function(){ load(); });

    // first run (fulfillment defaults to "Active" in HTML)
    $(function(){ ensureDT(); load(); });
})(jQuery);
</script>
</body>
</html>
