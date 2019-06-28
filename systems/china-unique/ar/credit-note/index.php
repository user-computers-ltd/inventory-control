<?php
  define("SYSTEM_PATH", "../../");

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
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (assigned($creditNoteNo)) : ?>
        <form id="credit-note-form" method="post">
          <table id="credit-note-header">
            <tr>
              <td>Credit Note No.:</td>
              <td><input type="text" name="credit_note_no" value="<?php echo $creditNoteNo; ?>" required /></td>
            </tr>
            <tr>
              <td>Date:</td>
              <td><input type="date" name="credit_note_date" value="<?php echo $creditNoteDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <select id="debtor-code" name="debtor_code" onchange="onDebtorCodeChange()" required>
                  <?php
                    foreach ($debtors as $code => $debtor) {
                      $label = "$code - " . $debtor["name"];
                      $selected = $debtorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$label</option>";
                    }
                  ?>
                </select>
              </td>
            </tr>
            <tr>
              <td>Invoice No.:</td>
              <td>
                <select id="invoice-no" name="invoice_no" onchange="onInvoiceNoChange()" required></select>
              </td>
            </tr>
            <tr>
              <td>Invoice Amount:</td>
              <td>
                <input id="invoice-amount" type="number" readonly />
              </td>
            </tr>
            <tr>
              <td>Credit Amount:</td>
              <td><input id="credit-note-amount" type="number" name="amount" step="0.01" min="0" ondragover=""value="<?php echo $amount; ?>" required /></td>
            </tr>
            <tr>
              <td>Remarks:</td>
              <td colspan="3"><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status === "DRAFT" || $status === "SAVED") : ?>
            <button name="action" type="submit" value="<?php echo $status === "DRAFT" ? "create" : "update"; ?>">Save</button>
          <?php endif ?>
          <?php if ($status === "SAVED") : ?>
            <button name="action" type="submit" value="delete">Delete</button>
          <?php endif ?>
        </form>
        <script>
          var invoices = <?php echo json_encode($invoices); ?>;

          var debtorCodeElement = document.querySelector("#debtor-code");
          var invoiceNoElement = document.querySelector("#invoice-no");
          var invoiceAmountElement = document.querySelector("#invoice-amount");

          function onDebtorCodeChange() {
            var debtorCode = debtorCodeElement.value;
            var debtorInvoices = invoices[debtorCode] || {};
            var invoiceNos = Object.keys(debtorInvoices);
            var options = "";

            for (var i = 0; i < invoiceNos.length; i++) {
              var invoice = debtorInvoices[invoiceNos[i]];
              var invoiceNo = invoice["invoice_no"];
              options += "<option value=\"" + invoiceNo + "\">" + invoiceNo + "</option>";
            }

            invoiceNoElement.innerHTML = options;
            onInvoiceNoChange();
          }

          function onInvoiceNoChange() {
            var dCode = debtorCodeElement.value;
            var invNo = invoiceNoElement.value;
            var invoiceAmount = invoices[dCode] && invoices[dCode][invNo] && invoices[dCode][invNo]["invoice_amount"] || 0;

            invoiceAmountElement.value = invoiceAmount;
          }

          window.addEventListener("load", function () {
            onDebtorCodeChange();
          });
        </script>
      <?php else : ?>
        <div id="credit-note-entry-not-found">Credit note not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
