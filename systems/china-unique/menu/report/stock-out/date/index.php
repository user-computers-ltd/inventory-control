<?php
  define("SYSTEM_PATH", "../../../../");

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
      <div class="headline"><?php echo REPORT_STOCK_OUT_DATE_TITLE; ?></div>
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
        <table id="invoice-results">
          <colgroup>
            <col style="width: 70px">
            <col style="width: 30px">
            <col>
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 60px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 60px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Date</th>
              <th class="number">#</th>
              <th>DO No. / Stock Out No.</th>
              <th>Client</th>
              <th class="number">Qty</th>
              <th class="number">Currency</th>
              <th class="number">Amount</th>
              <th class="number">Net <?php echo $InBaseCurrency; ?></th>
              <th class="number">Cost <?php echo $InBaseCurrency; ?></th>
              <th class="number">Profit</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQty = 0;
              $totalNet = 0;
              $totalCost = 0;

              for ($i = 0; $i < count($incomeHeaders); $i++) {
                $incomeHeader = $incomeHeaders[$i];
                $date = $incomeHeader["date"];
                $count = $incomeHeader["count"];
                $doId = $incomeHeader["do_id"];
                $doNo = $incomeHeader["do_no"];
                $stockOutId = $incomeHeader["stock_out_id"];
                $stockOutNo = $incomeHeader["stock_out_no"];
                $debtorName = $incomeHeader["debtor_name"];
                $qty = $incomeHeader["qty"];
                $currency = $incomeHeader["currency"];
                $amount = $incomeHeader["amount"];
                $net = $incomeHeader["net"];
                $cost = $incomeHeader["cost"];
                $profit = ($net - $cost) / $cost * 100;

                $totalQty += $qty;
                $totalNet += $net;
                $totalCost += $cost;

                $voucherColumn = assigned($doId) ? "<td title=\"$doNo\">
                  <a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$doId\">$doNo</a>
                </td>" : (assigned($stockOutId) ? "<td title=\"$stockOutNo\">
                  <a class=\"link\" href=\"" . STOCK_OUT_PRINTOUT_URL . "?id[]=$stockOutId\">$stockOutNo</a>
                </td>" : "");

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$count\" class=\"number\">$count</td>
                    $voucherColumn
                    <td title=\"$debtorName\">$debtorName</td>
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    <td title=\"$currency\" class=\"number\">$currency</td>
                    <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                    <td title=\"$net\" class=\"number\">" . number_format($net, 2) . "</td>
                    <td title=\"$cost\" class=\"number\">" . number_format($cost, 2) . "</td>
                    <td title=\"$profit\" class=\"number\">" . number_format($profit, 2) . "%</td>
                  </tr>
                ";
              }
            ?>
            <tr>
              <th></th>
              <th class="number"></th>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th></th>
              <th></th>
              <th class="number"><?php echo number_format($totalNet); ?></th>
              <th class="number"><?php echo number_format($totalCost); ?></th>
              <th></th>
            </tr>
          </tbody>
        </table>
      <?php else: ?>
        <div class="invoice-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
