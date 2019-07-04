<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once SYSTEM_PATH . "includes/php/actions.php";
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
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo STOCK_IN_SAVED_TITLE; ?></div>
      <form>
        <table id="stock-in-input" class="web-only">
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
      <?php if (count($stockInHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="post">Post</button>
          <button type="submit" name="action" value="print">Print</button>
          <button type="submit" name="action" value="delete">Delete</button>
          <table id="stock-in-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col style="width: 30px">
              <col>
              <col style="width: 80px">
              <col>
              <col style="width: 80px">
              <col style="width: 60px">
              <col style="width: 60px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th class="number">#</th>
                <th>Voucher No.</th>
                <th>Code</th>
                <th>Client</th>
                <th class="number">Total Qty</th>
                <th class="number">Discount</th>
                <th class="number">Currency</th>
                <th class="number">Total Amt</th>
                <th class="number"><?php echo $InBaseCurrency; ?></th>
                <th>T.C.</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalAmtBaseSum = 0;

                for ($i = 0; $i < count($stockInHeaders); $i++) {
                  $stockInHeader = $stockInHeaders[$i];
                  $id = $stockInHeader["id"];
                  $count = $stockInHeader["count"];
                  $date = $stockInHeader["date"];
                  $stockInNo = $stockInHeader["stock_in_no"];
                  $creditorCode = $stockInHeader["creditor_code"];
                  $creditorName = $stockInHeader["creditor_name"];
                  $qty = $stockInHeader["qty"];
                  $discount = $stockInHeader["discount"];
                  $currency = $stockInHeader["currency"];
                  $totalAmt = $stockInHeader["total_amt"];
                  $totalAmtBase = $stockInHeader["total_amt_base"];
                  $transCode = $stockInHeader["transaction_code"];
                  $transactionName = $TRANSACTION_CODES[$transCode];

                  $totalQty += $qty;
                  $totalAmtBaseSum += $totalAmtBase;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"stock_in_id[]\" value=\"$id\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$count\" class=\"number\">$count</td>
                      <td title=\"$stockInNo\"><a class=\"link\" href=\"" . STOCK_IN_URL . "?id=$id\">$stockInNo</a></td>
                      " . ($transCode === "R1" || $transCode === "R3" || $transCode === "R7" || $transCode === "R8" ? "<td title=\"$creditorCode\">$creditorCode</td>" : "<td></td>") . "
                      " . ($transCode === "R1" || $transCode === "R3" || $transCode === "R7" || $transCode === "R8" ? "<td title=\"$creditorName\">$creditorName</td>" : "<td></td>") . "
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      " . ($transCode === "R1" ? "<td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>" : "<td></td>") . "
                      " . ($transCode === "R1" ? "<td title=\"$currency\" class=\"number\">$currency</td>" : "<td></td>") . "
                      " . ($transCode === "R1" || $transCode === "R3" ? "<td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>" : "<td></td>") . "
                      " . ($transCode === "R1" || $transCode === "R3" ? "<td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>" : "<td></td>") . "
                      <td title=\"$transCode - $transactionName\">$transCode</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th class="number"></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th></th>
                <th></th>
                <th></th>
                <th class="number"><?php echo number_format($totalAmtBaseSum, 2); ?></th>
                <th></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else : ?>
        <div class="stock-in-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
