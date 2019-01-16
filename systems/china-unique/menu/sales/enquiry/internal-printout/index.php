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
      <div class="page">
        <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
        <div class="headline"><?php echo SALES_ENQUIRY_INTERNAL_PRINTOUT_TITLE ?></div>
        <table class="enquiry-header">
          <tr>
            <td>Date:</td>
            <td><?php echo $date; ?></td>
          </tr>
          <tr>
            <td>Client:</td>
            <td><?php echo $client; ?></td>
            <?php if (!$ignorePrice) : ?>
              <td>Currency:</td>
              <td><?php echo $currency; ?></td>
            <?php endif ?>
          </tr>
          <tr>
            <td>Persion In-charge:</td>
            <td><?php echo $inCharge; ?></td>
            <?php if ($discount > 0 && $ignorePrice) : ?>
              <td>Discount:</td>
              <td><?php echo $discount; ?>%</td>
            <?php endif ?>
          </tr>
        </table>
        <?php if (count($items) > 0) : ?>
          <table class="enquiry-models">
            <colgroup>
              <col>
              <col>
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th rowspan="2">Brand</th>
                <th rowspan="2">Model No.</th>
                <th colspan="6" class="quantity">Quantity</th>
              </tr>
              <tr>
                <th class="number">Request</th>
                <th class="number">On Hand</th>
                <th class="number">Reserved</th>
                <th class="number">Available</th>
                <th class="number">On Order</th>
                <th class="number">Allotted</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyOnHand = 0;
                $totalQtyOnReserve = 0;
                $totalQtyAvailable = 0;
                $totalQtyOnOrder = 0;
                $totalQtyAllotted = 0;

                for ($i = 0; $i < count($items); $i++) {
                  $item = $items[$i];
                  $brand = $item["brand"];
                  $modelNo = $item["model_no"];
                  $qty = $item["qty"];
                  $qtyOnHand = $item["qty_on_hand"];
                  $qtyOnReserve = $item["qty_on_reserve"];
                  $qtyAvailable = $item["qty_available"];
                  $qtyOnOrder = $item["qty_on_order"];
                  $qtyAllotted = $item["qty_allotted"];

                  $totalQty += $qty;
                  $totalQtyOnHand += $qtyOnHand;
                  $totalQtyOnReserve += $qtyOnReserve;
                  $totalQtyAvailable += $qtyAvailable;
                  $totalQtyOnOrder += $qtyOnOrder;
                  $totalQtyAllotted += $qtyAllotted;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
                      <td class=\"number\">" . number_format($qtyOnHand) . "</td>
                      <td class=\"number\">" . number_format($qtyOnReserve) . "</td>
                      <td class=\"number\">" . number_format($qtyAvailable) . "</td>
                      <td class=\"number\">" . number_format($qtyOnOrder) . "</td>
                      <td class=\"number\">" . number_format($qtyAllotted) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalQtyOnHand); ?></th>
                <th class="number"><?php echo number_format($totalQtyOnReserve); ?></th>
                <th class="number"><?php echo number_format($totalQtyAvailable); ?></th>
                <th class="number"><?php echo number_format($totalQtyOnOrder); ?></th>
                <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
              </tr>
            </tbody>
          </table>
        <?php else: ?>
          <div class="enquiry-models-no-results">No models</div>
        <?php endif ?>
        <table class="enquiry-footer">
          <?php if (assigned($remarks)) : ?>
            <tr>
              <td>Remarks:</td>
              <td><?php echo $remarks; ?></td>
            </tr>
          <?php endif ?>
        </table>
      </div>
      <div class="web-only printout-button-wrapper">
        <?php echo generateRedirectButton(SALES_ENQUIRY_PRINTOUT_URL, "External printout"); ?>
        <?php echo generateRedirectButton(SALES_ENQUIRY_URL, "Edit"); ?>
      </div>
    </div>
  </body>
</html>
