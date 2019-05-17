<?php
  define("SYSTEM_PATH", "../../../../");

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
        <form id="invoice-form" method="post">
          <table id="invoice-header">
            <tr>
              <td>Invoice No.:</td>
              <td><input type="text" name="invoice_no" value="<?php echo $invoiceNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="invoice_date" value="<?php echo $invoiceDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <select id="debtor-code" name="debtor_code" onchange="onDebtorCodeChange()" required>
                  <?php
                    foreach ($debtors as $debtor) {
                      $code = $debtor["code"];
                      $label = $debtor["code"] . " - " . $debtor["name"];
                      $selected = $debtorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$label</option>";
                    }
                  ?>
                </select>
              </td>
              <td>Currency:</td>
              <td>
                <select id="currency-code" name="currency_code" onchange="onCurrencyCodeChange()" required>
                  <?php
                    foreach ($currencies as $code => $rate) {
                      $selected = $currencyCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code</option>";
                    }
                  ?>
                </select>
                <input
                  id="exchange-rate"
                  class="option-field"
                  name="exchange_rate"
                  type="number"
                  step="0.00000001"
                  min="0.00000001"
                  value="<?php echo $exchangeRate; ?>"
                  required
                  <?php echo $currencyCode === COMPANY_CURRENCY ? "readonly" : ""; ?>
                />
              </td>
            </tr>
          </table>
          <button type="button" onclick="addItem()">Add</button>
          <table id="invoice-vouchers">
            <colgroup>
              <col>
              <col>
              <col>
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr>
                <th></th>
                <th>DO No. / Stock Out No.</th>
                <th class="number">Amount</th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr>
                <th></th>
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
          <?php if ($status == "DRAFT" || $status == "SAVED") : ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <?php if ($status == "SAVED") : ?>
            <button name="status" type="submit" value="PAID">Settle</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo SALES_INVOICE_PRINTOUT_URL; ?>">Print</button>
          <?php if ($status == "SAVED" || $status == "PAID") : ?>
            <button name="status" type="submit" value="DELETED">Delete</button>
          <?php endif ?>
        </form>
        <?php
          foreach ($stockOutVouchers as $dCode => $voucherList) {
            foreach ($voucherList as $cCode => $vouchers) {
              echo "<datalist id=\"stock-out-voucher-list-$dCode-$cCode\">";
              foreach ($vouchers as $voucher) {
                echo "<option value=\"" . $voucher["stock_out_no"]
                  . "\" data-stock_out_no=\"" . $voucher["stock_out_no"]
                  . "\" data-amount=\"" . round($voucher["amount"], 2)
                  . "\">" . $voucher["stock_out_no"] . "</option>";
              }
              echo "</datalist>";
            }
          }
          foreach ($deliveryOrders as $dCode => $orderList) {
            foreach ($orderList as $cCode => $orders) {
              echo "<datalist id=\"delivery-order-list-$dCode-$cCode\">";
              foreach ($orders as $order) {
                echo "<option value=\"" . $order["do_no"]
                  . "\" data-do_no=\"" . $order["do_no"]
                  . "\" data-amount=\"" . round($order["amount"], 2)
                  . "\">" . $order["do_no"] . "</option>";
              }
              echo "</datalist>";
            }
          }
        ?>
        <script>
          var invoiceVouchers = <?php echo json_encode($invoiceVouchers); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var brands = <?php echo json_encode($brands); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var debtorCodeElement = document.querySelector("#debtor-code");
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");
          var tableBodyElement = document.querySelector("#invoice-vouchers tbody");
          var totalAmountElement = document.querySelector("#total-amount");
          var modelListElement = document.querySelector("#model-list");

          function getStockOutVouchers(stockOutNo) {
            var debtorCode = debtorCodeElement.value;
            var currencyCode = currencyCodeElement.value;
            var voucherListElement = document.querySelector("#stock-out-voucher-list-" + debtorCode + "-" + currencyCode);
            var matchedElements = (voucherListElement && voucherListElement.querySelectorAll("option[value=\"" + stockOutNo + "\"]")) || [];
            var vouchers = [];

            for (var i = 0; i < matchedElements.length; i++) {
              vouchers.push(matchedElements[i].dataset);
            }

            return vouchers;
          }

          function getDeliveryOrders(doNo) {
            var debtorCode = debtorCodeElement.value;
            var currencyCode = currencyCodeElement.value;
            var doListElement = document.querySelector("#delivery-order-list-" + debtorCode + "-" + currencyCode);
            var matchedElements = (doListElement && doListElement.querySelectorAll("option[value=\"" + doNo + "\"]")) || [];
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

            for (var i = 0; i < invoiceVouchers.length; i++) {
              var debtorCode = debtorCodeElement.value;
              var currencyCode = currencyCodeElement.value;

              var invoiceVoucher = invoiceVouchers[i];
              var newRowElement = document.createElement("tr");

              var rowInnerHTML =
                  "<td>"
                  + "<div>"
                    + "<input "
                      + "type=\"radio\" "
                      + "name=\"by_order_" + i + "\" "
                      + "value=\"true\" "
                      + "onchange=\"onByOrderChange(true, " + i + ")\" "
                      + (invoiceVoucher["by_order"] ? "checked" : "")
                    + ">DO No."
                    + "<input "
                      + "type=\"radio\" "
                      + "name=\"by_order_" + i + "\" "
                      + "value=\"false\" "
                      + "onchange=\"onByOrderChange(false, " + i + ")\" "
                      + (invoiceVoucher["by_order"] ? "" : "checked")
                    + ">Stock Out No."
                  + "</div>"
                + "</td>"
                + "<td>"
                + "<input "
                  + "class=\"do-no " + (invoiceVoucher["by_order"] ? "" : "hide") + "\" "
                  + "type=\"text\" "
                  + "name=\"do_no[]\" "
                  + "list=\"delivery-order-list-" + debtorCode + "-" + currencyCode + "\" "
                  + "value=\"" + invoiceVoucher["do_no"] + "\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'do_no[]')\" "
                  + "onblur=\"onDONoChange(event, " + i + ")\" "
                  + "autocomplete=\"on\" "
                  + (invoiceVoucher["by_order"] ? "required" : "")
                + "/>"
                + "<input "
                  + "class=\"stock-out-no " + (invoiceVoucher["by_order"] ? "hide" : "") + "\" "
                  + "type=\"text\" "
                  + "name=\"stock_out_no[]\" "
                  + "list=\"stock-out-voucher-list-" + debtorCode + "-" + currencyCode + "\" "
                  + "value=\"" + invoiceVoucher["stock_out_no"] + "\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'stock_out_no[]')\" "
                  + "onblur=\"onStockOutNoChange(event, " + i + ")\" "
                  + "autocomplete=\"on\" "
                  + (invoiceVoucher["by_order"] ? "" : "required")
                + "/>"
              + "</td>"
              + "<td>"
                + "<input "
                  + "class=\"amount number\" "
                  + "type=\"number\" "
                  + "step=\"0.01\" "
                  + "min=\"0\" "
                  + "max=\"" + invoiceVoucher["amount_payable"] + "\" "
                  + "name=\"amount[]\" "
                  + "value=\"" + invoiceVoucher["amount"].toFixed(2) + "\" "
                  + "onchange=\"onAmountChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'amount[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "onkeydown=\"onAmountKeyDown(event, " + i + ")\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td><div class=\"remove\" onclick=\"removeStockOutModel(" + i + ")\">×</div></td>";

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

          function updateByOrder(index, byOrder = true) {
            var invoiceVoucher = invoiceVouchers[index];

            invoiceVoucher["by_order"] = byOrder;
            invoiceVoucher["stock_out_no"] = byOrder ? "" : invoiceVoucher["stock_out_no"];
            invoiceVoucher["do_no"] = byOrder ? invoiceVoucher["do_no"] : "";
            invoiceVoucher["amount"] = 0;
          }

          function updateVoucher(index, voucher = {}) {
            var invoiceVoucher = invoiceVouchers[index];

            invoiceVoucher["stock_out_no"] = voucher["stock_out_no"] || "";
            invoiceVoucher["do_no"] = voucher["do_no"] || "";
            invoiceVoucher["by_order"] = voucher["stock_out_no"] ? false : true;
            invoiceVoucher["amount_payable"] = parseFloat(voucher["amount"]) || 0;
            invoiceVoucher["amount"] = parseFloat(voucher["amount"]) || 0;
          }

          function updateAmount(index, amount = 0) {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["amount"] = Math.min(invoiceVoucher["amount_payable"], Math.max(0, parseFloat(amount)));
          }

          function addItem() {
            invoiceVouchers.push({});

            updateVoucher(invoiceVouchers.length - 1);
            render();
          }

          function removeStockOutModel(index) {
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
            for (var i = 0; i < invoiceVouchers.length; i++) {
              var invoiceVoucher = invoiceVouchers[i];
              var doNo = invoiceVoucher["do_no"];
              var stockOutNo = invoiceVoucher["stock_out_no"];
              var amount = invoiceVoucher["amount"];

              if (doNo) {
                updateVoucher(i, getDeliveryOrders(doNo)[0]);
              } else if (stockOutNo) {
                updateVoucher(i, getStockOutVouchers(stockOutNo)[0]);
              }

              updateAmount(i, amount);
            }

            render();
          }

          function onCurrencyCodeChange() {
            var currencyCode = currencyCodeElement.value;

            exchangeRateElement.value = currencies[currencyCode];
            if (currencyCode === "<?php echo COMPANY_CURRENCY; ?>") {
              exchangeRateElement.setAttribute("readonly", true);
            } else {
              exchangeRateElement.removeAttribute("readonly");
            }

            for (var i = 0; i < invoiceVouchers.length; i++) {
              var invoiceVoucher = invoiceVouchers[i];
              var doNo = invoiceVoucher["do_no"];
              var stockOutNo = invoiceVoucher["stock_out_no"];
              var amount = invoiceVoucher["amount"];

              if (doNo) {
                updateVoucher(i, getDeliveryOrders(doNo)[0]);
              } else if (stockOutNo) {
                updateVoucher(i, getStockOutVouchers(stockOutNo)[0]);
              }

              updateAmount(i, amount);
            }

            render();
          }

          function onByOrderChange(byOrder, index) {
            updateByOrder(index, byOrder);

            render();
          }

          function onStockOutNoChange(event, index) {
            var newStockOutNo = event.target.value;
            var matchedVoucher = getStockOutVouchers(newStockOutNo)[0];
            var invoiceVoucher = invoiceVouchers[index];

            if (invoiceVoucher["stock_out_no"] !== newStockOutNo) {
              var existsAlready = invoiceVouchers.filter(function (m) {
                return newStockOutNo && m["stock_out_no"] === newStockOutNo;
              }).length > 0;

              if (!existsAlready) {
                updateVoucher(index, matchedVoucher);
              }

              render();
            }

            onFieldBlurred();
          }

          function onDONoChange(event, index) {
            var newDONo = event.target.value;
            var matchedVoucher = getDeliveryOrders(newDONo)[0];
            var invoiceVoucher = invoiceVouchers[index];

            if (invoiceVoucher["do_no"] !== newDONo) {
              var existsAlready = invoiceVouchers.filter(function (m) {
                return newDONo && m["do_no"] === newDONo;
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
            var invoiceVoucher = invoiceVouchers[index];

            if (
              index === invoiceVouchers.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              (invoiceVoucher["do_no"] || invoiceVoucher["stock_out_no"]) &&
              invoiceVoucher["amount"]
            ) {
              updateAmount(index, event.target.value);
              addItem();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#invoice-form").reset();

            for (var i = 0; i < invoiceVouchers.length; i++) {
              var invoiceVoucher = invoiceVouchers[i];
              var doNo = invoiceVoucher["do_no"];
              var stockOutNo = invoiceVoucher["stock_out_no"];
              var amount = invoiceVoucher["amount"];

              if (doNo) {
                updateVoucher(i, getDeliveryOrders(doNo)[0]);
              } else if (stockOutNo) {
                updateVoucher(i, getStockOutVouchers(stockOutNo)[0]);
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
