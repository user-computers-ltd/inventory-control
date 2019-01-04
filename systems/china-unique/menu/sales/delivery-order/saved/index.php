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
      <div class="headline"><?php echo SALES_DELIVERY_ORDER_SAVED_TITLE; ?></div>
      <form>
        <table id="do-input" class="web-only">
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
      <?php if (count($doHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="post">Post</button>
          <button type="submit" name="action" value="print">Print</button>
          <button type="submit" name="action" value="delete">Delete</button>
          <table id="do-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th>Order No.</th>
                <th>Client</th>
                <th class="number">Total Qty</th>
                <th class="number">Discount</th>
                <th class="number">Currency</th>
                <th class="number">Total Amt</th>
                <th class="number"><?php echo $InBaseCurrency; ?></th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalAmtBaseSum = 0;

                for ($i = 0; $i < count($doHeaders); $i++) {
                  $doHeader = $doHeaders[$i];
                  $doId = $doHeader["do_id"];
                  $date = $doHeader["date"];
                  $debtor = $doHeader["debtor"];
                  $doNo = $doHeader["do_no"];
                  $qty = $doHeader["qty"];
                  $discount = $doHeader["discount"];
                  $currency = $doHeader["currency"];
                  $totalAmt = $doHeader["total_amt"];
                  $totalAmtBase = $doHeader["total_amt_base"];

                  $totalQty += $qty;
                  $totalAmtBaseSum += $totalAmtBase;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"do_id[]\" value=\"$doId\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$doNo\"><a class=\"link\" href=\"" . SALES_DELIVERY_ORDER_URL . "?id=$doId\">$doNo</a></td>
                      <td title=\"$debtor\">$debtor</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                      <td title=\"$currency\" class=\"number\">$currency</td>
                      <td title=\"$totalAmt\" class=\"number\">" . number_format($totalAmt, 2) . "</td>
                      <td title=\"$totalAmtBase\" class=\"number\">" . number_format($totalAmtBase, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
            <tfoot>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th></th>
                <th></th>
                <th></th>
                <th class="number"><?php echo number_format($totalAmtBaseSum, 2); ?></th>
              </tr>
            </tfoot>
          </table>
        </form>
      <?php else: ?>
        <div class="do-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
  <
</html>
