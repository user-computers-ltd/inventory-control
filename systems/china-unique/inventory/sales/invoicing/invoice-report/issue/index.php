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
      <div class="headline"><?php echo SALES_ISSUE_INVOICE_REPORT_TITLE; ?></div>
      <?php if (count($incomeHeaders) > 0) : ?>
        <?php foreach ($incomeHeaders as $currency => &$debtorHeaders) : ?>
          <table class="invoice-results sortable">
            <colgroup>
              <col style="width: 60px">
              <col>
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 100px">
              <col style="width: 180px">
              <col style="width: 100px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Code</th>
                <th>Client</th>
                <th class="number">Qty</th>
                <th class="number">Total Qty</th>
                <th class="number">Actual Cost (Exc. Tax)</th>
                <th class="number">Sales Amt (Inc. Tax)</th>
                <th class="number">Total Sales Amt</th>
                <th>Date</th>
                <th>Voucher No.</th>
                <th class="number">Total Tax Amt</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalSales = 0;
                $totalCost = 0;
                $totalTax = 0;

                foreach ($debtorHeaders as $debtorName => &$headers) {
                  $qtyColumn = "";
                  $amountColumn = "";
                  $costColumn = "";
                  $taxAmountColumn = "";
                  $dateColumn = "";
                  $voucherNoColumn = "";
                  $debtorTotalQty = 0;
                  $debtorTotalSales = 0;
                  $debtorTotalTax = 0;

                  for ($i = 0; $i < count($headers); $i++) {
                    $incomeHeader = $headers[$i];
                    $debtorCode = $incomeHeader["debtor_code"];
                    $date = $incomeHeader["date"];
                    $doId = $incomeHeader["do_id"];
                    $doNo = $incomeHeader["do_no"];
                    $stockOutId = $incomeHeader["stock_out_id"];
                    $stockOutNo = $incomeHeader["stock_out_no"];
                    $stockInId = $incomeHeader["stock_in_id"];
                    $stockInNo = $incomeHeader["stock_in_no"];
                    $qty = $incomeHeader["qty"];
                    $cost = $incomeHeader["cost"];
                    $amount = $incomeHeader["pending"];
                    $tax = $incomeHeader["tax"];
                    $taxAmount = $amount - $amount / $tax;

                    $debtorTotalQty += $qty;
                    $debtorTotalSales += $amount;
                    $totalCost += $cost;
                    $debtorTotalTax += $taxAmount;

                    $voucherColumn = assigned($doId) ? "
                      <a class=\"link\" title=\"$doNo\" href=\"" . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$doId\">$doNo</a>"
                    : (assigned($stockOutId) ? "
                      <a class=\"link\" title=\"$stockOutNo\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$stockOutId\">$stockOutNo</a>"
                    : (assigned($stockInId) ? "
                      <a class=\"link\" title=\"$stockInNo\" href=\"" . STOCK_IN_PRINTOUT_URL . "?id[]=$stockInId\">$stockInNo</a>"
                    : ""));

                    $qtyColumn = $qtyColumn . "<div title=\"$qty\">" . number_format($qty) . "</div>";
                    $amountColumn = $amountColumn . "<div title=\"$amount\">" . number_format($amount, 2) . "</div>";
                    $costColumn = $costColumn . "<div title=\"$cost\">" . number_format($cost, 2) . "</div>";
                    $taxAmountColumn = $taxAmountColumn . "<div title=\"$taxAmount\">" . number_format($taxAmount, 2) . "</div>";
                    $dateColumn = $dateColumn . "<div title=\"$date\">$date</div>";
                    $voucherNoColumn = $voucherNoColumn . "<div>" . $voucherColumn . "</div>";
                  }

                  $totalQty += $debtorTotalQty;
                  $totalSales += $debtorTotalSales;
                  $totalTax += $debtorTotalTax;

                  echo "
                    <tr>
                      <td title=\"$debtorCode\">$debtorCode</td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td class=\"number\">$qtyColumn</td>
                      <td class=\"number\">" . number_format($debtorTotalQty) . "</td>
                      <td class=\"number\">$costColumn</td>
                      <td class=\"number\">$amountColumn</td>
                      <td class=\"number\">" . number_format($debtorTotalSales, 2) . "</td>
                      <td>$dateColumn</td>
                      <td>$voucherNoColumn</td>
                      <td class=\"number\">" . number_format($debtorTotalTax, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
            <tbody>
              <tr>
                <th class="number">Total:</th>
                <th></th>
                <th></th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalCost); ?></th>
                <th></th>
                <th class="number"><?php echo number_format($totalSales, 2); ?></th>
                <th></th>
                <th></th>
                <th class="number"><?php echo number_format($totalTax, 2); ?></th>
              </tr>
            </tbody>
          </table>
        <?php endforeach ?>
      <?php else : ?>
        <div class="invoice-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
