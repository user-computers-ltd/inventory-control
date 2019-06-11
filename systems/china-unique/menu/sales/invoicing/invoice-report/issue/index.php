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
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_ISSUE_INVOICE_REPORT_TITLE; ?></div>
      <?php if (count($incomeHeaders) > 0) : ?>
        <?php foreach ($incomeHeaders as $currency => &$headers) : ?>
          <table class="invoice-results sortable">
            <colgroup>
              <col>
              <col style="width: 70px">
              <col style="width: 120px">
              <col style="width: 70px">
              <col style="width: 70px">
              <col style="width: 70px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Client</th>
                <th>Date</th>
                <th>DO No. / Stock Out No.</th>
                <th class="number">Qty</th>
                <th class="number">Total Sales Amt (Inc. Tax)</th>
                <th class="number">Tax Amt</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalSales = 0;
                $totalTax = 0;

                for ($i = 0; $i < count($headers); $i++) {
                  $incomeHeader = $headers[$i];
                  $date = $incomeHeader["date"];
                  $doId = $incomeHeader["do_id"];
                  $doNo = $incomeHeader["do_no"];
                  $stockOutId = $incomeHeader["stock_out_id"];
                  $stockOutNo = $incomeHeader["stock_out_no"];
                  $stockInId = $incomeHeader["stock_in_id"];
                  $stockInNo = $incomeHeader["stock_in_no"];
                  $debtorCode = $incomeHeader["debtor_code"];
                  $debtorName = $incomeHeader["debtor_name"];
                  $qty = $incomeHeader["qty"];
                  $amount = $incomeHeader["pending"];
                  $tax = $incomeHeader["tax"];
                  $taxAmount = $amount - $amount / $tax;

                  $totalQty += $qty;
                  $totalSales += $amount;
                  $totalTax += $taxAmount;

                  $voucherColumn = assigned($doId) ? "<td title=\"$doNo\">
                    <a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$doId\">$doNo</a>
                  </td>" : (assigned($stockOutId) ? "<td title=\"$stockOutNo\">
                    <a class=\"link\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$stockOutId\">$stockOutNo</a>
                  </td>" : (assigned($stockInId) ? "<td title=\"$stockInNo\">
                    <a class=\"link\" href=\"" . STOCK_IN_PRINTOUT_URL . "?id[]=$stockInId\">$stockInNo</a>
                  </td>" : ""));

                  echo "
                    <tr>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$date\">$date</td>
                      $voucherColumn
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                      <td title=\"$taxAmount\" class=\"number\">" . number_format($taxAmount, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
            <tbody>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalSales, 2); ?></th>
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
