<?php
  $page_title = 'Add Sale';
  require_once('includes/load.php');
  // Check which level user has permission to view this page
  page_require_level(3);
?>

<?php
  if (isset($_POST['add_sale'])) {
    $req_fields = array('s_id','quantity','price','total','date');
    validate_fields($req_fields);
    if (empty($errors)) {
      // Cast integers to ensure numeric context in PostgreSQL
      $p_id    = (int)$db->escape($_POST['s_id']);
      $s_qty   = (int)$db->escape($_POST['quantity']);
      // If 'total' is numeric, you can store it as a string or cast to numeric.
      $s_total = $db->escape($_POST['total']);
      
      // If you want to use the user-supplied date, do so:
      // $date      = $db->escape($_POST['date']);
      // But in your code, you used make_date() instead:
      $s_date  = make_date(); // e.g. "2024-12-26 10:35:00"

      // Build the INSERT for PostgreSQL
      // Use the integer variables directly (no quotes around integers).
      $sql  = "INSERT INTO sales (product_id, qty, price, date)";
      $sql .= " VALUES (";
      $sql .= " {$p_id},";         // integer
      $sql .= " {$s_qty},";        // integer
      $sql .= " '{$s_total}',";    // string or cast if numeric(25,2): '{$s_total}'::numeric
      $sql .= " '{$s_date}'";      // date or timestamp
      $sql .= " )";

      if ($db->query($sql)) {
      
        update_product_qty($s_qty, $p_id);

        $session->msg('s', "Sale added successfully.");
        redirect('add_sale.php', false);
      } else {
        $session->msg('d', 'Sorry, failed to add sale!');
        redirect('add_sale.php', false);
      }

    } else {
      $session->msg("d", $errors);
      redirect('add_sale.php', false);
    }
  }
?>

<?php include_once('layouts/header.php'); ?>
<div class="row">
  <div class="col-md-6">
    <?php echo display_msg($msg); ?>
    <form method="post" action="ajax.php" autocomplete="off" id="sug-form">
      <div class="form-group">
        <div class="input-group">
          <span class="input-group-btn">
            <button type="submit" class="btn btn-primary">Find It</button>
          </span>
          <input type="text" id="sug_input" class="form-control" name="title" placeholder="Search for product name">
        </div>
        <div id="result" class="list-group"></div>
      </div>
    </form>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="panel panel-default">
      <div class="panel-heading clearfix">
        <strong><span class="glyphicon glyphicon-th"></span><span>Sale Edit</span></strong>
      </div>
      <div class="panel-body">
        <form method="post" action="add_sale.php">
          <table class="table table-bordered">
            <thead>
              <th>Item</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Total</th>
              <th>Date</th>
              <th>Action</th>
            </thead>
            <tbody id="product_info"></tbody>
          </table>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include_once('layouts/footer.php'); ?>
