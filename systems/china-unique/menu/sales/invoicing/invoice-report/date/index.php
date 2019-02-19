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
            <td>
              <select name="product_type[]" multiple class="web-only">
                <?php
                  foreach ($PRODUCT_TYPES as $type) {
                    $selected = assigned($productTypes) && in_array($type, $productTypes) ? "selected" : "";
                    echo "<option value=\"$type\" $selected>$type</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo assigned($productTypes) ? join(", ", $productTypes) : "ALL"; ?></span>
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
                $averagePM = array("M" => 0, "S" => 0, "O" => 0);
                $totalSales = array("M" => 0, "S" => 0, "O" => 0);
                $totalPending = 0;
                $totalInvAmount = 0;

                for ($i = 0; $i < count($headers); $i++) {
                  $incomeHeader = $headers[$i];
                  $date = $incomeHeader["date"];
                  $doId = $incomeHeader["do_id"];
                  $doNo = $incomeHeader["do_no"];
                  $stockOutId = $incomeHeader["stock_out_id"];
                  $stockOutNo = $incomeHeader["stock_out_no"];
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
                  $invoiceCount = count($invoiceAmounts);
                  $invoiceSum = array_sum($invoiceAmounts);
                  $pendingAmount = $amount - $invoiceSum;

                  accumulateType($totalQty, $incomeHeader["qtyM"], $incomeHeader["qtyS"], $incomeHeader["qtyO"]);
                  accumulateType($totalCost, $incomeHeader["costM"], $incomeHeader["costS"], $incomeHeader["costO"]);
                  accumulateType($totalNet, $incomeHeader["netM"], $incomeHeader["netS"], $incomeHeader["netO"]);
                  accumulateType($totalSales, $incomeHeader["amountM"], $incomeHeader["amountS"], $incomeHeader["amountO"]);
                  accumulateType(
                    $averagePM,
                    getProfit($incomeHeader["netM"], $incomeHeader["costM"]),
                    getProfit($incomeHeader["netS"], $incomeHeader["costS"]),
                    getProfit($incomeHeader["netO"], $incomeHeader["costO"])
                  );

                  $totalPending += $pendingAmount;
                  $totalInvAmount += $invoiceSum;

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

                $averagePM["M"] /= count($headers);
                $averagePM["S"] /= count($headers);
                $averagePM["O"] /= count($headers);
              ?>
              <tr>
                <th></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format(sumType($totalQty)); ?></th>
                <th class="number"><?php echo number_format(sumType($totalCost), 2); ?></th>
                <th class="number"><?php echo number_format(sumType($totalNet), 2); ?></th>
                <th class="number"><?php echo number_format(getProfit(sumType($totalNet), sumType($totalCost)), 2); ?>%</th>
                <th class="number"><?php echo number_format(sumType($totalSales), 2); ?></th>
                <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
                <th></th>
                <th></th>
              </tr>
              <?php if (!assigned($productTypes)) : ?>
                <tr>
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
                </tr>
                <tr>
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
                </tr>
                <tr>
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
                </tr>
              <?php endif ?>
            </tbody>
          </table>
          <table class="invoice-results-total">
            <tr>
              <th>Total Profit:</th>
              <th class="number"><?php echo number_format(sumType($totalNet) - sumType($totalCost), 2); ?></th>
            </tr>
            <?php if (!assigned($productTypes)) : ?>
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
