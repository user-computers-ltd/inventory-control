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
              <col style="width: 150px">
              <col>
              <col>
              <col>
              <col>
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr>
                <th></th>
                <th>DO No. / Stock Out No.</th>
                <th class="number">Payable</th>
                <th class="number">Amount</th>
                <th>Settlement</th>
                <th>Remarks</th>
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
                <th></th>
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

              var byOrder = invoiceVoucher["by_order"];
              var doListElement = document.querySelector("#delivery-order-list-" + debtorCode + "-" + currencyCode + "");
              var stockOutListElement = document.querySelector("#stock-out-voucher-list-" + debtorCode + "-" + currencyCode + "");

              var rowInnerHTML =
                  "<td>"
                  + "<div>"
                    + "<input "
                      + "id=\"by_order_do_" + i + "\" "
                      + "type=\"radio\" "
                      + "name=\"by_order_" + i + "\" "
                      + "value=\"true\" "
                      + "onchange=\"onByOrderChange(true, " + i + ")\" "
                      + (byOrder ? "checked" : "")
                    + "/><label for=\"by_order_do_" + i + "\">DO No.</label><br/>"
                    + "<input "
                      + "id=\"by_order_si_" + i + "\" "
                      + "type=\"radio\" "
                      + "name=\"by_order_" + i + "\" "
                      + "value=\"false\" "
                      + "onchange=\"onByOrderChange(false, " + i + ")\" "
                      + (byOrder ? "" : "checked")
                    + "/><label for=\"by_order_si_" + i + "\">Stock Out No.</label>"
                  + "</div>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"do-no " + (byOrder ? "" : "hide") + "\" "
                    + "name=\"do_no[]\" "
                    + "onchange=\"onDONoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'do_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + (byOrder ? "required" : "")
                  + ">"
                    + "<option value=\"\"></option>";

              if (doListElement) {
                for (var j = 0; j < doListElement.children.length; j++) {
                  var doNo = doListElement.children[j].value;
                  var selected = invoiceVoucher["do_no"] === doNo ? " selected" : "";
                  var disabled = invoiceVouchers.filter(function (m, mi) {
                    return mi !== i && m["do_no"] === doNo;
                  }).length > 0 ? " disabled" : "";
                  rowInnerHTML += "<option value=\"" + doNo + "\"" + selected + disabled + ">" + doNo + "</option>";
                }
              }

              rowInnerHTML += "</select>"
                  + "<select "
                    + "class=\"stock-out-no " + (byOrder ? "hide" : "") + "\" "
                    + "name=\"stock_out_no[]\" "
                    + "onchange=\"onStockOutNoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'stock_out_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + (byOrder ? "" : "required")
                  + ">"
                    + "<option value=\"\"></option>";

              if (stockOutListElement) {
                for (var j = 0; j < stockOutListElement.children.length; j++) {
                  var stockOutNo = stockOutListElement.children[j].value;
                  var selected = invoiceVoucher["stock_out_no"] === stockOutNo ? " selected" : "";

                  rowInnerHTML += "<option value=\"" + stockOutNo + "\"" + selected + ">" + stockOutNo + "</option>";
                }
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<span class=\"number\">" + invoiceVoucher["amount_payable"] + "</span>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"amount number\" "
                    + "type=\"number\" "
                    + "step=\"0.01\" "
                    + "min=\"0\" "
                    + "name=\"amount[]\" "
                    + "value=\"" + invoiceVoucher["amount"].toFixed(2) + "\" "
                    + "onchange=\"onAmountChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'amount[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td>"
                + "<input "
                  + "type=\"hidden\" "
                  + "name=\"settlement[]\" "
                  + "value=\"" + invoiceVoucher["settlement"] + "\" "
                  + "required "
                + "/>"
                  + "<div>"
                    + "<input "
                      + "id=\"settlement_full_" + i + "\" "
                      + "type=\"radio\" "
                      + "name=\"settlement_" + i + "\" "
                      + "value=\"FULL\" "
                      + "onchange=\"onSettlementChange('FULL', " + i + ")\" "
                      + (invoiceVoucher["settlement"] === "FULL" ? "checked" : "")
                    + "><label for=\"settlement_full_" + i + "\">Full</label><br/>"
                    + "<input "
                      + "id=\"settlement_partial_" + i + "\" "
                      + "type=\"radio\" "
                      + "name=\"settlement_" + i + "\" "
                      + "value=\"PARTIAL\" "
                      + "onchange=\"onSettlementChange('PARTIAL', " + i + ")\" "
                      + (invoiceVoucher["settlement"] === "PARTIAL" ? "checked" : "")
                    + "><label for=\"settlement_partial_" + i + "\">Partial</label>"
                  + "</div>"
                + "</td>"
                + "<td>"
                  + "<textarea "
                    + "class=\"settle-remarks\" "
                    + "name=\"settle_remarks[]\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'settle_remarks[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onchange=\"onSettleRemarksChange(event, " + i + ")\" "
                    + "onkeydown=\"onSettleRemarksKeyDown(event, " + i + ")\" "
                  + "/>" + invoiceVoucher["settle_remarks"] + "</textarea>"
                + "</td>"
                + "<td><div class=\"remove\" onclick=\"removeItem(" + i + ")\">×</div></td>";

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
            invoiceVoucher["amount_payable"] = 0;
          }

          function updateVoucher(index, voucher = {}) {
            var invoiceVoucher = invoiceVouchers[index];

            invoiceVoucher["stock_out_no"] = voucher["stock_out_no"] || "";
            invoiceVoucher["do_no"] = voucher["do_no"] || "";
            invoiceVoucher["by_order"] = voucher["stock_out_no"] ? false : true;
            invoiceVoucher["amount_payable"] = parseFloat(voucher["amount"]) || 0;
            invoiceVoucher["amount"] = parseFloat(voucher["amount"]) || 0;
            invoiceVoucher["settlement"] = voucher["settlement"] || "FULL";
            invoiceVoucher["settle_remarks"] = voucher["settle_remarks"] || "";
          }

          function updateAmount(index, amount = 0) {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["amount"] = parseFloat(amount);
          }

          function updateSettlement(index, settlement = "FULL") {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["settlement"] = settlement;
          }

          function updateOffset(index, offset = 0) {
            var invoiceVoucher = invoiceVouchers[index];
            invoiceVoucher["offset"] = parseFloat(offset);
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

          function onSettlementChange(settlement, index) {
            updateSettlement(index, settlement);
            render();
          }

          function onSettleRemarksChange(event, index) {
            updateSettleRemarks(index, event.target.value);
            render();
          }

          function onSettleRemarksKeyDown(event, index) {
            var invoiceVoucher = invoiceVouchers[index];

            if (
              index === invoiceVouchers.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              (invoiceVoucher["do_no"] || invoiceVoucher["stock_out_no"]) &&
              invoiceVoucher["amount"]
            ) {
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
              var settlement = invoiceVoucher["settlement"];
              var settleRemarks = invoiceVoucher["settle_remarks"];

              if (doNo) {
                updateVoucher(i, getDeliveryOrders(doNo)[0]);
              } else if (stockOutNo) {
                updateVoucher(i, getStockOutVouchers(stockOutNo)[0]);
              }

              updateSettlement(i, settlement);
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
