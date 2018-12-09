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
      <div class="headline"><?php echo SALES_ORDER_INTERNAL_PRINTOUT_TITLE ?></div>

      <?php if (isset($soHeader)): ?>
        <table id="so-header">
          <tr>
            <td>Order No.:</td>
            <td><?php echo $soHeader["so_no"]; ?></td>
            <td>Date:</td>
            <td><?php echo $soHeader["date"]; ?></td>
          </tr>
          <tr>
            <td>Client:</td>
            <td><?php echo $soHeader["customer"]; ?></td>
            <td>Currency:</td>
            <td><?php echo $soHeader["currency"]; ?></td>
          </tr>
          <tr>
            <td>Discount:</td>
            <td><?php echo $soHeader["discount"]; ?>%</td>
            <td>Status:</td>
            <td><?php echo $soHeader["status"]; ?></td>
          </tr>
        </table>
        <?php if (count($soModels) > 0) : ?>
          <table id="so-models">
            <thead>
              <tr></tr>
              <tr>
                <th>Brand</th>
                <th>Model No.</th>
                <th class="number">Selling Price</th>
                <th class="number">Qty</th>
                <th class="number">OutStanding Qty</th>
                <th class="number">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyOutstanding = 0;
                $subtotalSum = 0;
                $discount = $soHeader["discount"];

                for ($i = 0; $i < count($soModels); $i++) {
                  $soModel = $soModels[$i];
                  $brand = $soModel["brand"];
                  $modelNo = $soModel["model_no"];
                  $price = $soModel["price"];
                  $qty = $soModel["qty"];
                  $qtyOutstanding = $soModel["qty_outstanding"];
                  $subtotal = $soModel["subtotal"];

                  $totalQty += $qty;
                  $totalQtyOutstanding += $qtyOutstanding;
                  $subtotalSum += $subtotal;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td class=\"number\">" . number_format($price, 2) . "</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
                      <td class=\"number\">" . number_format($qtyOutstanding) . "</td>
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
                  <th></th>
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
                <th class="number"><?php echo number_format($totalQtyOutstanding); ?></th>
                <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
              </tr>
            </tfoot>
          </table>
        <?php else: ?>
          <div id="so-models-no-results">No models</div>
        <?php endif ?>
        <table id="so-footer">
          <?php if (assigned($soHeader["remarks"])) : ?>
            <tr>
              <td>Remarks:</td>
              <td><?php echo $soHeader["remarks"]; ?></td>
            </tr>
          <?php endif ?>
        </table>
        <?php echo "
          <div class=\"web-only\">
          " . generateRedirectButton(SALES_ORDER_PRINTOUT_URL, "External printout") . "
          </div>
        ";?>
      <?php else: ?>
        <div id="so-not-found">Sales order not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
