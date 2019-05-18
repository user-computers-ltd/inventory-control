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
    <style type="text/css" media="print">
      @page { size: landscape }
    </style>
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper landscape">
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
              <select name="filter_debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterDebtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($filterDebtorCodes) {
                    return in_array($i["code"], $filterDebtorCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($incomeHeaders) > 0) : ?>
        <?php foreach ($incomeHeaders as $currency => &$headers) : ?>
          <h4><?php echo $currency; ?></h4>
          <table class="invoice-results sortable">
            <colgroup>
              <col style="width: 70px">
              <col style="width: 50px">
              <col>
              <col style="width: 120px">
              <col style="width: 120px">
              <col style="width: 70px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 70px">
              <col style="width: 80px">
              <col style="width: 70px">
              <col style="width: 80px">
              <col style="width: 100px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Date</th>
                <th>Code</th>
                <th>Client</th>
                <th>DO No. / Stock Out No.</th>
                <th>SO No. / Trans. Code</th>
                <th class="number">Qty</th>
                <th class="number">Actual Cost (Exc. Tax)</th>
                <th class="number">Net Sales Amount (Exc. Tax)</th>
                <th class="number">PM %</th>
                <th class="number">Total Sales Amt (Inc. Tax)</th>
                <th>Inv. Date</th>
                <th class="number">Inv. Amount</th>
                <th>Inv. No.</th>
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
                  $soNos = join("", array_map(function ($s) { return "<div title=\"$s\">$s</div>"; }, explode(",", $incomeHeader["so_no"])));
                  $stockOutId = $incomeHeader["stock_out_id"];
                  $stockOutNo = $incomeHeader["stock_out_no"];
                  $stockInId = $incomeHeader["stock_in_id"];
                  $stockInNo = $incomeHeader["stock_in_no"];
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

                  $voucherColumn = assigned($doId) ? "<td title=\"$doNo\">
                    <a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$doId\">$doNo</a>
                  </td>" : (assigned($stockOutId) ? "<td title=\"$stockOutNo\">
                    <a class=\"link\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$stockOutId\">$stockOutNo</a>
                  </td>" : (assigned($stockInId) ? "<td title=\"$stockInNo\">
                    <a class=\"link\" href=\"" . STOCK_IN_PRINTOUT_URL . "?id[]=$stockInId\">$stockInNo</a>
                  </td>" : ""));

                  $invoiceDateColumn = "";
                  $invoiceAmountColumn = "";
                  $invoiceNoColumn = "";

                  for ($j = 0; $j < $invoiceCount; $j++) {
                    $invoiceAmount = number_format($invoiceAmounts[$j], 2);
                    $invoiceDate = $invoiceDates[$j];
                    $invoiceNo = $invoiceNos[$j];
                    $invoiceId = $invoiceIds[$j];
                    $totalInvAmount += $invoiceAmounts[$j];

                    $invoiceDateColumn = $invoiceDateColumn . "
                      <div title=\"$invoiceDate\">$invoiceDate</div>";
                      $invoiceAmountColumn = $invoiceAmountColumn . "<div class=\"number\" title=\"$invoiceAmount\">$invoiceAmount</div>";
                      $invoiceNoColumn = $invoiceNoColumn . "<div title=\"$invoiceNo\">
                        <a class=\"link\" href=\"" . SALES_INVOICE_PRINTOUT_URL . "?id[]=$invoiceId\">$invoiceNo</a>
                      </div>
                    ";
                  }

                  echo "
                    <tr>
                      <td title=\"$date\">$date</td>
                      <td title=\"$debtorCode\">$debtorCode</td>
                      <td title=\"$debtorName\">$debtorName</td>
                      $voucherColumn
                      <td>$soNos</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$cost\" class=\"number\">" . number_format($cost, 2) . "</td>
                      <td title=\"$net\" class=\"number\">" . number_format($net, 2) . "</td>
                      <td title=\"$profit\" class=\"number\">" . number_format($profit, 2) . "%</td>
                      <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                      <td title=\"$invoiceDate\">$invoiceDateColumn</td>
                      <td title=\"$invoiceAmount\">$invoiceAmountColumn</td>
                      <td title=\"$invoiceNo\">$invoiceNoColumn</td>
                    </tr>
                  ";
                }

                $averagePM /= count($headers);
              ?>
              <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalCost, 2); ?></th>
                <th class="number"><?php echo number_format($totalNet, 2); ?></th>
                <th class="number"><?php echo number_format($averagePM, 2); ?>%</th>
                <th class="number"><?php echo number_format($totalSales, 2); ?></th>
                <th></th>
                <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
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
