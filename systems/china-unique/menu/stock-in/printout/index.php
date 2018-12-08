<?php
  define("SYSTEM_PATH", "../../../");

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
      <div class="headline"><?php echo STOCK_IN_PRINTOUT_TITLE ?></div>

      <?php if (isset($stockInHeader)): ?>
        <table id="stock-in-header">
          <tr>
            <td>Voucher No.:</td>
            <td><?php echo $stockInHeader["stock_in_no"]; ?></td>
            <td>Date:</td>
            <td><?php echo $stockInHeader["date"]; ?></td>
          </tr>
          <tr>
            <td>Transaction Code:</td>
            <td><?php echo $stockInHeader["transaction_type"]; ?></td>
            <td>Warehouse:</td>
            <td><?php echo $stockInHeader["warehouse"]; ?></td>
          </tr>
          <tr>
            <td>Creditor:</td>
            <td><?php echo $stockInHeader["creditor"]; ?></td>
            <td>Currency:</td>
            <td><?php echo $stockInHeader["currency"]; ?></td>
          </tr>
          <tr>
            <td>Discount:</td>
            <td><?php echo $stockInHeader["discount"]; ?>%</td>
            <td>Net Amount:</td>
            <td><?php echo $stockInHeader["net_amount"]; ?></td>
          </tr>
          <tr>
            <td>Status:</td>
            <td><?php echo $stockInHeader["status"]; ?></td>
          </tr>
        </table>
        <?php if (count($stockInModels) > 0) : ?>
          <table id="stock-in-models">
            <thead>
              <tr></tr>
              <tr>
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
                $discount = $stockInHeader["discount"];

                for ($i = 0; $i < count($stockInModels); $i++) {
                  $stockInModel = $stockInModels[$i];
                  $brand = $stockInModel["brand"];
                  $modelNo = $stockInModel["model_no"];
                  $price = $stockInModel["price"];
                  $qty = $stockInModel["qty"];
                  $subtotal = $stockInModel["subtotal"];

                  $totalQty += $qty;
                  $subtotalSum += $subtotal;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td class=\"number\">" . number_format($price, 2) . "</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
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
                  <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                </tr>
                <tr>
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
                <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
              </tr>
            </tfoot>
          </table>
        <?php else: ?>
          <div id="stock-in-models-no-results">No models</div>
        <?php endif ?>
        <?php if (assigned($remarks)) : ?>
          <table id="stock-in-footer">
            <tr>
              <td>Remarks:</td>
              <td><?php echo $remarks; ?></td>
            </tr>
          </table>
        <?php endif ?>
      <?php else: ?>
        <div id="stock-in-not-found">Stock in voucher not found</div>
      <?php endif ?>
    </div>
  </body>
</html>