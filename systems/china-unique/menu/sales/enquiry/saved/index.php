<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once "process.php";
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
      <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_ENQUIRY_SAVED_TITLE; ?></div>
      <form>
        <table id="enquiry-input" class="web-only">
          <tr>
            <th>From:</th>
            <th>To:</th>
          </tr>
          <tr>
            <td><input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($enquiryHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="print">Print</button>
          <button type="submit" name="action" value="delete">Delete</button>
          <table id="enquiry-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th>Order No.</th>
                <th>Client</th>
                <th class="number">Total Qty</th>
                <th class="number">Total Qty Allotted</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyAllotted = 0;

                for ($i = 0; $i < count($enquiryHeaders); $i++) {
                  $enquiryHeader = $enquiryHeaders[$i];
                  $id = $enquiryHeader["id"];
                  $date = $enquiryHeader["date"];
                  $enquiryNo = $enquiryHeader["enquiry_no"];
                  $debtorName = $enquiryHeader["debtor_name"];
                  $qty = $enquiryHeader["qty"];
                  $qtyAllotted = $enquiryHeader["qty_allotted"];

                  $totalQty += $qty;
                  $totalQtyAllotted += $qtyAllotted;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"enquiry_id[]\" value=\"$id\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$enquiryNo\"><a class=\"link\" href=\"" . SALES_ENQUIRY_URL . "?id=$id\">$enquiryNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$qtyAllotted\" class=\"number\">" . number_format($qtyAllotted) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else : ?>
        <div class="enquiry-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
