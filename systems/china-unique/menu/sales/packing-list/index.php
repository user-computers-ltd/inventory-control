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
      <div class="headline"><?php echo PACKING_LIST_DETAIL_TITLE; ?></div>
      <?php if ($plHeader): ?>
        <form method="post">
          <table id="pl-header">
            <tr>
              <td>Packing List No.:</td>
              <td><?php echo $plHeader["pl_no"]; ?></td>
              <td>Date:</td>
              <td><?php echo $plHeader["date"]; ?></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td><?php echo $plHeader["debtor_code"] . " - " . $plHeader["debtor_name"]; ?></td>
              <td>Currency:</td>
              <td><?php echo $plHeader["currency_code"] . " @ " . $plHeader["exchange_rate"]; ?></td>
            </tr>
            <tr>
              <td>Discount:</td>
              <td><?php echo $plHeader["discount"]; ?>%</td>
              <td>Tax:</td>
              <td><?php echo $plHeader["tax"]; ?>%</td>
            </tr>
          </table>
          <?php if (count($plModels) > 0) : ?>
            <table id="pl-models">
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
                  $discount = $plHeader["discount"];

                  for ($i = 0; $i < count($plModels); $i++) {
                    $plModel = $plModels[$i];
                    $soNo = $plModel["so_no"];
                    $brand = $plModel["brand"];
                    $modelNo = $plModel["model_no"];
                    $price = $plModel["price"];
                    $qty = $plModel["qty"];
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
            <table id="pl-footer">
              <tr>
                <td>Reference No.:</td>
                <td><input id="ref-no" name="ref_no" value="<?php echo $plHeader["ref_no"]; ?>" /></td>
              </tr>
              <tr>
                <td>Remarks:</td>
                <td><textarea id="remarks" name="remarks"><?php echo $plHeader["remarks"]; ?></textarea></td>
              </tr>
            </table>
            <button name="status" type="submit" value="SAVED">Save</button>
            <?php if (assigned($plHeader["status"]) && $plHeader["status"] != "POSTED") : ?>
              <button name="status" type="submit" value="POSTED">Post</button>
              <button name="status" type="submit" value="DELETED">Delete</button>
            <?php endif ?>
            <!-- <button type="button" onclick="window.open('invoice_printout.php?pl_no=<?php echo $plHeader["pl_no"]; ?>')">Print</button>
            <button type="button" onclick="window.open('packing_list.php?pl_no=<?php echo $plHeader["pl_no"]; ?>')">Packing List</button> -->
          <?php else: ?>
            <div id="pl-models-no-results">No models</div>
          <?php endif ?>
        </form>
      <?php else: ?>
        <div id="pl-not-found">Proforma invoice not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
