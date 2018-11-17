<?php
  if (!defined("SYSTEM_PATH")) {
    define("SYSTEM_PATH", "../../");
  }

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "invoice_process.php";

  if (!defined("SALES_PATH")) {
    define("SALES_PATH", "");
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="<?php echo SALES_PATH; ?>invoice.css">
  </head>
  <body>
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline">Proforma Invoice</div>
      <?php if ($piHeader): ?>
        <form method="post">
          <table id="inv-header">
            <tr>
              <td>Invoice No.:</td>
              <td><?php echo $piHeader["pi_no"]; ?></td>
              <td>Date:</td>
              <td><?php echo $piHeader["date"]; ?></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td><?php echo $piHeader["debtor_code"] . " - " . $piHeader["debtor_name"]; ?></td>
              <td>Currency:</td>
              <td><?php echo $piHeader["currency_code"] . " @ " . $piHeader["exchange_rate"]; ?></td>
            </tr>
            <tr>
              <td>Discount:</td>
              <td><?php echo $piHeader["discount"]; ?>%</td>
              <td>Tax:</td>
              <td><?php echo $piHeader["tax"]; ?>%</td>
            </tr>
          </table>
          <?php if (count($piModels) > 0) : ?>
            <table id="inv-models">
              <thead>
                <tr></tr>
                <tr>
                  <th>SO No.</th>
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
                  <td></td>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
                </tr>
              </tfoot>
            </table>
            <table id="inv-footer">
              <tr>
                <td>Reference No.:</td>
                <td><input id="ref-no" name="ref_no" placeholder="Reference No." value="<?php echo $piHeader["ref_no"]; ?>" /></td>
              </tr>
              <tr>
                <td>Remarks:</td>
                <td><textarea id="remarks" name="remarks" placeholder="Remarks"><?php echo $piHeader["remarks"]; ?></textarea></td>
              </tr>
            </table>
            <button name="status" type="submit" value="SAVED">Save</button>
            <?php if (assigned($piHeader["status"]) && $piHeader["status"] != "POSTED") : ?>
              <button name="status" type="submit" value="POSTED">Post</button>
              <button name="status" type="submit" value="DELETED">Delete</button>
            <?php endif ?>
            <button type="button" onclick="window.open('invoice_printout.php?pi_no=<?php echo $piHeader["pi_no"]; ?>')">Print</button>
            <button type="button" onclick="window.open('packing_list.php?pi_no=<?php echo $piHeader["pi_no"]; ?>')">Packing List</button>
          <?php else: ?>
            <div id="inv-models-no-results">No models</div>
          <?php endif ?>
        </form>
      <?php else: ?>
        <div id="inv-not-found">Proforma invoice not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
