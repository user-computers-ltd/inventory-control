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
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo PACKING_LIST_PRINTOUT_TITLE . " (" . $plHeader["warehouse"] . ")" ?></div>

      <?php if ($plHeader): ?>
        <table id="pl-header">
          <tr>
            <td>Order No.:</td>
            <td><?php echo $plHeader["pl_no"]; ?></td>
          </tr>
          <tr>
            <td>Client:</td>
            <td><?php echo $plHeader["customer_name"]; ?></td>
          </tr>
          <tr>
            <td>Address:</td>
            <td><?php echo $plHeader["customer_address"]; ?></td>
          </tr>
          <tr>
            <td>Contact:</td>
            <td><?php echo $plHeader["customer_contact"]; ?></td>
          </tr>
          <tr>
            <td>Tel:</td>
            <td><?php echo $plHeader["customer_tel"]; ?></td>
          </tr>
          <tr>
            <td>Date:</td>
            <td><?php echo $plHeader["date"]; ?></td>
          </tr>
        </table>
        <?php if (count($plModels) > 0) : ?>
          <table id="pl-models">
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
                $discount = $plHeader["discount"];

                for ($i = 0; $i < count($plModels); $i++) {
                  $plModel = $plModels[$i];
                  $brand = $plModel["brand"];
                  $modelNo = $plModel["model_no"];
                  $price = $plModel["price"];
                  $qty = $plModel["qty"];
                  $subtotal = $qty * $price;

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
          <div id="pl-models-no-results">No models</div>
        <?php endif ?>
        <?php if (assigned($remarks)) : ?>
          <table id="pl-footer">
            <tr>
              <td>Remarks:</td>
              <td><?php echo $remarks; ?></td>
            </tr>
          </table>
        <?php endif ?>
      <?php else: ?>
        <div id="pl-not-found">Packing list not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
