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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php if (count($stockOutHeaders) > 0) : ?>
        <?php foreach($stockOutHeaders as &$stockOutHeader) : ?>
          <?php $transCode = $stockOutHeader["transaction_code"]; ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo STOCK_OUT_PRINTOUT_TITLE ?></div>
            <table class="stock-out-header">
              <tr>
                <td>Voucher No.:</td>
                <td><?php echo $stockOutHeader["stock_out_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $stockOutHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>Transaction Code:</td>
                <td><?php echo $stockOutHeader["transaction_type"]; ?></td>
                <td>Warehouse:</td>
                <td><?php echo $stockOutHeader["warehouse"]; ?></td>
              </tr>
              <tr>
                <?php if ($transCode === "S1" || $transCode === "S3" || $transCode === "S7" || $transCode === "S8") : ?>
                  <td>Client:</td>
                  <td><?php echo $stockOutHeader["debtor"]; ?></td>
                <?php endif ?>
                <?php if ($transCode === "S1") : ?>
                  <td>Currency:</td>
                  <td><?php echo $stockOutHeader["currency"]; ?></td>
                <?php endif ?>
              </tr>
              <?php if ($transCode === "S1") : ?>
                <tr>
                  <td>Discount:</td>
                  <td><?php echo $stockOutHeader["discount"]; ?>%</td>
                  <td>Net Amount:</td>
                  <td><?php echo $stockOutHeader["net_amount"]; ?></td>
                </tr>
              <?php endif ?>
              <tr>
                <td>Status:</td>
                <td><?php echo $stockOutHeader["status"]; ?></td>
              </tr>
            </table>
            <?php if (count($stockOutModels[$stockOutHeader["stock_out_no"]]) > 0) : ?>
              <?php $showPrice = $transCode === "S1" || $transCode === "S3"; ?>
              <table class="stock-out-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>Brand</th>
                    <th>Model No.</th>
                    <?php if ($showPrice) : ?><th class="number">Price</th><?php endif ?>
                    <th class="number">Qty</th>
                    <?php if ($showPrice) : ?><th class="number">Subtotal</th><?php endif ?>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $subtotalSum = 0;
                    $totalCost = 0;
                    $discount = $stockOutHeader["discount"];
                    $models = $stockOutModels[$stockOutHeader["stock_out_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $price = $model["price"];
                      $costAverage = $model["cost_average"];
                      $qty = $model["qty"];
                      $subtotal = $model["subtotal"];

                      $totalQty += $qty;
                      $subtotalSum += $subtotal;
                      $totalCost += $qty * $costAverage;

                      echo "
                        <tr>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          " . ($showPrice ? "<td class=\"number\">" . rtrim(rtrim($price, "0"), ".") . "</td>" : "") . "
                          <td class=\"number\">" . number_format($qty) . "</td>
                          " . ($showPrice ? "<td class=\"number\">" . number_format($subtotal, 2) . "</td>" : "") . "
                        </tr>
                      ";
                    }
                  ?>
                  <?php if ($discount > 0) : ?>
                    <tr>
                      <td></td>
                      <?php if ($showPrice) : ?><td></td><?php endif ?>
                      <td></td>
                      <th></th>
                      <?php if ($showPrice) : ?>
                        <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                      <?php endif ?>
                    </tr>
                    <tr>
                      <td></td>
                      <?php if ($showPrice) : ?><td></td><?php endif ?>
                      <td></td>
                      <td class="number">Discount <?php echo $discount; ?>%</td>
                      <?php if ($showPrice) : ?>
                        <td class="number"><?php echo number_format($subtotalSum * $discount / 100, 2); ?></td>
                      <?php endif ?>
                    </tr>
                  <?php endif ?>
                  <tr>
                    <th></th>
                    <?php if ($showPrice) : ?><th></th><?php endif ?>
                    <th class="number">Total:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                    <?php if ($showPrice) : ?>
                      <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
                    <?php endif ?>
                  </tr>
                  <?php if ($showPrice) : ?>
                    <tr>
                      <th></th>
                      <?php if ($showPrice) : ?><th></th><?php endif ?>
                      <th class="number">Total Cost:</th>
                      <th></th>
                      <?php if ($showPrice) : ?>
                        <th class="number"><?php echo number_format($totalCost, 2); ?></th>
                      <?php endif ?>
                    </tr>
                  <?php endif ?>
                </tbody>
              </table>
            <?php else : ?>
              <div class="stock-out-models-no-results">No models</div>
            <?php endif ?>
            <table class="stock-out-footer">
              <?php if (assigned($stockOutHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $stockOutHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="stock-out-not-found">Stock out voucher not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
