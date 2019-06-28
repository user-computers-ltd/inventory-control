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
      <div class="headline"><?php echo AR_CREDIT_NOTE_ISSUED_TITLE; ?></div>
      <form>
        <table id="credit-note-input" class="web-only">
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
      <?php if (count($creditNoteHeaders) > 0) : ?>
        <form id="credit-note-form" method="post">
          <button type="submit" name="action" value="print" class="web-only">Print</button>
          <button type="submit" name="action" value="delete" style="display: none;"></button>
          <button type="button" onclick="confirmDelete(event)" class="web-only">Delete</button>
          <button type="submit" name="action" value="settle" style="display: none;"></button>
          <button type="button" onclick="confirmSettle(event)" class="web-only">Settle</button>
          <table id="credit-note-results">
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
                <th>Credit Note No.</th>
                <th>Client</th>
                <th class="number">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalAmountBase = 0;

                for ($i = 0; $i < count($creditNoteHeaders); $i++) {
                  $creditNoteHeader = $creditNoteHeaders[$i];
                  $id = $creditNoteHeader["id"];
                  $date = $creditNoteHeader["date"];
                  $creditNoteNo = $creditNoteHeader["credit_note_no"];
                  $debtorName = $creditNoteHeader["debtor_name"];
                  $amount = $creditNoteHeader["amount"];

                  $totalAmount += $amount;

                  echo "
                    <tr>
                      <td class=\"web-only\">
                        <input type=\"checkbox\" name=\"credit_note_id[]\" data-credit_note_no=\"$creditNoteNo\" value=\"$id\" />
                      </td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$creditNoteNo\"><a class=\"link\" href=\"" . AR_CREDIT_NOTE_URL . "?id=$id\">$creditNoteNo</a></td>
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
          var creditNoteFormElement = document.querySelector("#credit-note-form");
          var deleteButtonElement = creditNoteFormElement.querySelector("button[value=\"delete\"]");

          function confirmDelete(event) {
            var checkedItems = creditNoteFormElement.querySelectorAll("input[name=\"credit_note_id[]\"]:checked");

            if (checkedItems.length > 0) {
              var listElement = "<ul>";

              for (var i = 0; i < checkedItems.length; i++) {
                listElement += "<li>" + checkedItems[i].dataset["credit_note_no"] + "</li>";
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
        <div class="credit-note-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
