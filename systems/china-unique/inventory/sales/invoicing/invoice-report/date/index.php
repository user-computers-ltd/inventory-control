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
      <div class="headline"><?php echo SALES_INVOICE_REPORT_DATE_TITLE; ?></div>
      <form>
        <table id="invoice-input">
          <tr>
            <th>Period:</th>
            <th>Client:</th>
            <th>Product Types:</th>
          </tr>
          <tr>
            <td>
              <select name="period" class="web-only" onchange="this.form.submit()">
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
            <td>
              <select name="filter_product_type[]" multiple class="web-only">
                <?php
                  foreach ($PRODUCT_TYPES as $type) {
                    $selected = assigned($filterProductTypes) && in_array($type, $filterProductTypes) ? "selected" : "";
                    echo "<option value=\"$type\" $selected>$type</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo assigned($filterProductTypes) ? join(", ", $filterProductTypes) : "ALL"; ?></span>
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
              <col style="width: 60px">
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
                <th>Variance</th>
              </tr>
            </thead>
            <tbody>
              <?php
                function accumulateType(&$total, $M, $S, $O) {
                  $total["M"] += $M;
                  $total["S"] += $S;
                  $total["O"] += $O;
                }

                function sumType($total) {
                  return $total["M"] + $total["S"] + $total["O"];
                }

                function getProfit($net, $cost) {
                  return $net > 0 ? ($net - $cost) / $cost * 100 : 0;
                }

                $totalQty = array("M" => 0, "S" => 0, "O" => 0);
                $totalCost = array("M" => 0, "S" => 0, "O" => 0);
                $totalNet = array("M" => 0, "S" => 0, "O" => 0);
                $totalSales = array("M" => 0, "S" => 0, "O" => 0);
                $totalPending = 0;
                $totalInvAmount = 0;
                $totalVariance = 0;

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
                  $qty = $incomeHeader["qtyM"] + $incomeHeader["qtyS"] + $incomeHeader["qtyO"];
                  $cost = $incomeHeader["costM"] + $incomeHeader["costS"] + $incomeHeader["costO"];
                  $net = $incomeHeader["netM"] + $incomeHeader["netS"] + $incomeHeader["netO"];
                  $amount = $incomeHeader["amountM"] + $incomeHeader["amountS"] + $incomeHeader["amountO"];
                  $profit = getProfit($net, $cost);
                  $invoiceAmounts = explode(",", $incomeHeader["invoice_amounts"]);
                  $invoiceDates = explode(",", $incomeHeader["invoice_dates"]);
                  $invoiceNos = explode(",", $incomeHeader["invoice_nos"]);
                  $invoiceIds = explode(",", $incomeHeader["invoice_ids"]);
                  $invoiceSum = array_sum($invoiceAmounts);
                  $pendingAmount = round($amount - $invoiceSum, 2);
                  $settlement = $incomeHeader["settlement"];

                  if ($settlement === "FULL") {
                    $variance = number_format($pendingAmount, 2);
                    $totalVariance += $variance;
                  } else if ($settlement === "PARTIAL") {
                    $variance = "PI";
                    $totalPending += $pendingAmount;
                  } else {
                    $variance = "";
                    $totalPending += $pendingAmount;
                  }

                  accumulateType($totalQty, $incomeHeader["qtyM"], $incomeHeader["qtyS"], $incomeHeader["qtyO"]);
                  accumulateType($totalCost, $incomeHeader["costM"], $incomeHeader["costS"], $incomeHeader["costO"]);
                  accumulateType($totalNet, $incomeHeader["netM"], $incomeHeader["netS"], $incomeHeader["netO"]);
                  accumulateType($totalSales, $incomeHeader["amountM"], $incomeHeader["amountS"], $incomeHeader["amountO"]);

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

                  for ($j = 0; $j < count($invoiceAmounts); $j++) {
                    $invoiceAmount = number_format($invoiceAmounts[$j], 2);
                    $invoiceDate = $invoiceDates[$j];
                    $invoiceNo = $invoiceNos[$j];
                    $invoiceId = $invoiceIds[$j];

                    $invoiceDateColumn = $invoiceDateColumn . "
                      <div title=\"$invoiceDate\">$invoiceDate</div>";
                      $invoiceAmountColumn = $invoiceAmountColumn . "<div class=\"number\" title=\"$invoiceAmount\">$invoiceAmount</div>";
                      $invoiceNoColumn = $invoiceNoColumn . "<div title=\"$invoiceNo\">
                        <a class=\"link\" href=\"" . SALES_INVOICE_URL . "?id=$invoiceId\">$invoiceNo</a>
                      </div>
                    ";
                  }

                  $totalInvAmount += $invoiceSum;

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
                      <td title=\"$variance\" class=\"number\">$variance</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
            <tbody>
              <tr>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format(sumType($totalQty)); ?></th>
                <th class="number"><?php echo number_format(sumType($totalCost), 2); ?></th>
                <th class="number"><?php echo number_format(sumType($totalNet), 2); ?></th>
                <th class="number"><?php echo number_format(getProfit(sumType($totalNet), sumType($totalCost)), 2); ?>%</th>
                <th class="number"><?php echo number_format(sumType($totalSales), 2); ?></th>
                <th></th>
                <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
                <th></th>
                <th class="number"><?php echo number_format($totalVariance, 2); ?></th>
              </tr>
              <?php if (!assigned($filterProductTypes)) : ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th class="number">M:</th>
                  <th class="number"><?php echo number_format($totalQty["M"]); ?></th>
                  <th class="number"><?php echo number_format($totalCost["M"], 2); ?></th>
                  <th class="number"><?php echo number_format($totalNet["M"], 2); ?></th>
                  <th class="number"><?php echo number_format(getProfit($totalNet["M"], $totalCost["M"]), 2); ?>%</th>
                  <th class="number"><?php echo number_format($totalSales["M"], 2); ?></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th class="number">S:</th>
                  <th class="number"><?php echo number_format($totalQty["S"]); ?></th>
                  <th class="number"><?php echo number_format($totalCost["S"], 2); ?></th>
                  <th class="number"><?php echo number_format($totalNet["S"], 2); ?></th>
                  <th class="number"><?php echo number_format(getProfit($totalNet["S"], $totalCost["S"]), 2); ?>%</th>
                  <th class="number"><?php echo number_format($totalSales["S"], 2); ?></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th class="number">O:</th>
                  <th class="number"><?php echo number_format($totalQty["O"]); ?></th>
                  <th class="number"><?php echo number_format($totalCost["O"], 2); ?></th>
                  <th class="number"><?php echo number_format($totalNet["O"], 2); ?></th>
                  <th class="number"><?php echo number_format(getProfit($totalNet["O"], $totalCost["O"]), 2); ?>%</th>
                  <th class="number"><?php echo number_format($totalSales["O"], 2); ?></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              <?php endif ?>
            </tbody>
          </table>
          <table class="invoice-results-total">
            <tr>
              <th>Total Profit:</th>
              <th class="number"><?php echo number_format(sumType($totalNet) - sumType($totalCost), 2); ?></th>
            </tr>
            <?php if (!assigned($filterProductTypes)) : ?>
              <tr>
                <td>M</td>
                <td class="number"><?php echo number_format($totalNet["M"] - $totalCost["M"], 2); ?></td>
              </tr>
              <tr>
                <td>S</td>
                <td class="number"><?php echo number_format($totalNet["S"] - $totalCost["S"], 2); ?></td>
              </tr>
              <tr>
                <td>O</td>
                <td class="number"><?php echo number_format($totalNet["O"] - $totalCost["O"], 2); ?></td>
              </tr>
            <?php endif ?>
            <tr>
              <th>Pending Amount:</th>
              <th class="number"><?php echo number_format($totalPending, 2); ?></th>
            </tr>
          </table>
        <?php endforeach ?>
      <?php else : ?>
        <div class="invoice-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
