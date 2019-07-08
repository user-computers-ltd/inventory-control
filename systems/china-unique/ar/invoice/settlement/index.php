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
      <div class="headline"><?php echo AR_INVOICE_SETTLEMENT_TITLE; ?></div>
      <?php if (assigned($invoiceNo)) : ?>
        <form id="invoice-form" method="post">
          <table id="invoice-header">
            <tr>
              <td>Invoice No.:</td>
              <td><?php echo $invoiceNo; ?></td>
              <td>Date:</td>
              <td><?php echo $invoiceDate; ?></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td><?php echo $debtorCode . " - " . $debtorName; ?></td>
              <td>Currency:</td>
              <td><?php echo $currencyCode . " @ " . $exchangeRate; ?></td>
            </tr>
            <tr>
              <td>Invoice Amount:</td>
              <td><?php echo number_format($invoiceAmount, 2); ?></td>
            </tr>
          </table>
          <button type="button" onclick="addItem()">Add</button>
          <table id="invoice-vouchers">
            <colgroup>
              <col style="width: 120px">
              <col>
              <col>
              <col>
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr>
                <th></th>
                <th>Voucher No.</th>
                <th class="number">Amount</th>
                <th class="number">Settle Amount</th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th id="total-amount" class="number"></th>
                <th></th>
              </tr>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Invoice Amount:</th>
                <th class="number"><?php echo round($invoiceAmount, 2); ?></th>
                <th></th>
              </tr>
              <?php if (count($ownCreditNoteVouchers) > 0) : ?>
                <tr>
                  <th>Credit Notes:</th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
                <?php foreach ($ownCreditNoteVouchers as &$voucher) : ?>
                  <tr>
                    <td><?php echo $voucher["credit_note_date"]; ?></td>
                    <td><a href="<?php echo AR_CREDIT_NOTE_URL . "?id=" . $voucher["id"]; ?>">
                      <?php echo $voucher["credit_note_no"]; ?>
                    </td></a>
                    <td></td>
                    <td class="number credit-amount"><?php echo round($voucher["amount"], 2); ?></td>
                  </tr>
                <?php endforeach ?>
              <?php endif ?>
              <tr>
                <th></th>
                <th></th>
                <th class="number">Outstanding:</th>
                <th id="outstanding-amount" class="number"></th>
                <th></th>
              </tr>
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <?php if (assigned($remarks)) : ?>
            <table id="invoice-footer">
              <tr>
                <td>Remarks:</td>
                <td><?php echo $remarks; ?></td>
              </tr>
            </table>
          <?php endif ?>
          <button type="submit">Save</button>
        </form>
        <?php
          echo "<datalist id=\"payment-voucher-list\">";

          foreach ($paymentVouchers as $voucher) {
            echo "<option value=\"" . $voucher["payment_no"]
              . "\" data-payment_no=\"" . $voucher["payment_no"]
              . "\" data-amount=\"" . round($voucher["amount"], 2)
              . "\">" . $voucher["payment_no"] . "</option>";
          }

          echo "</datalist>";

          echo "<datalist id=\"credit-note-voucher-list\">";

          foreach ($creditNoteVouchers as $voucher) {
            echo "<option value=\"" . $voucher["credit_note_no"]
              . "\" data-credit_note_no=\"" . $voucher["credit_note_no"]
              . "\" data-amount=\"" . round($voucher["amount"], 2)
              . "\">" . $voucher["credit_note_no"] . "</option>";
          }

          echo "</datalist>";
        ?>
        <script>
          var settlemntVouchers = <?php echo json_encode($settlemntVouchers); ?>;
          var ownCreditNoteVouchers = <?php echo json_encode($ownCreditNoteVouchers); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var creditNoteListElement = document.querySelector("#credit-note-voucher-list");
          var paymentListElement = document.querySelector("#payment-voucher-list");
          var tableBodyElement = document.querySelector("#invoice-vouchers tbody");
          var totalAmountElement = document.querySelector("#total-amount");
          var outstandingAmountElement = document.querySelector("#outstanding-amount");
          var creditAmountElements = document.querySelectorAll(".credit-amount");
          var invoiceAmount = <?php echo $invoiceAmount; ?>;
          var totalCreditAmount = 0;
          for (var i = 0; i < creditAmountElements.length; i++) {
            totalCreditAmount += parseFloat(creditAmountElements[i].innerHTML);
          }

          function getCreditNoteVouchers(creditNoteNo) {
            var matchedElements = creditNoteListElement.querySelectorAll("option[value=\"" + creditNoteNo + "\"]");
            var vouchers = [];

            for (var i = 0; i < matchedElements.length; i++) {
              vouchers.push(matchedElements[i].dataset);
            }

            return vouchers;
          }

          function getPaymentVouchers(paymentNo) {
            var matchedElements = paymentListElement.querySelectorAll("option[value=\"" + paymentNo + "\"]");
            var orders = [];

            for (var i = 0; i < matchedElements.length; i++) {
              orders.push(matchedElements[i].dataset);
            }

            return orders;
          }

          function render() {
            var focusedElement = null;

            tableBodyElement.innerHTML = "";

            var totalQty = 0;
            var totalAmount = 0;

            for (var i = 0; i < settlemntVouchers.length; i++) {
              var settlemntVoucher = settlemntVouchers[i];
              var newRowElement = document.createElement("tr");

              var source = settlemntVoucher["source"];

              var rowInnerHTML =
                  "<td>"
                  + "<div>"
                    + "<input "
                      + "id=\"source_payment_" + i + "\" "
                      + "type=\"radio\" "
                      + "name=\"source_" + i + "\" "
                      + "value=\"true\" "
                      + "onchange=\"onSourceChange('payment', " + i + ")\" "
                      + (source === "payment" ? "checked" : "")
                    + "/><label for=\"source_payment_" + i + "\">Payment No.</label><br/>"
                    + "<input "
                      + "id=\"source_credit_" + i + "\" "
                      + "type=\"radio\" "
                      + "name=\"source_" + i + "\" "
                      + "value=\"false\" "
                      + "onchange=\"onSourceChange('credit', " + i + ")\" "
                      + (source === "credit" ? "checked" : "")
                    + "/><label for=\"source_credit_" + i + "\">Credit Note No.</label><br/>"
                  + "</div>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"payment-no " + (source === "payment" ? "" : "hide") + "\" "
                    + "name=\"payment_no[]\" "
                    + "onchange=\"onPaymentNoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'payment_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + (source === "payment" ? "required" : "")
                  + ">"
                    + "<option value=\"\"></option>";

              if (paymentListElement) {
                for (var j = 0; j < paymentListElement.children.length; j++) {
                  var paymentNo = paymentListElement.children[j].value;
                  var selected = settlemntVoucher["payment_no"] === paymentNo ? " selected" : "";
                  var disabled = settlemntVouchers.filter(function (m, mi) {
                    return mi !== i && m["payment_no"] === paymentNo;
                  }).length > 0 ? " disabled" : "";
                  rowInnerHTML += "<option value=\"" + paymentNo + "\"" + selected + disabled + ">" + paymentNo + "</option>";
                }
              }

              rowInnerHTML += "</select>"
                  + "<select "
                    + "class=\"credit-note-no " + (source === "credit"  ? "" : "hide") + "\" "
                    + "name=\"credit_note_no[]\" "
                    + "onchange=\"onStockOutNoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'credit_note_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + (source === "credit" ? "required" : "")
                  + ">"
                    + "<option value=\"\"></option>";

              if (creditNoteListElement) {
                for (var j = 0; j < creditNoteListElement.children.length; j++) {
                  var creditNoteNo = creditNoteListElement.children[j].value;
                  var selected = settlemntVoucher["credit_note_no"] === creditNoteNo ? " selected" : "";

                  rowInnerHTML += "<option value=\"" + creditNoteNo + "\"" + selected + ">" + creditNoteNo + "</option>";
                }
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<span class=\"number\">" + settlemntVoucher["amount_settlable"] + "</span>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"amount number\" "
                    + "type=\"number\" "
                    + "step=\"0.01\" "
                    + "name=\"amount[]\" "
                    + "value=\"" + settlemntVoucher["amount"].toFixed(2) + "\" "
                    + "onchange=\"onAmountChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'amount[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onAmountKeyDown(event, " + i + ")\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td><div class=\"remove\" onclick=\"removeItem(" + i + ")\">×</div></td>";

              newRowElement.innerHTML = rowInnerHTML;

              totalAmount += parseFloat(settlemntVoucher["amount"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (settlemntVouchers.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\" id=\"invoice-entry-no-model\">No vouchers</td>";
              tableBodyElement.appendChild(rowElement);
            }

            totalAmountElement.innerHTML = totalAmount.toFixed(2);
            outstandingAmountElement.innerHTML = (invoiceAmount - totalAmount + totalCreditAmount).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateSource(index, source = "do") {
            var settlemntVoucher = settlemntVouchers[index];

            settlemntVoucher["source"] = source;
            settlemntVoucher["payment_no"] = source === "payment" ? settlemntVoucher["payment_no"] : "";
            settlemntVoucher["credit_note_no"] = source === "credit" ? settlemntVoucher["credit_note_no"] : "";
            settlemntVoucher["amount"] = 0;
            settlemntVoucher["amount_settlable"] = 0;
          }

          function updateVoucher(index, voucher = {}) {
            var settlemntVoucher = settlemntVouchers[index];

            settlemntVoucher["payment_no"] = voucher["payment_no"] || "";
            settlemntVoucher["credit_note_no"] = voucher["credit_note_no"] || "";
            settlemntVoucher["source"] = voucher["credit_note_no"] ? "credit" : "payment";
            settlemntVoucher["amount_settlable"] = parseFloat(voucher["amount"]) || 0;
            settlemntVoucher["amount"] = parseFloat(voucher["amount"]) || 0;
          }

          function updateAmount(index, amount = 0) {
            var settlemntVoucher = settlemntVouchers[index];
            settlemntVoucher["amount"] = parseFloat(amount);
          }

          function addItem() {
            settlemntVouchers.push({});

            updateVoucher(settlemntVouchers.length - 1);
            render();
          }

          function removeItem(index) {
            settlemntVouchers.splice(index, 1);
            render();
          }

          function onFieldFocused(index, name) {
            focusedRow = index;
            focusedFieldName = name;
          }

          function onFieldBlurred() {
            focusedRow = null;
            focusedFieldName = null;
          }

          function onSourceChange(source, index) {
            updateSource(index, source);
            render();
          }

          function onPaymentNoChange(event, index) {
            var newPaymentNo = event.target.value;
            var matchedVoucher = getPaymentVouchers(newPaymentNo)[0];
            var settlemntVoucher = settlemntVouchers[index];

            if (settlemntVoucher["payment_no"] !== newPaymentNo) {
              var existsAlready = settlemntVouchers.filter(function (m) {
                return newPaymentNo && m["payment_no"] === newPaymentNo;
              }).length > 0;

              if (!existsAlready) {
                updateVoucher(index, matchedVoucher);
              }

              render();
            }

            onFieldBlurred();
          }

          function onStockOutNoChange(event, index) {
            var newStockOutNo = event.target.value;
            var matchedVoucher = getCreditNoteVouchers(newStockOutNo)[0];
            var settlemntVoucher = settlemntVouchers[index];

            if (settlemntVoucher["credit_note_no"] !== newStockOutNo) {
              var existsAlready = settlemntVouchers.filter(function (m) {
                return newStockOutNo && m["credit_note_no"] === newStockOutNo;
              }).length > 0;

              if (!existsAlready) {
                updateVoucher(index, matchedVoucher);
              }

              render();
            }

            onFieldBlurred();
          }

          function onAmountChange(event, index) {
            updateAmount(index, event.target.value);
            render();
          }

          function onAmountKeyDown(event, index) {
            var settlemntVoucher = settlemntVouchers[index];

            if (
              index === settlemntVouchers.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              (settlemntVoucher["payment_no"] || settlemntVoucher["credit_note_no"]) &&
              settlemntVoucher["amount"]
            ) {
              addItem();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#invoice-form").reset();

            for (var i = 0; i < settlemntVouchers.length; i++) {
              var settlemntVoucher = settlemntVouchers[i];
              var paymentNo = settlemntVoucher["payment_no"];
              var creditNoteNo = settlemntVoucher["credit_note_no"];
              var amount = settlemntVoucher["amount"];

              if (paymentNo) {
                updateVoucher(i, getPaymentVouchers(paymentNo)[0]);
              } else if (creditNoteNo) {
                updateVoucher(i, getCreditNoteVouchers(creditNoteNo)[0]);
              }

              updateAmount(i, amount);
            }

            render();
          });
        </script>
      <?php else : ?>
        <div id="invoice-entry-not-found">Invoice not found</div>
      <?php endif ?>
    </div>
  </body>
</html>