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
      <?php if (count($soHeaders) > 0) : ?>
        <?php foreach($soHeaders as &$soHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo SALES_ORDER_PRINTOUT_TITLE ?></div>
            <table class="so-header">
              <tr>
                <td>Order No.:</td>
                <td><?php echo $soHeader["so_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $soHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>Client:</td>
                <td><?php echo $soHeader["client"]; ?></td>
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
            <?php if (count($soModels[$soHeader["so_no"]]) > 0) : ?>
              <table class="so-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>Brand</th>
                    <th>Model No.</th>
                    <th class="number">Selling Price</th>
                    <th class="number">Qty</th>
                    <th class="number">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $subtotalSum = 0;
                    $discount = $soHeader["discount"];
                    $models = $soModels[$soHeader["so_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $price = $model["price"];
                      $qty = $model["qty"];
                      $subtotal = $model["subtotal"];

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
                </tbody>
              </table>
            <?php else: ?>
              <div class="so-models-no-results">No models</div>
            <?php endif ?>
            <table class="so-footer">
              <?php if (assigned($soHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $soHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
        <div class="web-only">
          <?php echo generateRedirectButton(SALES_ORDER_INTERNAL_PRINTOUT_URL, "Internal printout"); ?>
        </div>
      <?php else: ?>
        <div id="so-not-found">Sales order not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
