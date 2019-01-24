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
            <?php if ($showPrice) : ?>
              <td>Currency:</td>
              <td><?php echo $currency; ?></td>
            <?php endif ?>
          </tr>
          <tr>
            <td>Persion In-charge:</td>
            <td><?php echo $inCharge; ?></td>
            <?php if ($discount > 0 && $showPrice) : ?>
              <td>Discount:</td>
              <td><?php echo $discount; ?>%</td>
            <?php endif ?>
          </tr>
        </table>
        <?php if (count($items) > 0) : ?>
          <table class="enquiry-models">
            <colgroup>
              <col style="width: 80px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th rowspan="2">Brand</th>
                <th rowspan="2">Model No.</th>
                <th colspan="7" class="quantity">Quantity</th>
              </tr>
              <tr>
                <th class="number">Requested</th>
                <th class="number">On Hand</th>
                <th class="number">Reserved</th>
                <th class="number">Available</th>
                <th class="number">Allotted</th>
                <th class="number">Incoming</th>
                <th class="number">Allotment</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyOnHand = 0;
                $totalQtyOnHandReserve = 0;
                $totalQtyAvailable = 0;
                $totalQtyAllotted = 0;

                for ($i = 0; $i < count($items); $i++) {
                  $item = $items[$i];
                  $brand = $item["brand"];
                  $modelNo = $item["model_no"];
                  $qty = $item["qty"];
                  $qtyOnHand = $item["qty_on_hand"];
                  $qtyOnHandReserve = $item["qty_on_hand_reserve"];
                  $qtyAvailable = $item["qty_available"];
                  $qtyIncoming = $item["qty_incoming"];
                  $qtyIncomingReserve = $item["qty_incoming_reserve"];
                  $qtyAllotted = $item["qty_allotted"];

                  $totalQty += $qty;
                  $totalQtyOnHand += $qtyOnHand;
                  $totalQtyOnHandReserve += $qtyOnHandReserve;
                  $totalQtyAvailable += $qtyAvailable;
                  $totalQtyAllotted += $qtyAllotted;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
                      <td class=\"number\">" . number_format($qtyOnHand) . "</td>
                      <td class=\"number\">" . number_format($qtyOnHandReserve) . "</td>
                      <td class=\"number\">" . number_format($qtyAvailable) . "</td>
                      <td class=\"number\">" . number_format($qtyAllotted) . "</td>
                      <td class=\"number\">" . number_format($qtyIncoming) . "</td>
                      <td class=\"number\">" . number_format($qtyIncomingReserve) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalQtyOnHand); ?></th>
                <th class="number"><?php echo number_format($totalQtyOnHandReserve); ?></th>
                <th class="number"><?php echo number_format($totalQtyAvailable); ?></th>
                <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
                <th></th>
                <th></th>
              </tr>
            </tbody>
          </table>
        <?php else : ?>
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
