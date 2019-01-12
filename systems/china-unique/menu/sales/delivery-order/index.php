<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once SYSTEM_PATH . "includes/php/actions.php";
  include "process.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_DELIVERY_ORDER_PRINTOUT_TITLE; ?></div>
      <?php if (isset($doHeader)) : ?>
        <form method="post">
          <table id="do-header">
            <tr>
              <td>Order No.:</td>
              <td><input type="text" name="do_no" value="<?php echo $doHeader["do_no"]; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="do_date" value="<?php echo $doHeader["do_date"]; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td><?php echo $doHeader["debtor_code"] . " - " . $doHeader["debtor_name"]; ?></td>
              <td>Currency:</td>
              <td><?php echo $doHeader["currency_code"] . " @ " . $doHeader["exchange_rate"]; ?></td>
            </tr>
            <tr>
              <td>Address:</td>
              <td><textarea name="address"><?php echo $doHeader["debtor_address"]; ?></textarea></td>
              <td>Discount:</td>
              <td><?php echo $doHeader["discount"]; ?>%</td>
            </tr>
            <tr>
              <td>Contact:</td>
              <td><input type="text" name="contact" value="<?php echo $doHeader["debtor_contact"]; ?>" required /></td>
              <td>Status:</td>
              <td><?php echo $doHeader["status"]; ?></td>
            </tr>
            <tr>
              <td>Tel:</td>
              <td><input type="text" name="tel" value="<?php echo $doHeader["debtor_tel"]; ?>" required /></td>
            </tr>
          </table>
          <?php if (count($doModels) > 0) : ?>
            <table id="do-models">
              <thead>
                <tr></tr>
                <tr>
                  <th>DO No. / On Hand</th>
                  <th>Order No.</th>
                  <th>Brand</th>
                  <th>Model No.</th>
                  <th class="number">Price</th>
                  <th class="number">Qty</th>
                  <th class="number">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalQty = 0;
                  $subtotalSum = 0;
                  $discount = $doHeader["discount"];
                  $hasIncoming = false;

                  for ($i = 0; $i < count($doModels); $i++) {
                    $doModel = $doModels[$i];
                    $iaNo = $doModel["ia_no"];
                    $soId = $doModel["so_id"];
                    $soNo = $doModel["so_no"];
                    $brand = $doModel["brand"];
                    $modelNo = $doModel["model_no"];
                    $price = $doModel["price"];
                    $qty = $doModel["qty"];
                    $subtotal = $qty * $price;
                    $status = "On Hand";

                    if (assigned($iaNo)) {
                      $status = $iaNo;
                      $hasIncoming = true;
                    }

                    $totalQty += $qty;
                    $subtotalSum += $subtotal;

                    echo "
                      <tr>
                        <td title=\"$status\">$status</td>
                        <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a></td>
                        <td title=\"$brand\">$brand</td>
                        <td title=\"$modelNo\">$modelNo</td>
                        <td title=\"$price\" class=\"number\">" . number_format($price, 2) . "</td>
                        <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                        <td title=\"$subtotal\" class=\"number\">" . number_format($subtotal, 2) . "</td>
                      </tr>
                    ";
                  }
                ?>
                <?php if ($discount > 0) : ?>
                  <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <th></th>
                    <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                  </tr>
                  <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="number">Discount <?php echo $discount; ?>%</td>
                    <td class="number"><?php echo number_format($subtotalSum * $discount / 100, 2); ?></td>
                  </tr>
                <?php endif ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
                </tr>
              </tbody>
            </table>
          <?php else: ?>
            <div id="do-models-no-results">No models</div>
          <?php endif ?>
          <table id="do-footer">
            <tr>
              <td>Invoice No.:</td>
              <td><input id="ref-no" name="invoice_no" value="<?php echo $doHeader["invoice_no"]; ?>" /></td>
            </tr>
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $doHeader["remarks"]; ?></textarea></td>
            </tr>
          </table>
          <?php if ($doHeader["status"] == "SAVED") : ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $doHeader["status"]; ?>" formaction="<?php echo SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$id"; ?>">Print</button>
          <?php if ($doHeader["status"] == "SAVED" && !$hasIncoming) : ?>
            <button name="status" type="submit" value="POSTED">Post</button>
          <?php endif ?>
          <?php if ($doHeader["status"] == "SAVED") : ?>
            <button name="status" type="submit" value="DELETED">Delete</button>
          <?php endif ?>
        </form>
      <?php else: ?>
        <div id="do-not-found"><?php echo SALES_DELIVERY_ORDER_PRINTOUT_TITLE; ?> not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
