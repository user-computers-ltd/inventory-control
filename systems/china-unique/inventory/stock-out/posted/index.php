<?php
  define("SYSTEM_PATH", "../../../");

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
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo STOCK_OUT_POSTED_TITLE; ?></div>
      <form>
        <table id="stock-out-input" class="web-only">
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
      <?php if (count($stockOutHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="print">Print</button>
          <table id="stock-out-results">
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

                for ($i = 0; $i < count($stockOutHeaders); $i++) {
                  $stockOutHeader = $stockOutHeaders[$i];
                  $id = $stockOutHeader["id"];
                  $count = $stockOutHeader["count"];
                  $date = $stockOutHeader["date"];
                  $stockOutNo = $stockOutHeader["stock_out_no"];
                  $debtorCode = $stockOutHeader["debtor_code"];
                  $debtorName = $stockOutHeader["debtor_name"];
                  $qty = $stockOutHeader["qty"];
                  $discount = $stockOutHeader["discount"];
                  $currency = $stockOutHeader["currency"];
                  $totalAmt = $stockOutHeader["total_amt"];
                  $totalAmtBase = $stockOutHeader["total_amt_base"];
                  $transCode = $stockOutHeader["transaction_code"];
                  $transactionName = $TRANSACTION_CODES[$transCode];

                  $totalQty += $qty;
                  $totalAmtBaseSum += $totalAmtBase;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"stock_out_id[]\" value=\"$id\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$count\" class=\"number\">$count</td>
                      <td title=\"$stockOutNo\"><a class=\"link\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$id\">$stockOutNo</a></td>
                      " . ($transCode === "S1" || $transCode === "S3" || $transCode === "S7" || $transCode === "S8" ? "<td title=\"$debtorCode\">$debtorCode</td>" : "<td></td>") . "
                      " . ($transCode === "S1" || $transCode === "S3" || $transCode === "S7" || $transCode === "S8" ? "<td title=\"$debtorName\">$debtorName</td>" : "<td></td>") . "
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      " . ($transCode === "S1" ? "<td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>" : "<td></td>") . "
                      " . ($transCode === "S1" ? "<td title=\"$currency\" class=\"number\">$currency</td>" : "<td></td>") . "
                      " . ($transCode === "S1" || $transCode === "S3" ? "<td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>" : "<td></td>") . "
                      " . ($transCode === "S1" || $transCode === "S3" ? "<td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>" : "<td></td>") . "
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
        <div class="stock-out-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
