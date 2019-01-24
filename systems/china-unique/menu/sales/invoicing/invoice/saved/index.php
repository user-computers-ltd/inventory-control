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
      <div class="headline"><?php echo SALES_INVOICE_SAVED_TITLE; ?></div>
      <form>
        <table id="invoice-input" class="web-only">
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
      <?php if (count($invoiceHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="settle">Settle</button>
          <button type="submit" name="action" value="print">Print</button>
          <button type="submit" name="action" value="delete">Delete</button>
          <table id="invoice-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col style="width: 30px">
              <col>
              <col>
              <col style="width: 60px">
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th class="number">#</th>
                <th>Invoice No.</th>
                <th>Client</th>
                <th>Currency</th>
                <th class="number">Amount</th>
                <th class="number"><?php echo $InBaseCurrency; ?></th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalAmountBase = 0;

                for ($i = 0; $i < count($invoiceHeaders); $i++) {
                  $invoiceHeader = $invoiceHeaders[$i];
                  $id = $invoiceHeader["id"];
                  $count = $invoiceHeader["count"];
                  $date = $invoiceHeader["date"];
                  $invoiceNo = $invoiceHeader["invoice_no"];
                  $debtorName = $invoiceHeader["debtor_name"];
                  $currencyCode = $invoiceHeader["currency_code"];
                  $amount = $invoiceHeader["amount"];
                  $amountBase = $invoiceHeader["amount_base"];

                  $totalAmountBase += $amountBase;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"invoice_id[]\" value=\"$id\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$count\" class=\"number\">$count</td>
                      <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . SALES_INVOICE_URL . "?id=$id\">$invoiceNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$currencyCode\">$currencyCode</td>
                      <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                      <td title=\"$amountBase\" class=\"number\">" . number_format($amountBase, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th class="number"></th>
                <th></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalAmountBase, 2); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else : ?>
        <div class="invoice-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
