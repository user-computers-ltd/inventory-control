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
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo AR_INVOICE_ISSUED_TITLE; ?></div>
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
        <form id="invoice-form" method="post">
          <button type="submit" name="action" value="print" class="web-only">Print</button>
          <button type="submit" name="action" value="delete" style="display: none;"></button>
          <button type="button" onclick="confirmDelete(event)" class="web-only">Delete</button>
          <button type="submit" name="action" value="cancel" style="display: none;"></button>
          <button type="button" onclick="confirmCancel(event)" class="web-only">Cancel</button>
          <table id="invoice-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col style="width: 30px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 70px">
              <col style="width: 70px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th class="number">#</th>
                <th>Invoice No.</th>
                <th>Client</th>
                <th class="number">Amount</th>
                <th class="number">Outstanding</th>
                <th>Maturity Date</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalAmount = 0;
                $totalOutstanding = 0;

                for ($i = 0; $i < count($invoiceHeaders); $i++) {
                  $invoiceHeader = $invoiceHeaders[$i];
                  $id = $invoiceHeader["id"];
                  $count = $invoiceHeader["count"];
                  $date = $invoiceHeader["date"];
                  $invoiceNo = $invoiceHeader["invoice_no"];
                  $debtorName = $invoiceHeader["debtor_name"];
                  $currencyCode = $invoiceHeader["currency_code"];
                  $amount = $invoiceHeader["amount"];
                  $outstanding = $invoiceHeader["outstanding"];
                  $maturityDate = $invoiceHeader["maturity_date"];

                  $totalAmount += $amount;
                  $totalOutstanding += $outstanding;

                  echo "
                    <tr>
                      <td class=\"web-only\">
                        <input type=\"checkbox\" name=\"invoice_id[]\" data-invoice_no=\"$invoiceNo\" value=\"$id\" />
                      </td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$count\" class=\"number\">$count</td>
                      <td title=\"$invoiceNo\"><a class=\"link\" href=\"" . AR_INVOICE_URL . "?id=$id\">$invoiceNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                      <td title=\"$outstanding\" class=\"number\">" . number_format($outstanding, 2) . "</td>
                      <td title=\"$maturityDate\">$maturityDate</td>
                      <td><a class=\"link\" href=\"" . AR_INVOICE_SETTLEMENT_URL . "?id=$id\">Settlement</a></td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th class="number"></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
                <th class="number"><?php echo number_format($totalOutstanding, 2); ?></th>
                <th></th>
                <th></th>
              </tr>
            </tbody>
          </table>
        </form>
        <script>
          var invoiceFormElement = document.querySelector("#invoice-form");
          var deleteButtonElement = invoiceFormElement.querySelector("button[value=\"delete\"]");
          var cancelButtonElement = invoiceFormElement.querySelector("button[value=\"cancel\"]");

          function confirmDelete(event) {
            var checkedItems = invoiceFormElement.querySelectorAll("input[name=\"invoice_id[]\"]:checked");

            if (checkedItems.length > 0) {
              var listElement = "<ul>";

              for (var i = 0; i < checkedItems.length; i++) {
                listElement += "<li>" + checkedItems[i].dataset["invoice_no"] + "</li>";
              }

              listElement += "</ul>";

              showConfirmDialog("<b>Are you sure you want to delete the following?</b><br/><br/>" + listElement, function () {
                deleteButtonElement.click();
                setLoadingMessage("Deleting...")
                toggleLoadingScreen(true);
              });
            }
          }

          function confirmCancel(event) {
            var checkedItems = invoiceFormElement.querySelectorAll("input[name=\"invoice_id[]\"]:checked");

            if (checkedItems.length > 0) {
              var listElement = "<ul>";

              for (var i = 0; i < checkedItems.length; i++) {
                listElement += "<li>" + checkedItems[i].dataset["invoice_no"] + "</li>";
              }

              listElement += "</ul>";

              showConfirmDialog("<b>Are you sure you want to cancel the following?</b><br/><br/>" + listElement, function () {
                cancelButtonElement.click();
                setLoadingMessage("Cancelling...")
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
