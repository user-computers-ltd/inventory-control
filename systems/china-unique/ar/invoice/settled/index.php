<?php
  define("SYSTEM_PATH", "../../../");

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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo AR_INVOICE_SETTLED_TITLE; ?></div>
      <form>
        <table id="invoice-input" class="web-only">
          <tr>
            <th>From:</th>
            <th>To:</th>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <input type="date" class="web-only" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" />
              <span class="print-only"><?php echo assigned($from) ? $from : "ANY"; ?></span>
            </td>
            <td>
              <input type="date" class="web-only" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" />
              <span class="print-only"><?php echo assigned($to) ? $to : "ANY"; ?></span>
            </td>
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
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($invoiceHeaders) > 0) : ?>
        <form id="invoice-form" method="post">
          <button type="submit" name="action" value="print" class="web-only">Print</button>
          <button type="submit" name="action" value="reverse" style="display: none;"></button>
          <button type="button" onclick="confirmReverse(event)" class="web-only">Reverse</button>
          <table id="invoice-results" class="sortable">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 80px">
              <col style="width: 30px">
              <col>
              <col style="width: 80px">
              <col>
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
                <th>Code</th>
                <th>Client</th>
                <th class="number">Amount</th>
                <th class="number">Variance</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalAmount = 0;
                $totalVariance = 0;

                for ($i = 0; $i < count($invoiceHeaders); $i++) {
                  $invoiceHeader = $invoiceHeaders[$i];
                  $id = $invoiceHeader["id"];
                  $count = $invoiceHeader["count"];
                  $date = $invoiceHeader["date"];
                  $invoiceNo = $invoiceHeader["invoice_no"];
                  $debtorCode = $invoiceHeader["debtor_code"];
                  $debtorName = $invoiceHeader["debtor_name"];
                  $amount = $invoiceHeader["amount"];
                  $variance = $invoiceHeader["variance"];

                  $totalAmount += $amount;
                  $totalVariance += $variance;

                  echo "
                    <tr>
                      <td class=\"web-only\">
                        <input type=\"checkbox\" name=\"invoice_id[]\" data-invoice_no=\"$invoiceNo\" value=\"$id\" />
                      </td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$count\" class=\"number\">$count</td>
                      <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . AR_INVOICE_URL . "?id=$id\">$invoiceNo</a></td>
                      <td title=\"$debtorCode\">$debtorCode</td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                      <td title=\"$variance\" class=\"number\">" . number_format($variance, 2) . "</td>
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
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
                <th class="number"><?php echo number_format($totalVariance, 2); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
        <script>
          var invoiceFormElement = document.querySelector("#invoice-form");
          var reverseButtonElement = invoiceFormElement.querySelector("button[value=\"reverse\"]");

          function confirmReverse(event) {
            var checkedItems = invoiceFormElement.querySelectorAll("input[name=\"invoice_id[]\"]:checked");

            if (checkedItems.length > 0) {
              var listElement = "<ul>";

              for (var i = 0; i < checkedItems.length; i++) {
                listElement += "<li>" + checkedItems[i].dataset["invoice_no"] + "</li>";
              }

              listElement += "</ul>";

              showConfirmDialog("<b>Are you sure you want to reverse the following?</b><br/><br/>" + listElement, function () {
                reverseButtonElement.click();
                setLoadingMessage("Settling...")
                toggleLoadingScreen(true);
              });
            }
          }
        </script>
      <?php else : ?>
        <div class="invoice-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
