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
      <div class="headline"><?php echo AR_PAYMENT_SETTLEMENT_TITLE; ?></div>
      <?php if (assigned($paymentNo)) : ?>
        <form id="payment-form" method="post">
          <table id="payment-header">
            <tr>
              <td>Payment No.:</td>
              <td><?php echo $paymentNo; ?></td>
              <td>Date:</td>
              <td><?php echo $paymentDate; ?></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td><?php echo $debtorCode . " - " . $debtorName; ?></td>
              <td>Currency:</td>
              <td><?php echo $currencyCode . " @ " . $exchangeRate; ?></td>
            </tr>
            <tr>
              <td>Payment Amount:</td>
              <td><?php echo number_format($paymentAmount, 2); ?></td>
            </tr>
          </table>
          <button type="button" onclick="addItem()">Add</button>
          <table id="payment-vouchers">
            <colgroup>
              <col>
              <col>
              <col>
              <col>
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr>
                <th>Invoice No.</th>
                <th class="number">Amount</th>
                <th class="number">Settle Amount</th>
                <th>Settle Remarks</th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr>
                <th></th>
                <th class="number">Total:</th>
                <th id="total-amount" class="number"></th>
                <th></th>
                <th></th>
              </tr>
              <tr>
                <th></th>
                <th class="number">Payment Amount:</th>
                <th class="number"><?php echo round($paymentAmount, 2); ?></th>
                <th></th>
                <th></th>
              </tr>
              <tr>
                <th></th>
                <th class="number">Remaining:</th>
                <th id="outstanding-amount" class="number"></th>
                <th></th>
                <th></th>
              </tr>
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <table id="payment-footer">
            <?php if (assigned($remarks)) : ?>
              <tr>
                <td>Remarks:</td>
                <td><?php echo $remarks; ?></td>
              </tr>
            <?php endif ?>
            <tr>
              <td>Settlement Remarks:</td>
              <td><textarea name="settlement_remarks"><?php echo $settlementRemarks; ?></textarea></td>
            </tr>
          </table>
          <button name="action" value="save" type="submit">Save</button>
          <button name="action" value="settle" type="submit">Settle</button>
        </form>
        <?php
          echo "<datalist id=\"invoice-voucher-list\">";

          foreach ($invoiceVouchers as $voucher) {
            echo "<option value=\"" . $voucher["invoice_no"]
              . "\" data-invoice_no=\"" . $voucher["invoice_no"]
              . "\" data-amount=\"" . round($voucher["amount"], 2)
              . "\">" . $voucher["invoice_no"] . " (" . round($voucher["amount"], 2) . ")</option>";
          }

          echo "</datalist>";
        ?>
        <script>
          var settlemntVouchers = <?php echo json_encode($settlemntVouchers); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var invoiceListElement = document.querySelector("#invoice-voucher-list");
          var tableBodyElement = document.querySelector("#payment-vouchers tbody");
          var totalAmountElement = document.querySelector("#total-amount");
          var outstandingAmountElement = document.querySelector("#outstanding-amount");
          var paymentAmount = <?php echo $paymentAmount; ?>;

          function getInvoiceVouchers(inoviceNo) {
            var matchedElements = invoiceListElement.querySelectorAll("option[value=\"" + inoviceNo + "\"]");
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
                  + "<select "
                    + "class=\"payment-no\" "
                    + "name=\"invoice_no[]\" "
                    + "onchange=\"onInvoiceNoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'invoice_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                  + ">"
                    + "<option value=\"\">- Select invoice -</option>";

              if (invoiceListElement) {
                for (var j = 0; j < invoiceListElement.children.length; j++) {
                  var invoiceNo = invoiceListElement.children[j].value;
                  var label = invoiceListElement.children[j].innerHTML;
                  var selected = settlemntVoucher["invoice_no"] === invoiceNo ? " selected" : "";
                  var disabled = settlemntVouchers.filter(function (m, mi) {
                    return mi !== i && m["invoice_no"] === invoiceNo;
                  }).length > 0 ? " disabled" : "";
                  rowInnerHTML += "<option value=\"" + invoiceNo + "\"" + selected + disabled + ">" + label + "</option>";
                }
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<span class=\"number\">" + (settlemntVoucher["invoice_no"] ? settlemntVoucher["amount_settlable"] : "-") + "</span>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"amount number\" "
                    + "type=\"number\" "
                    + "step=\"0.01\" "
                    + "min=\"1\""
                    + "max=\"" + Math.max(0, settlemntVoucher["amount_settlable"]) + "\""
                    + "name=\"amount[]\" "
                    + "value=\"" + settlemntVoucher["amount"].toFixed(2) + "\" "
                    + "onchange=\"onAmountChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'amount[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td>"
                  + "<textarea "
                    + "class=\"settle-remarks\" "
                    + "name=\"settle_remarks[]\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'settle_remarks[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onchange=\"onSettleRemarksChange(event, " + i + ")\" "
                    + "onkeydown=\"onSettleRemarksKeyDown(event, " + i + ")\" "
                  + "/>" + settlemntVoucher["settle_remarks"] + "</textarea>"
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
              rowElement.innerHTML = "<td colspan=\"10\" id=\"payment-entry-no-model\">No vouchers</td>";
              tableBodyElement.appendChild(rowElement);
            }

            totalAmountElement.innerHTML = totalAmount.toFixed(2);
            outstandingAmountElement.innerHTML = (paymentAmount - totalAmount).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateVoucher(index, voucher = {}) {
            var settlemntVoucher = settlemntVouchers[index];
            var settledAmount = settlemntVouchers.filter(function (s, i) { return i !== index; }).reduce(function (a, b) { return a + b["amount"]; }, 0);
            settlemntVoucher["invoice_no"] = voucher["invoice_no"] || "";
            settlemntVoucher["amount"] = Math.min(parseFloat(voucher["amount"]) || 0, paymentAmount - settledAmount);
            settlemntVoucher["amount_settlable"] = parseFloat(voucher["amount"]) || 0;
            settlemntVoucher["settle_remarks"] = voucher["settle_remarks"] || "";
          }

          function updateAmount(index, amount = 0) {
            var settlemntVoucher = settlemntVouchers[index];
            var settledAmount = settlemntVouchers.filter(function (s, i) { return i !== index; }).reduce(function (a, b) { return a + b["amount"]; }, 0);

            settlemntVoucher["amount"] = Math.min(amount, paymentAmount - settledAmount);
          }

          function updateSettleRemarks(index, remarks = "") {
            var settlemntVoucher = settlemntVouchers[index];
            settlemntVoucher["settle_remarks"] = remarks;
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

          function onInvoiceNoChange(event, index) {
            var newInvoiceNo = event.target.value;
            var matchedVoucher = getInvoiceVouchers(newInvoiceNo)[0];
            var settlemntVoucher = settlemntVouchers[index];

            if (settlemntVoucher["invoice_no"] !== newInvoiceNo) {
              var existsAlready = settlemntVouchers.filter(function (m) {
                return newInvoiceNo && m["invoice_no"] === newInvoiceNo;
              }).length > 0;

              if (!existsAlready) {
                updateVoucher(index, matchedVoucher);
              }

              render();
            }
          }

          function onAmountChange(event, index) {
            updateAmount(index, event.target.value);
            render();
          }

          function onSettleRemarksChange(event, index) {
            updateSettleRemarks(index, event.target.value);
            render();
          }

          function onSettleRemarksKeyDown(event, index) {
            var settlemntVoucher = settlemntVouchers[index];
            updateSettleRemarks(index, event.target.value);

            if (
              index === settlemntVouchers.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              settlemntVoucher["invoice_no"] &&
              settlemntVoucher["amount"]
            ) {
              addItem();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#payment-form").reset();

            for (var i = 0; i < settlemntVouchers.length; i++) {
              var settlemntVoucher = settlemntVouchers[i];
              var invoiceNo = settlemntVoucher["invoice_no"];
              var amount = settlemntVoucher["amount"];
              var settleRemarks = settlemntVoucher["settle_remarks"];

              if (invoiceNo) {
                updateVoucher(i, getInvoiceVouchers(invoiceNo)[0]);
              }

              updateAmount(i, amount);
              updateSettleRemarks(i, settleRemarks);
            }

            render();
          });
        </script>
      <?php else : ?>
        <div id="payment-entry-not-found">Payment not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
