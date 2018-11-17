<?php
  if (!defined("SYSTEM_PATH")) {
    define("SYSTEM_PATH", "../../");
  }

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "packing_list_process.php";

  if (!defined("SALES_PATH")) {
    define("SALES_PATH", "");
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="<?php echo SALES_PATH; ?>packing_list.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline">Packing List (<?php echo $piHeader["warehouse_name"]; ?>)</div>

      <?php if ($piHeader): ?>
        <table id="pl-header">
          <tr>
            <td>To:</td>
            <td><?php echo $piHeader["debtor_name"]; ?></td>
          </tr>
          <tr>
            <td>Address:</td>
            <td><?php echo $piHeader["debtor_address"]; ?></td>
          </tr>
          <tr>
            <td>Receiver:</td>
            <td><?php echo $piHeader["debtor_contact"]; ?></td>
          </tr>
          <tr>
            <td>Tel:</td>
            <td><?php echo $piHeader["debtor_tel"]; ?></td>
          </tr>
          <tr>
            <td>Date:</td>
            <td><?php echo $piHeader["date"]; ?></td>
          </tr>
        </table>
        <?php if (count($piModels) > 0) : ?>
          <table id="pl-models">
            <thead>
              <tr></tr>
              <tr>
                <th>Order No.</th>
                <th>Brand</th>
                <th>Model No.</th>
                <th class="number">Qty</th>
                <th class="number">Price (<?php echo $piHeader["currency_code"]; ?>)</th>
                <th class="number">Subtotal (<?php echo $piHeader["currency_code"]; ?>)</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $subtotalSum = 0;
                $discount = $piHeader["discount"];

                for ($i = 0; $i < count($piModels); $i++) {
                  $piModel = $piModels[$i];
                  $soNo = $piModel["so_no"];
                  $brand = $piModel["brand"];
                  $modelNo = $piModel["model_no"];
                  $price = $piModel["price"];
                  $qty = $piModel["qty"];
                  $subtotal = $qty * $price;

                  $totalQty += $qty;
                  $subtotalSum += $subtotal;

                  echo "
                    <tr>
                      <td>$soNo</td>
                      <td>$brand</td>
                      <td>$modelNo</td>
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
          <div id="pl-models-no-results">No models</div>
        <?php endif ?>
      <?php else: ?>
        <div id="pl-not-found">Packing list not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
