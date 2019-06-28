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
      <?php if (assigned($invoiceNo)) : ?>
        <?php
          $isNotSalesInvoice = count(array_filter($invoiceVouchers, function ($i) {
            return $i["do_no"] !== "" || $i["stock_out_no"] !== "" || $i["stock_in_no"] !== "";
          })) === 0;
        ?>
        <form id="invoice-form" method="post">
          <table id="invoice-header">
            <tr>
              <td>Invoice No.:</td>
              <td><input type="text" name="invoice_no" value="<?php echo $invoiceNo; ?>" required /></td>
              <td>Date:</td>
              <td><input id="invoice-date" type="date" name="invoice_date" value="<?php echo $invoiceDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <select id="debtor-code" name="debtor_code" onchange="onDebtorCodeChange()" required <?php echo $isNotSalesInvoice ? "" : "readonly"; ?>>
                  <?php
                    foreach ($debtors as $code => $debtor) {
                      $label = "$code - " . $debtor["name"];
                      $selected = $debtorCode == $code ? "selected" : "";

                      if ($isNotSalesInvoice || $debtorCode == $code) {
                        echo "<option value=\"$code\" $selected>$label</option>";
                      }
                    }
                  ?>
                </select>
              </td>
              <td>Currency:</td>
              <td>
                <select id="currency-code" name="currency_code" onchange="onCurrencyCodeChange()" required <?php echo $isNotSalesInvoice ? "" : "readonly"; ?>>
                  <?php
                    foreach ($currencies as $code => $rate) {
                      $selected = $currencyCode == $code ? "selected" : "";

                      if ($isNotSalesInvoice || $currencyCode == $code) {
                        echo "<option value=\"$code\" $selected>$code</option>";
                      }
                    }
                  ?>
                </select>
                <input
                  id="exchange-rate"
                  name="exchange_rate"
                  type="number"
                  step="0.00000001"
                  min="0.00000001"
                  value="<?php echo $exchangeRate; ?>"
                  required
                  <?php echo $currencyCode === COMPANY_CURRENCY  || !$isNotSalesInvoice? "readonly" : ""; ?>
                />
              </td>
            </tr>
            <tr>
              <td>Maturity Date:</td>
              <td><input id="maturity-date" type="date" name="maturity_date" value="<?php echo $maturityDate; ?>" required /></td>
            </tr>
          </table>
          <button type="button" onclick="addItem()">Add</button>
          <table id="invoice-vouchers">
            <colgroup>
              <col>
              <col style="width: 130px">
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr>
                <th>Description</th>
                <th class="number">Amount</th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr>
                <th class="number">Total:</th>
                <th id="total-amount" class="number"></th>
                <th></th>
              <tr>
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <table id="invoice-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status === "DRAFT" || $status === "SAVED") : ?>
            <button name="action" type="submit" value="<?php echo $status === "DRAFT" ? "create" : "update"; ?>">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo AR_INVOICE_PRINTOUT_URL; ?>">Print</button>
          <?php if ($status === "SAVED") : ?>
            <button name="action" type="submit" value="delete">Delete</button>
            <button name="action" type="submit" value="cancel">Cancel</button>
          <?php endif ?>
        </form>
        <script>
          var invoiceVouchers = <?php echo json_encode($invoiceVouchers); ?>;
          var debtors = <?php echo json_encode($debtors); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var brands = <?php echo json_encode($brands); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var invoiceDateElement = document.querySelector("#invoice-date");
          var debtorCodeElement = document.querySelector("#debtor-code");
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");
          var maturityDateElement = document.querySelector("#maturity-date");
          var tableBodyElement = document.querySelector("#invoice-vouchers tbody");
          var totalAmountElement = document.querySelector("#total-amount");
          var modelListElement = document.querySelector("#model-list");

          function render() {
            var focusedElement = null;

            tableBodyElement.innerHTML = "";

            var totalQty = 0;
            var totalAmount = 0;

            for (var i = 0; i < invoiceVouchers.length; i++) {
              var debtorCode = debtorCodeElement.value;
              var currencyCode = currencyCodeElement.value;

              var invoiceVoucher = invoiceVouchers[i];
              var newRowElement = document.createElement("tr");
              var editable = !invoiceVoucher["stock_in_no"] && !invoiceVoucher["stock_out_no"] && !invoiceVoucher["do_no"];
              var description = invoiceVoucher["stock_in_no"]
              ? invoiceVoucher["stock_in_no"]
              : invoiceVoucher["stock_out_no"]
              ? invoiceVoucher["stock_out_no"]
              : invoiceVoucher["do_no"]
              ? invoiceVoucher["do_no"]
              : "";
              description += description && invoiceVoucher["settle_remarks"] && (" - " + invoiceVoucher["settle_remarks"]);

              var rowInnerHTML =
                "<td>"
                  + (editable ? "<textarea "
                    + "class=\"settle-remarks\" "
                    + "name=\"settle_remarks[]\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'settle_remarks[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onchange=\"onSettleRemarksChange(event, " + i + ")\" "
                    + "onkeydown=\"onSettleRemarksKeyDown(event, " + i + ")\" "
                  + "/>" + invoiceVoucher["settle_remarks"] + "</textarea>" : description + "<input type=\"hidden\" name=\"settle_remarks[]\" value=\"" + invoiceVoucher["settle_remarks"] + "\" />")
                + "</td>"
                + "<td>"
                  + "<input type=\"hidden\" name=\"settlement[]\" value=\"" + invoiceVoucher["settlement"] + "\" />"
                  + "<input type=\"hidden\" name=\"do_no[]\" value=\"" + invoiceVoucher["do_no"] + "\" />"
                  + "<input type=\"hidden\" name=\"stock_out_no[]\" value=\"" + invoiceVoucher["stock_out_no"] + "\" />"
                  + "<input type=\"hidden\" name=\"stock_in_no[]\" value=\"" + invoiceVoucher["stock_in_no"] + "\" />"
                  + "<input type=\"hidden\" name=\"amount[]\" value=\"" + invoiceVoucher["amount"] + "\" />"
                  + (editable ? "<input "
                    + "class=\"amount number\" "
                    + "type=\"number\" "
                    + "step=\"0.01\" "
                    + "name=\"amount[]\" "
                    + "value=\"" + invoiceVoucher["amount"].toFixed(2) + "\" "
                    + "onchange=\"onAmountChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'amount[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onAmountKeyDown(event, " + i + ")\" "
                    + "required "
                  + "/>" : "<span class=\"number\">" + invoiceVoucher["amount"].toFixed(2) + "</span>")
                + "</td>"
                + "<td>"
                  + (editable ? "<div class=\"remove\" onclick=\"removeItem(" + i + ")\">×</div>" : "")
                + "</td>";

              newRowElement.innerHTML = rowInnerHTML;

              totalAmount += parseFloat(invoiceVoucher["amount"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (invoiceVouchers.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\" id=\"invoice-entry-no-model\">No vouchers</td>";
              tableBodyElement.appendChild(rowElement);
            }

            totalAmountElement.innerHTML = totalAmount.toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateVoucher(index, voucher = {}) {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["do_no"] = voucher["do_no"] || "";
            invoiceVoucher["stock_out_no"] = voucher["stock_out_no"] || "";
            invoiceVoucher["stock_in_no"] = voucher["stock_in_no"] || "";
            invoiceVoucher["settlement"] = voucher["settlement"] || "FULL";
            invoiceVoucher["amount"] = parseFloat(voucher["amount"]) || 0;
            invoiceVoucher["settle_remarks"] = voucher["settle_remarks"] || "";
          }

          function updateAmount(index, amount = 0) {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["amount"] = parseFloat(amount);
          }

          function updateSettleRemarks(index, remarks = "") {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["settle_remarks"] = remarks;
          }

          function addItem() {
            invoiceVouchers.push({});

            updateVoucher(invoiceVouchers.length - 1);
            render();
          }

          function removeItem(index) {
            invoiceVouchers.splice(index, 1);
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

          function onDebtorCodeChange() {
            var invoiceDate = new Date(invoiceDateElement.value);
            var creditTerm = debtors[debtorCodeElement.value]["credit_term"];
            var maturityDate = new Date(invoiceDate.getTime() + 86400000 * creditTerm);
            maturityDateElement.value = formatDate(maturityDate);
          }

          function onCurrencyCodeChange() {
            var currencyCode = currencyCodeElement.value;

            exchangeRateElement.value = currencies[currencyCode];
            if (currencyCode === "<?php echo COMPANY_CURRENCY; ?>") {
              exchangeRateElement.setAttribute("readonly", true);
            } else {
              exchangeRateElement.removeAttribute("readonly");
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

          function onAmountKeyDown(event, index) {
            var invoiceVoucher = invoiceVouchers[index];

            if (
              index === invoiceVouchers.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              invoiceVoucher["settle_remarks"] &&
              invoiceVoucher["amount"]
            ) {
              addItem();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#invoice-form").reset();

            for (var i = 0; i < invoiceVouchers.length; i++) {
              var invoiceVoucher = invoiceVouchers[i];
              var amount = invoiceVoucher["amount"];
              var settleRemarks = invoiceVoucher["settle_remarks"];

              updateVoucher(i, invoiceVoucher);
              updateSettleRemarks(i, settleRemarks);
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
