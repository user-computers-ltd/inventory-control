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
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_ORDER_POSTED_TITLE; ?></div>
      <form>
        <table id="so-input" class="web-only">
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
      <?php if (count($soHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="print">Print</button>
          <button type="submit" name="action" value="delete">Delete</button>
          <table id="so-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 60px">
              <col style="width: 60px">
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
                <th class="number">Outstanding Qty</th>
                <th class="number">Discount</th>
                <th class="number">Currency</th>
                <th class="number">Outstanding Amt</th>
                <th class="number"><?php echo $InBaseCurrency; ?></th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalOutstanding = 0;
                $totalAmtBase = 0;

                for ($i = 0; $i < count($soHeaders); $i++) {
                  $soHeader = $soHeaders[$i];
                  $id = $soHeader["id"];
                  $date = $soHeader["date"];
                  $soNo = $soHeader["so_no"];
                  $debtorName = $soHeader["debtor_name"];
                  $qty = $soHeader["qty"];
                  $outstandingQty = $soHeader["outstanding_qty"];
                  $discount = $soHeader["discount"];
                  $currency = $soHeader["currency"];
                  $outstandingAmt = $soHeader["outstanding_amt"];
                  $outstandingAmtBase = $soHeader["outstanding_amt_base"];
                  $ongoingDelivery = $outstandingQty < $qty ? "true" : "false";

                  $totalQty += $qty;
                  $totalOutstanding += $outstandingQty;
                  $totalAmtBase += $outstandingAmtBase;

                  echo "
                    <tr>
                      <td class=\"web-only\">
                        <input type=\"checkbox\" name=\"so_id[]\" value=\"$id\" data-ongoing=\"$ongoingDelivery\" onchange=\"onUpdateSelection()\"/>
                      </td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$id\">$soNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                      <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                      <td title=\"$currency\" class=\"number\">$currency</td>
                      <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                      <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
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
                <th class="number"><?php echo number_format($totalOutstanding); ?></th>
                <th></th>
                <th></th>
                <th></th>
                <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else : ?>
        <div class="so-client-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      var deleteButton = document.querySelector("form button[value=\"delete\"]");

      function onUpdateSelection() {
        var disableDelete = document.querySelectorAll("form input[name=\"so_id[]\"][data-ongoing=\"true\"]:checked").length > 0;

        toggleClass(deleteButton, "hide", disableDelete);
      }
    </script>
  </body>
</html>
