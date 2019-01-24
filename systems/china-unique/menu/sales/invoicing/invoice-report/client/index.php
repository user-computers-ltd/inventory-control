<?php
  define("SYSTEM_PATH", "../../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once SYSTEM_PATH . "includes/php/actions.php";
  include "process.php";

  function generateHeaderRows($headers) {
    $total = array(
      "qty" => 0
    );

    for ($i = 0; $i < count($headers); $i++) {
      $header = $headers[$i];
      $date = $header["date"];
      $doId = $header["do_id"];
      $doNo = $header["do_no"];
      $stockOutId = $header["stock_out_id"];
      $stockOutNo = $header["stock_out_no"];
      $debtorName = $header["debtor_name"];
      $qty = $header["qty"];
      $currency = $header["currency"];
      $amount = $header["amount"];
      $invoiceAmounts = explode(",", $header["invoice_amounts"]);
      $invoiceNos = explode(",", $header["invoice_nos"]);
      $invoiceIds = explode(",", $header["invoice_ids"]);
      $invoiceCount = count($invoiceAmounts);
      $pendingAmount = $amount - array_sum($invoiceAmounts);

      $total["qty"] += $qty;

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
              $voucherColumn
              <td title=\"$debtorName\" rowspan=\"$invoiceCount\">$debtorName</td>
              <td title=\"$qty\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($qty) . "</td>
              <td title=\"$currency\" rowspan=\"$invoiceCount\" class=\"number\">$currency</td>
              <td title=\"$amount\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($amount, 2) . "</td>
              <td title=\"$invoiceAmount\" class=\"number\">" . number_format($invoiceAmount, 2) . "</td>
              <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . SALES_INVOICE_PRINTOUT_URL . "?id[]=$invoiceId\">$invoiceNo</a></td>
              <td title=\"$pendingAmount\" rowspan=\"$invoiceCount\" class=\"number\">" . number_format($pendingAmount, 2) . "</td>
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

    return $total;
  }
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
        <table id="invoice-input" class="web-only">
          <tr>
            <th>Period:</th>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="period">
                <?php
                  foreach ($periods as $p) {
                    $filterP = $p["period"];
                    $selected = $period === $filterP ? "selected" : "";
                    echo "<option value=\"$filterP\" $selected>$filterP</option>";
                  }
                ?>
              </select>
            <td>
              <select name="debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($debtorCodes) && in_array($code, $debtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($incomeHeaders) > 0) : ?>
        <?php foreach ($incomeHeaders as $client => &$headers) : ?>
          <div class="invoice-client">
            <h4><?php echo $client; ?></h4>
            <table id="invoice-results">
              <colgroup>
                <col style="width: 70px">
                <col>
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 60px">
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 120px">
                <col style="width: 80px">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Date</th>
                  <th>DO No. / Stock Out No.</th>
                  <th>Client</th>
                  <th class="number">Qty</th>
                  <th class="number">Currency</th>
                  <th class="number">Amount</th>
                  <th class="number">Inv. Amount</th>
                  <th>Invoice No.</th>
                  <th class="number">Pending</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $previousHeaders = $headers["previous"];
                  $currentHeaders = $headers["current"];

                  if (assigned($previousHeaders)) {
                    echo "<tr><td colspan=\"9\" class=\"divider\">Previously pending</td></tr>";
                    $previousTotal = generateHeaderRows($previousHeaders);
                  }

                  if (assigned($currentHeaders)) {
                    echo "<tr><td colspan=\"9\" class=\"divider\">$period</td></tr>";
                    $currentTotal = generateHeaderRows($currentHeaders);
                  }
                ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($previousTotal["qty"] + $currentTotal["qty"]); ?></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="invoice-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
