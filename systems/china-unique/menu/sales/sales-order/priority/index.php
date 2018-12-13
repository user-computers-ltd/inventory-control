<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
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
      <div class="headline"><?php echo SALES_ORDER_PRIORITY_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Customer:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($soHeaders) > 0): ?>
        <form method="post">
          <button type="submit">Save</button>
          <table class="so-results">
            <colgroup>
              <col style="width: 80px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 60px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 40px">
              <col>
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Order No.</th>
                <th class="number">Total Qty</th>
                <th class="number">Outstanding Qty</th>
                <th class="number">Currency</th>
                <th class="number">Outstanding Amt</th>
                <th class="number"><?php echo $InBaseCurrency; ?></th>
                <th class="number">Discount</th>
                <th class="number">Priority</th>
              </tr>
            </thead>
            <tbody>
            <?php
              $totalQty = 0;
              $totalOutstanding = 0;
              $totalAmtBase = 0;

              for ($i = 0; $i < count($soHeaders); $i++) {
                $soHeader = $soHeaders[$i];
                $date = $soHeader["date"];
                $debtorCode = $soHeader["debtor_code"];
                $debtorName = $soHeader["debtor_name"];
                $soId = $soHeader["so_id"];
                $soNo = $soHeader["so_no"];
                $qty = $soHeader["qty"];
                $outstandingQty = $soHeader["qty_outstanding"];
                $discount = $soHeader["discount"];
                $priority = $soHeader["priority"];
                $currency = $soHeader["currency"];
                $outstandingAmt = $soHeader["amt_outstanding"];
                $outstandingAmtBase = $soHeader["amt_outstanding_base"];

                $totalQty += $qty;
                $totalOutstanding += $outstandingQty;
                $totalAmtBase += $outstandingAmtBase;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$debtorCode\">$debtorName</td>
                    <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id=$soId\">$soNo</a></td>
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                    <td title=\"$currency\" class=\"number\">$currency</td>
                    <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                    <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                    <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                    <td>
                      <input name=\"so_id[]\" type=\"hidden\" value=\"$soId\" />
                      <input class=\"number\" name=\"priority[]\" type=\"number\" min=\"0\" max=\"100\" value=\"$priority\" required />
                    </td>
                  </tr>
                ";
              }
            ?>
            </tbody>
            <tfoot>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalOutstanding); ?></th>
                <th></th>
                <th></th>
                <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </form>
      <?php else: ?>
        <div class="so-customer-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
