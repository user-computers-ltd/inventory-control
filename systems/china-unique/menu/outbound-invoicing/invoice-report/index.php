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
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo OUT_INVOICE_REPORT_TITLE; ?></div>
      <form>
        <table id="invoice-input" class="web-only">
          <tr>
            <th>From:</th>
            <th>To:</th>
          </tr>
          <tr>
            <td><input type="date" name="from" value="<?php echo $from; ?>" /></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" /></td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($incomeHeaders) > 0) : ?>
        <form method="post">
          <table id="invoice-results">
            <colgroup>
              <col style="width: 70px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 60px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 120px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Date</th>
                <th>DO No. / Stock Out No.</th>
                <th>Client</th>
                <th class="number">Qty</th>
                <th class="number">Currency</th>
                <th class="number">Amount</th>
                <th class="number">Inv. Amount</th>
                <th>Invoice No.</th>
                <th class="number">Pending</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;

                for ($i = 0; $i < count($incomeHeaders); $i++) {
                  $incomeHeader = $incomeHeaders[$i];
                  $date = $incomeHeader["date"];
                  $doId = $incomeHeader["do_id"];
                  $doNo = $incomeHeader["do_no"];
                  $stockOutId = $incomeHeader["stock_out_id"];
                  $stockOutNo = $incomeHeader["stock_out_no"];
                  $debtorName = $incomeHeader["debtor_name"];
                  $qty = $incomeHeader["qty"];
                  $currency = $incomeHeader["currency"];
                  $amount = $incomeHeader["amount"];
                  $invoiceAmounts = explode(",", $incomeHeader["invoice_amounts"]);
                  $invoiceNos = explode(",", $incomeHeader["invoice_nos"]);
                  $invoiceIds = explode(",", $incomeHeader["invoice_ids"]);
                  $invoiceCount = count($invoiceAmounts);
                  $pendingAmount = $amount - array_sum($invoiceAmounts);

                  $totalQty += $qty;

                  for ($j = 0; $j < $invoiceCount; $j++) {
                    $invoiceAmount = $invoiceAmounts[$j];
                    $invoiceNo = $invoiceNos[$j];
                    $invoiceId = $invoiceIds[$j];

                    if ($j == 0) {
                      $voucherColumn = assigned($doId) ? "<td title=\"$doNo\" rowspan=\"$invoiceCount\">
                        <a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$doId\">$doNo</a>
                      </td>" : (assigned($stockOutId) ? "<td title=\"$stockOutNo\" rowspan=\"$invoiceCount\">
                        <a class=\"link\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$stockOutId\">$stockOutNo</a>
                      </td>" : "");

                      echo "
                        <tr>
                          <td title=\"$date\" rowspan=\"$invoiceCount\">$date</td>
                          $voucherColumn
                          <td title=\"$debtorName\" rowspan=\"$invoiceCount\">$debtorName</td>
                          <td title=\"$qty\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($qty) . "</td>
                          <td title=\"$currency\" rowspan=\"$invoiceCount\" class=\"number\">$currency</td>
                          <td title=\"$amount\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($amount, 2) . "</td>
                          <td title=\"$invoiceAmount\" class=\"number\">" . number_format($invoiceAmount, 2) . "</td>
                          <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . OUT_INVOICE_PRINTOUT_URL . "?id[]=$invoiceId\">$invoiceNo</a></td>
                          <td title=\"$pendingAmount\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($pendingAmount, 2) . "</td>
                        </tr>
                      ";
                    } else {
                      echo "
                        <tr>
                          <td title=\"$invoiceAmount\" class=\"number\">" . number_format($invoiceAmount, 2) . "</td>
                          <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . OUT_INVOICE_PRINTOUT_URL . "?id[]=$invoiceId\">$invoiceNo</a></td>
                        </tr>
                      ";
                    }
                  }
                }
              ?>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else: ?>
        <div class="invoice-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
