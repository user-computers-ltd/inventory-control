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
      <div class="headline"><?php echo AR_PAYMENT_ISSUED_TITLE; ?></div>
      <form>
        <table id="payment-input" class="web-only">
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
      <?php if (count($paymentHeaders) > 0) : ?>
        <form id="payment-form" method="post">
          <button type="submit" name="action" value="print" class="web-only">Print</button>
          <button type="submit" name="action" value="delete" style="display: none;"></button>
          <button type="button" onclick="confirmDelete(event)" class="web-only">Delete</button>
          <button type="submit" name="action" value="settle" style="display: none;"></button>
          <button type="button" onclick="confirmSettle(event)" class="web-only">Settle</button>
          <table id="payment-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col>
              <col>
              <col style="width: 150px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th>Payment No.</th>
                <th>Client</th>
                <th class="number">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalAmountBase = 0;

                for ($i = 0; $i < count($paymentHeaders); $i++) {
                  $paymentHeader = $paymentHeaders[$i];
                  $id = $paymentHeader["id"];
                  $date = $paymentHeader["date"];
                  $paymentNo = $paymentHeader["payment_no"];
                  $debtorName = $paymentHeader["debtor_name"];
                  $amount = $paymentHeader["amount"];

                  $totalAmount += $amount;

                  echo "
                    <tr>
                      <td class=\"web-only\">
                        <input type=\"checkbox\" name=\"payment_id[]\" data-payment_no=\"$paymentNo\" value=\"$id\" />
                      </td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$paymentNo\"><a class=\"link\" href=\"" . AR_PAYMENT_URL . "?id=$id\">$paymentNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$amount\" class=\"number\">" . number_format($amount, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
        <script>
          var paymentFormElement = document.querySelector("#payment-form");
          var deleteButtonElement = paymentFormElement.querySelector("button[value=\"delete\"]");

          function confirmDelete(event) {
            var checkedItems = paymentFormElement.querySelectorAll("input[name=\"payment_id[]\"]:checked");

            if (checkedItems.length > 0) {
              var listElement = "<ul>";

              for (var i = 0; i < checkedItems.length; i++) {
                listElement += "<li>" + checkedItems[i].dataset["payment_no"] + "</li>";
              }

              listElement += "</ul>";

              showConfirmDialog("<b>Are you sure you want to delete the following?</b><br/><br/>" + listElement, function () {
                deleteButtonElement.click();
                setLoadingMessage("Deleting...")
                toggleLoadingScreen(true);
              });
            }
          }
        </script>
      <?php else : ?>
        <div class="payment-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
