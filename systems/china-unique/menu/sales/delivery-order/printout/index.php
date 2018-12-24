<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
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
      <div class="headline"><?php echo DELIVERY_ORDER_PRINTOUT_TITLE . " (" . $doHeader["warehouse"] . ")" ?></div>

      <?php if (isset($doHeader)): ?>
        <table id="do-header">
          <tr>
            <td>Order No.:</td>
            <td><?php echo $doHeader["do_no"]; ?></td>
          </tr>
          <tr>
            <td>Client:</td>
            <td><?php echo $doHeader["customer_name"]; ?></td>
          </tr>
          <tr>
            <td>Address:</td>
            <td><?php echo $doHeader["customer_address"]; ?></td>
          </tr>
          <tr>
            <td>Contact:</td>
            <td><?php echo $doHeader["customer_contact"]; ?></td>
          </tr>
          <tr>
            <td>Tel:</td>
            <td><?php echo $doHeader["customer_tel"]; ?></td>
          </tr>
          <tr>
            <td>Date:</td>
            <td><?php echo $doHeader["date"]; ?></td>
          </tr>
        </table>
        <?php if (count($doModels) > 0) : ?>
          <table id="do-models">
            <thead>
              <tr></tr>
              <tr>
                <th>Brand</th>
                <th>Model No.</th>
                <th>Order No.</th>
                <th class="number">Qty</th>
                <th class="number">Unit Price</th>
                <th class="number">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $subtotalSum = 0;
                $discount = $doHeader["discount"];

                for ($i = 0; $i < count($doModels); $i++) {
                  $doModel = $doModels[$i];
                  $brand = $doModel["brand"];
                  $modelNo = $doModel["model_no"];
                  $soNo = $doModel["so_no"];
                  $qty = $doModel["qty"];
                  $price = $doModel["price"];
                  $subtotal = $qty * $price;

                  $totalQty += $qty;
                  $subtotalSum += $subtotal;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td>$soNo</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
                      <td class=\"number\">" . number_format($price, 2) . "</td>
                      <td class=\"number\">" . number_format($subtotal, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
            <tfoot>
              <?php if ($discount > 0) : ?>
                <tr>
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
                  <td class="number">Discount <?php echo $discount; ?>%</td>
                  <td class="number"><?php echo number_format($subtotalSum * $discount / 100, 2); ?></td>
                </tr>
              <?php endif ?>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th></th>
                <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
              </tr>
            </tfoot>
          </table>
        <?php else: ?>
          <div id="do-models-no-results">No models</div>
        <?php endif ?>
        <table id="do-footer">
          <?php if (assigned($doHeader["invoice_no"])) : ?>
            <tr>
              <td>Invoice No.:</td>
              <td><?php echo $doHeader["invoice_no"]; ?></td>
            </tr>
          <?php endif ?>
          <?php if (assigned($doHeader["remarks"])) : ?>
            <tr>
              <td>Remarks:</td>
              <td><?php echo $doHeader["remarks"]; ?></td>
            </tr>
          <?php endif ?>
        </table>
        <?php echo "
          <div class=\"web-only\">
          " . generateRedirectButton(DELIVERY_ORDER_INTERNAL_PRINTOUT_URL, "Internal printout") . "
          </div>
        ";?>
      <?php else: ?>
        <div id="do-not-found"><?php echo DELIVERY_ORDER_PRINTOUT_TITLE; ?> not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
