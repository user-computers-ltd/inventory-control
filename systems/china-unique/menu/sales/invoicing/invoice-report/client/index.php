<?php
  define("SYSTEM_PATH", "../../../../../");

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
      <div class="headline"><?php echo SALES_INVOICE_REPORT_CUSTOMER_TITLE; ?></div>
      <form>
        <table id="invoice-input">
          <tr>
            <th>Period:</th>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="period" class="web-only">
                <?php
                  foreach ($periods as $p) {
                    $selected = $period === $p ? "selected" : "";
                    echo "<option value=\"$p\" $selected>$p</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo $period; ?></span>
            <td>
              <select name="debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($debtorCodes) && in_array($code, $debtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo assigned($debtorCodes) ? join(", ", $debtorCodes) : "ALL"; ?></span>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($incomeHeaders) > 0) : ?>
        <?php foreach ($incomeHeaders as $currency => &$headers) : ?>
          <h4><?php echo $currency; ?></h4>
          <table class="invoice-results">
            <colgroup>
              <col style="width: 70px">
              <col>
              <col style="width: 70px">
              <col style="width: 70px">
              <col style="width: 70px">
              <col style="width: 70px">
              <col style="width: 70px">
              <col style="width: 70px">
              <col style="width: 70px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Date</th>
                <th>Code</th>
                <th>Client</th>
                <th>DO No. / Stock Out No.</th>
                <th class="number">Qty</th>
                <th class="number">Actual Cost (Exc. Tax)</th>
                <th class="number">Net Sales Amount (Exc. Tax)</th>
                <th class="number">PM %</th>
                <th class="number">Total Sales Amt (Inc. Tax)</th>
                <th class="number">Inv. Amount</th>
                <th>Invoice No.</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalCost = 0;
                $totalNet = 0;
                $averagePM = 0;
                $totalSales = 0;
                $totalInvAmount = 0;
                $previousPending = 0;
                $currentPending = 0;
                $previousIssued = 0;
                $currentIssued = 0;

                for ($i = 0; $i < count($headers); $i++) {
                  $incomeHeader = $headers[$i];
                  $date = $incomeHeader["date"];
                  $doId = $incomeHeader["do_id"];
                  $doNo = $incomeHeader["do_no"];
                  $stockOutId = $incomeHeader["stock_out_id"];
                  $stockOutNo = $incomeHeader["stock_out_no"];
                  $debtorCode = $incomeHeader["debtor_code"];
                  $debtorName = $incomeHeader["debtor_name"];
                  $qty = $incomeHeader["qty"];
                  $cost = $incomeHeader["cost"];
                  $net = $incomeHeader["net"];
                  $profit = ($net - $cost) / $cost * 100;
                  $amount = $incomeHeader["pending"];
                  $invoiceAmounts = explode(",", $incomeHeader["invoice_amounts"]);
                  $invoiceDates = explode(",", $incomeHeader["invoice_dates"]);
                  $invoiceNos = explode(",", $incomeHeader["invoice_nos"]);
                  $invoiceIds = explode(",", $incomeHeader["invoice_ids"]);
                  $invoiceCount = count($invoiceAmounts);
                  $invoiceSum = array_sum($invoiceAmounts);
                  $pendingAmount = $amount - $invoiceSum;

                  $totalQty += $qty;
                  $totalCost += $cost;
                  $totalNet += $net;
                  $averagePM += $profit;
                  $totalSales += $amount;
                  $previousPending += $period != $incomeHeader["period"] ? $pendingAmount : 0;
                  $currentPending += $period == $incomeHeader["period"] ? $pendingAmount : 0;
                  $previousIssued += $period != $incomeHeader["period"] ? $invoiceSum : 0;
                  $currentIssued += $period == $incomeHeader["period"] ? $invoiceSum : 0;

                  for ($j = 0; $j < $invoiceCount; $j++) {
                    $invoiceAmount = $invoiceAmounts[$j];
                    $invoiceNo = $invoiceNos[$j];
                    $invoiceId = $invoiceIds[$j];
                    $totalInvAmount += $invoiceAmount;

                    if ($j == 0) {
                      $voucherColumn = assigned($doId) ? "<td title=\"$doNo\" rowspan=\"$invoiceCount\">
                        <a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$doId\">$doNo</a>
                      </td>" : (assigned($stockOutId) ? "<td title=\"$stockOutNo\" rowspan=\"$invoiceCount\">
                        <a class=\"link\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$stockOutId\">$stockOutNo</a>
                      </td>" : "");

                      echo "
                        <tr>
                          <td title=\"$date\" rowspan=\"$invoiceCount\">$date</td>
                          <td title=\"$debtorCode\" rowspan=\"$invoiceCount\">$debtorCode</td>
                          <td title=\"$debtorName\" rowspan=\"$invoiceCount\">$debtorName</td>
                          $voucherColumn
                          <td title=\"$qty\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($qty) . "</td>
                          <td title=\"$cost\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($cost, 2) . "</td>
                          <td title=\"$net\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($net, 2) . "</td>
                          <td title=\"$profit\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($profit, 2) . "%</td>
                          <td title=\"$amount\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($amount, 2) . "</td>
                          <td title=\"$invoiceAmount\" class=\"number\">" . number_format($invoiceAmount, 2) . "</td>
                          <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . SALES_INVOICE_PRINTOUT_URL . "?id[]=$invoiceId\">$invoiceNo</a></td>
                        </tr>
                      ";
                    } else {
                      echo "
                        <tr>
                          <td title=\"$invoiceAmount\" class=\"number\">" . number_format($invoiceAmount, 2) . "</td>
                          <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . SALES_INVOICE_PRINTOUT_URL . "?id[]=$invoiceId\">$invoiceNo</a></td>
                        </tr>
                      ";
                    }
                  }
                }

                $averagePM /= count($headers);
              ?>
              <tr>
                <th></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalCost, 2); ?></th>
                <th class="number"><?php echo number_format($totalNet, 2); ?></th>
                <th class="number"><?php echo number_format($averagePM, 2); ?>%</th>
                <th class="number"><?php echo number_format($totalSales, 2); ?></th>
                <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
                <th class="number"></th>
                <th></th>
              </tr>
            </tbody>
          </table>
          <table class="invoice-results-total">
            <?php if (assigned($previousPeriod)) : ?>
              <tr>
                <th colspan="2">Previous Stock Out </th>
              </tr>
              <tr>
                <td>Invoice Issued:</td>
                <td class="number"><?php echo number_format($previousIssued, 2); ?></td>
              </tr>
              <tr>
                <td>Invoice Pending:</td>
                <td class="number"><?php echo number_format($previousPending, 2); ?></td>
              </tr>
            <?php endif ?>
            <tr>
              <th colspan="2">Current Stock Out (<?php echo $period; ?>)</th>
            </tr>
            <tr>
              <td>Invoice Issued:</td>
              <td class="number"><?php echo number_format($currentIssued, 2); ?></td>
            </tr>
            <tr>
              <td>Invoice Pending:</td>
              <td class="number"><?php echo number_format($currentPending, 2); ?></td>
            </tr>
            <tr>
              <th>Total Invoice Issued:</th>
              <th class="number"><?php echo number_format($previousIssued + $currentIssued, 2); ?></th>
            </tr>
          </table>
        <?php endforeach ?>
      <?php else : ?>
        <div class="invoice-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
