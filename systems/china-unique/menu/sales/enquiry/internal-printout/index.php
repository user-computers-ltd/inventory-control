<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";

  $date = new DateTime();
  $date = $date->format("d-m-Y H:i:s");
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
      <?php if (count($enquiryHeaders) > 0) : ?>
        <?php foreach($enquiryHeaders as &$enquiryHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo SALES_ENQUIRY_INTERNAL_PRINTOUT_TITLE ?></div>
            <table class="enquiry-header">
              <tr>
                <td>Enquiry No.:</td>
                <td><?php echo $enquiryHeader["enquiry_no"]; ?></td>
                <td>Enquiry Date:</td>
                <td><?php echo $enquiryHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>Client:</td>
                <td><?php echo $enquiryHeader["client"]; ?></td>
              </tr>
              <tr>
                <td>Persion In-charge:</td>
                <td><?php echo $enquiryHeader["in_charge"]; ?></td>
              </tr>
            </table>
            <div class="generation-date">Generation Date: <?php echo $date; ?></div>
            <?php if (count($enquiryModels[$enquiryHeader["enquiry_no"]]) > 0) : ?>
              <table class="enquiry-models">
                <colgroup>
                  <col style="width: 30px">
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
                    <th rowspan="2">#</th>
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
                    $items = $enquiryModels[$enquiryHeader["enquiry_no"]];

                    for ($i = 0; $i < count($items); $i++) {
                      $index = $i + 1;
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
                          <td>$index</td>
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
              <?php if (assigned($enquiryHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $enquiryHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
          <div class="web-only printout-button-wrapper">
            <?php echo generateRedirectButton(SALES_ENQUIRY_PRINTOUT_URL, "External printout"); ?>
            <?php if (isset($enquiryHeader["id"])) : ?>
              <form action="<?php echo SALES_ENQUIRY_URL; ?>">
                <input type="hidden" name="id" value="<?php echo $enquiryHeader["id"]; ?>" />
                <button type="submit">Edit</button>
              </form>
            <?php else : ?>
              <?php echo generateRedirectButton(SALES_ENQUIRY_URL, "Edit"); ?>
            <?php endif ?>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="enquiry-not-found">Sales enquiry not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
