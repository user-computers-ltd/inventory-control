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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php if (count($poHeaders) > 0) : ?>
        <?php foreach($poHeaders as &$poHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo PURCHASE_ORDER_INTERNAL_PRINTOUT_TITLE ?></div>
            <table class="po-header">
              <tr>
                <td>Order No.:</td>
                <td><?php echo $poHeader["po_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $poHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>Supplier:</td>
                <td><?php echo $poHeader["supplier"]; ?></td>
                <td>Currency:</td>
                <td><?php echo $poHeader["currency"]; ?></td>
              </tr>
              <tr>
                <td>Discount:</td>
                <td><?php echo $poHeader["discount"]; ?>%</td>
                <td>Status:</td>
                <td><?php echo $poHeader["status"]; ?></td>
              </tr>
            </table>
            <?php if (count($poModels[$poHeader["po_no"]]) > 0) : ?>
              <table class="po-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>Brand</th>
                    <th>Model No.</th>
                    <th class="number">Price</th>
                    <th class="number">Qty</th>
                    <th class="number">Outstanding Qty</th>
                    <th class="number">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $totalQtyOutstanding = 0;
                    $subtotalSum = 0;
                    $discount = $poHeader["discount"];
                    $models = $poModels[$poHeader["po_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $price = $model["price"];
                      $qty = $model["qty"];
                      $qtyOutstanding = $model["qty_outstanding"];

                      $totalQty += $qty;
                      $totalQtyOutstanding += $qtyOutstanding;
                      $subtotalSum += $qty * $price;

                      echo "
                        <tr>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          <td class=\"number\">" . number_format($price, 2) . "</td>
                          <td class=\"number\">" . number_format($qty) . "</td>
                          <td class=\"number\">" . number_format($qtyOutstanding) . "</td>
                          <td class=\"number\">" . number_format($qty * $price, 2) . "</td>
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
                      <th></th>
                      <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                    </tr>
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td class="number">Discount: <?php echo $discount; ?>%</td>
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
                </tbody>
              </table>
            <?php else : ?>
              <div class="po-models-no-results">No models</div>
            <?php endif ?>
            <table class="po-footer">
              <?php if (assigned($poHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $poHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
        <div class="web-only">
          <?php echo generateRedirectButton(PURCHASE_ORDER_PRINTOUT_URL, "External printout"); ?>
        </div>
      <?php else : ?>
        <div id="po-not-found">Purchase order not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
