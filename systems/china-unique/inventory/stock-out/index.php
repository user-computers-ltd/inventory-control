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
      <?php if (assigned($stockOutNo)) : ?>
        <form id="stock-out-form" method="post">
          <table id="stock-out-header">
            <tr>
              <td>Voucher No.:</td>
              <td><input type="text" name="stock_out_no" value="<?php echo $stockOutNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="stock_out_date" value="<?php echo $stockOutDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Transaction Code:</td>
              <td>
                <select id="transaction-code" name="transaction_code" onchange="onTransactionCodeChange()" required>
                  <?php
                    foreach ($transactionCodes as $code => $name) {
                      $selected = $transactionCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code - $name</option>";
                    }
                  ?>
                </select>
              </td>
              <td>Warehouse:</td>
              <td>
                <select id="warehouse-code" name="warehouse_code" onchange="onWarehouseCodeChange()" required>
                  <?php
                    foreach ($warehouses as $warehouse) {
                      $code = $warehouse["code"];
                      $name = $warehouse["name"];
                      $selected = $warehouseCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code - $name</option>";
                    }
                  ?>
                </select>
              </td>
            </tr>
            <tr>
              <td class="s7-cell">Client:</td>
              <td class="s7-cell">
                <select id="debtor-code" class="option-field" name="debtor_code" required <?php echo $useCreditor ? "disabled hidden" : ""; ?>>
                  <?php
                    foreach ($debtors as $debtor) {
                      $code = $debtor["code"];
                      $label = $debtor["code"] . " - " . $debtor["name"];
                      $selected = $debtorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$label</option>";
                    }
                  ?>
                </select>
                <select id="creditor-code" class="option-field" name="debtor_code" required <?php echo $useCreditor ? "" : "disabled hidden"; ?>>
                  <?php
                    foreach ($creditors as $creditor) {
                      $code = $creditor["code"];
                      $label = $creditor["code"] . " - " . $creditor["name"];
                      $selected = $debtorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$label</option>";
                    }
                  ?>
                </select>
              </td>
              <td class="misc-cell">Currency:</td>
              <td class="misc-cell">
                <select id="currency-code" class="option-field" name="currency_code" onchange="onCurrencyCodeChange()" required>
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
                  onchange="onExchangeRateChange()"
                  required
                  <?php echo $currencyCode === COMPANY_CURRENCY ? "readonly" : ""; ?>
                />
              </td>
            </tr>
            <tr>
              <td class="misc-cell">Discount:</td>
              <td class="misc-cell">
                <input
                  id="discount"
                  class="option-field"
                  name="discount"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value="<?php echo $discount; ?>"
                  onchange="onDiscountChange()"
                  required
                /><span>%</span>
                <input id="tax" name="tax" type="number" value="<?php echo $tax; ?>" hidden required />
              </td>
              <td class="misc-cell">Net Amount:</td>
              <td class="misc-cell">
                <input
                  id="net-amount"
                  class="option-field"
                  name="net_amount"
                  type="number"
                  min="0"
                  step="0.01"
                  value="<?php echo $netAmount; ?>"
                  onchange="onNetAmountChange()"
                  required
                />
              </td>
            </tr>
            <tr>
              <td colspan="2" class="misc-cell">
                <input
                  id="normal-price"
                  name="price_standard"
                  type="radio"
                  value="normal_price"
                  onchange="onPriceStandardChange()"
                  checked
                />
                <label for="normal-price">Normal Price</label>
                <input
                  id="special-price"
                  name="price_standard"
                  type="radio"
                  value="special_price"
                  onchange="onPriceStandardChange()"
                />
                <label for="special-price">Special Price</label>
              </td>
            </tr>
          </table>
          <button type="button" onclick="addStockOutModel()">Add</button>
          <table id="stock-out-models">
            <colgroup>
              <col>
              <col>
              <col>
              <col>
              <col>
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr>
                <th>Model no.</th>
                <th>Brand code</th>
                <th class="number">Quantity</th>
                <th class="option-column number">Price</th>
                <th class="option-column number">Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr class="discount-row">
                <td colspan="3"></td>
                <th></th>
                <th id="sub-total-amount" class="number"></th>
                <td></td>
              </tr>
              <tr class="discount-row">
                <td colspan="3"></td>
                <td id="discount-percentage" class="number"></td>
                <td id="discount-amount" class="number"></td>
                <td></td>
              </tr>
              <tr>
                <th></th>
                <th class="number">Total:</th>
                <th id="total-qty" class="number"></th>
                <th></th>
                <th id="total-amount" class="number"></th>
                <th></th>
              <tr>
              </tr>
                <td></td>
                <td></td>
                <td></td>
                <th class="number">Variance:</th>
                <th id="variance" class="number"></th>
                <td></td>
              </tr>
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <table id="stock-out-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status == "DRAFT" || $status == "SAVED") : ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo STOCK_OUT_PRINTOUT_URL; ?>">Print</button>
          <?php if ($status == "SAVED") : ?>
            <button name="status" type="submit" value="POSTED">Post</button>
            <button name="status" type="submit" value="DELETED">Delete</button>
          <?php endif ?>
        </form>
        <?php
          foreach ($models as $wc => $modelList) {
            echo "<datalist id=\"model-list-$wc\">";

            foreach ($modelList as $model) {
              echo "<option value=\"" . $model["model_no"]
               . "\" data-model_no=\"" . $model["model_no"]
               . "\" data-brand_code=\"" . $model["brand_code"]
               . "\" data-normal_price=\"" . $model["normal_price"]
               . "\" data-special_price=\"" . $model["special_price"]
               . "\" data-qty_on_hand=\"" . $model["qty_on_hand"]
               . "\" data-qty_on_reserve=\"" . $model["qty_on_reserve"]
               . "\" data-qty_available=\"" . $model["qty_available"]
               . "\">" . $model["model_no"] . "</option>";
            }

            echo "</datalist>";
          }
        ?>
        <script>
          var stockOutModels = <?php echo json_encode($stockOutModels); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var brands = <?php echo json_encode($brands); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var transactionCodeElement = document.querySelector("#transaction-code");
          var warehouseCodeElement = document.querySelector("#warehouse-code");
          var netAmountElement = document.querySelector("#net-amount");
          var discountElement = document.querySelector("#discount");
          var taxElement = document.querySelector("#tax");
          var debtorCodeElement = document.querySelector("#debtor-code");
          var creditorCodeElement = document.querySelector("#creditor-code");
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");
          var tableBodyElement = document.querySelector("#stock-out-models tbody");
          var discountRowElements = document.querySelectorAll(".discount-row");
          var subTotalAmountElement = document.querySelector("#sub-total-amount");
          var discountPercentageElement = document.querySelector("#discount-percentage");
          var discountAmountElement = document.querySelector("#discount-amount");
          var totalQtyElement = document.querySelector("#total-qty");
          var totalAmountElement = document.querySelector("#total-amount");
          var varianceElement = document.querySelector("#variance");

          function getModels(modelNo, brandCode) {
            var warehouseCode = warehouseCodeElement.value;
            var modelListElement = document.querySelector("#model-list-" + warehouseCode);
            var brandCodeSearch = brandCode ? "[data-brand_code=\"" + brandCode + "\"]" : "";
            var matchedModelElements = modelListElement.querySelectorAll("option[value=\"" + modelNo + "\"]" + brandCodeSearch);
            var models = [];

            for (var i = 0; i < matchedModelElements.length; i++) {
              models.push(matchedModelElements[i].dataset);
            }

            return models;
          }

          function render() {
            var focusedElement = null;

            tableBodyElement.innerHTML = "";

            var transactionCode = transactionCodeElement.value;
            var miscellaneous = transactionCode !== "S1" && transactionCode !== "S3";
            var warehouseCode = warehouseCodeElement.value;
            var netAmount = netAmountElement.value;
            var discount = discountElement.value;
            var totalQty = 0;
            var totalAmount = 0;

            for (var i = 0; i < stockOutModels.length; i++) {
              var stockOutModel = stockOutModels[i];
              var matchedModels = getModels(stockOutModel["model_no"]);
              var newRowElement = document.createElement("tr");

              var rowInnerHTML =
                  "<tr>"
                + "<td>"
                  + "<input "
                    + "class=\"model-no\" "
                    + "type=\"text\" "
                    + "name=\"model_no[]\" "
                    + "list=\"model-list-" + warehouseCode + "\" "
                    + "value=\"" + stockOutModel["model_no"] + "\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'model_no[]')\" "
                    + "onblur=\"onModelNoChange(event, " + i + ")\" "
                    + "autocomplete=\"on\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"brand-code\" "
                    + "name=\"brand_code[]\" "
                    + "value=\"" + stockOutModel["brand_code"] + "\" "
                    + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required"
                  + ">";

              for (var j = 0; j < brands.length; j++) {
                var code = brands[j]["code"];
                var selected = stockOutModel["brand_code"] === code ? " selected" : "";
                var disabled = matchedModels.map(function (model) {
                  return model["brand_code"];
                }).indexOf(code) === -1 ? " disabled hidden" : "";

                rowInnerHTML += "<option value=\"" + code + "\"" + selected + disabled + ">" + code + "</option>";
              }

              rowInnerHTML +=
                  "</select>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"qty number\" "
                    + "type=\"number\" "
                    + "min=\"0\" "
                    + "max=\"" + stockOutModel["qty_available"] + "\" "
                    + "name=\"qty[]\" "
                    + "value=\"" + stockOutModel["qty"] + "\" "
                    + "onchange=\"onQuantityChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onQuantityKeyDown(event, " + i + ")\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"price option-field number\" "
                    + "type=\"number\" "
                    + "step=\"0.000001\" "
                    + "min=\"0\" "
                    + "name=\"price[]\" "
                    + "value=\"" + stockOutModel["price"].toFixed(6) + "\" "
                    + "onchange=\"onPriceChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'price[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onPriceKeyDown(event, " + i + ")\" "
                    + "required "
                    + (miscellaneous ? "disabled" : "")
                  + "/>"
                + "</td>"
                + "<td class=\"total-amount number\">" + stockOutModel["total_amount"].toFixed(2) + "</td>"
                + "<td><div class=\"remove\" onclick=\"removeStockOutModel(" + i + ")\">×</div></td>"
                + "</tr>";

              newRowElement.innerHTML = rowInnerHTML;

              totalQty += parseFloat(stockOutModel["qty"]);
              totalAmount += parseFloat(stockOutModel["price"] * stockOutModel["qty"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (stockOutModels.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\" id=\"stock-out-entry-no-model\">No models</td>";
              tableBodyElement.appendChild(rowElement);
            }

            for (var k = 0; k < discountRowElements.length; k++) {
              toggleClass(discountRowElements[k], "show", stockOutModels.length > 0 && discount > 0);
            }

            subTotalAmountElement.innerHTML = totalAmount.toFixed(2);

            discountPercentageElement.innerHTML = "Discount " + discount + "%";
            discountAmountElement.innerHTML = (totalAmount * (discount) / 100).toFixed(2);

            totalQtyElement.innerHTML = totalQty;
            totalAmountElement.innerHTML = (totalAmount * (100 - discount) / 100).toFixed(2);
            varianceElement.innerHTML = (netAmount - totalAmount * (100 - discount) / 100).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateModel(index, model = {}) {
            var priceStandard = document.querySelector("input[name='price_standard']:checked").value;

            var stockOutModel = stockOutModels[index];

            stockOutModel["model_no"] = model["model_no"] || "";
            stockOutModel["brand_code"] = model["brand_code"] || "";
            stockOutModel["cost_average"] = parseFloat(model["cost_average"]) || 0;
            stockOutModel["normal_price"] = parseFloat(model["normal_price"]) || 0;
            stockOutModel["special_price"] = parseFloat(model["special_price"]) || 0;
            stockOutModel["price"] = parseFloat(model[priceStandard]) || 0;
            stockOutModel["qty"] = stockOutModel["qty"] || 0;
            stockOutModel["total_amount"] = (stockOutModel["qty"] || 0) * stockOutModel["price"];
            stockOutModel["qty_on_hand"] = parseFloat(model["qty_on_hand"]) || 0;
            stockOutModel["qty_on_reserve"] = parseFloat(model["qty_on_reserve"]) || 0;
            stockOutModel["qty_available"] = parseFloat(model["qty_available"]) || 0;
          }

          function updateQuantity (index, qty = 0) {
            var stockOutModel = stockOutModels[index];

            stockOutModel["qty"] = Math.min(Math.max(0, parseFloat(qty)), stockOutModel["qty_available"]);

            if (stockOutModel["price"]) {
              stockOutModel["total_amount"] = stockOutModel["price"] * stockOutModel["qty"];
            }
          }

          function updatePrice(index, price = 0) {
            var stockOutModel = stockOutModels[index];

            stockOutModel["price"] = Math.max(0, parseFloat(price));

            if (stockOutModel["qty"]) {
              stockOutModel["total_amount"] = stockOutModel["price"] * stockOutModel["qty"];
            }
          }

          function addStockOutModel() {
            stockOutModels.push({});

            updateModel(stockOutModels.length - 1);
            render();
          }

          function removeStockOutModel(index) {
            stockOutModels.splice(index, 1);
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

          function onTransactionCodeChange() {
            var miscCells = document.querySelectorAll(".misc-cell");
            var s7Cells = document.querySelectorAll(".s7-cell");
            var optionFields = document.querySelectorAll(".option-field");
            var optionColumns = document.querySelectorAll(".option-column");
            var transactionCode = transactionCodeElement.value;
            var miscellaneous = transactionCode !== "S1" && transactionCode !== "S3";

            for (var i = 0; i < miscCells.length; i++) {
              toggleClass(miscCells[i], "hide", miscellaneous);
            }

            for (var i = 0; i < s7Cells.length; i++) {
              toggleClass(s7Cells[i], "hide", miscellaneous && transactionCode !== "S7");
            }

            for (var i = 0; i < optionFields.length; i++) {
              optionFields[i].disabled = miscellaneous;
            }

            for (var i = 0; i < optionColumns.length; i++) {
              toggleClass(optionColumns[i], "hide", miscellaneous);
            }

            debtorCodeElement.disabled = transactionCode === "S3";
            debtorCodeElement.hidden = transactionCode === "S3";
            creditorCodeElement.disabled = transactionCode === "S1" || transactionCode === "S7";
            creditorCodeElement.hidden = transactionCode === "S1" || transactionCode === "S7";
          }

          function onWarehouseCodeChange() {
            for (var i = 0; i < stockOutModels.length; i++) {
              var stockOutModel = stockOutModels[i];
              var matchedModel = getModels(stockOutModel["model_no"], stockOutModel["brand_code"])[0];

              updateModel(i, matchedModel);
              updateQuantity(i, stockOutModel["qty"]);
            }

            render();
          }

          function onNetAmountChange() {
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
          }

          function onExchangeRateChange() {
          }

          function onDiscountChange() {
          }

          function onPriceStandardChange() {
            for (var i = 0; i < stockOutModels.length; i++) {
              var stockOutModel = stockOutModels[i];
              var matchedModel = getModels(stockOutModel["model_no"], stockOutModel["brand_code"])[0];

              updateModel(i, matchedModel);
            }

            render();
          }

          function onModelNoChange(event, index) {
            var newModelNo = event.target.value;
            var matchedModel = getModels(newModelNo)[0];
            var stockOutModel = stockOutModels[index];

            if (stockOutModel["model_no"] !== newModelNo) {
              var existsAlready = stockOutModels.filter(function (m) {
                return newModelNo && m["model_no"] === newModelNo;
              }).length > 0;

              if (!existsAlready) {
                updateModel(index, matchedModel);
              }

              render();
            }

            onFieldBlurred();
          }

          function onBrandCodeChange(event, index) {
            var modelNo = stockOutModels[index]["model_no"];
            var brandCode = event.target.value;
            var matchedModel =
              modelNo &&
              brandCode &&
              getModels(modelNo).filter(function (model) {
                return model["brand_code"] === brandCode;
              })[0] || undefined;

            updateModel(index, matchedModel);
            render();
          }

          function onPriceChange(event, index) {
            updatePrice(index, event.target.value);
            render();
          }

          function onPriceKeyDown(event, index) {
            var stockOutModel = stockOutModels[index];

            if (
              index === stockOutModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              stockOutModel["model_no"] &&
              stockOutModel["brand_code"] &&
              stockOutModel["qty"] &&
              stockOutModel["price"]
            ) {
              updatePrice(index, event.target.value);
              addStockOutModel();
            }
          }

          function onQuantityChange(event, index) {
            updateQuantity(index, event.target.value);
            render();
          }

          function onQuantityKeyDown(event, index) {
            var stockOutModel = stockOutModels[index];
            var transactionCode = transactionCodeElement.value;
            var miscellaneous = transactionCode !== "S1" && transactionCode !== "S3";

            if (
              miscellaneous &&
              index === stockOutModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              stockOutModel["model_no"] &&
              stockOutModel["brand_code"] &&
              stockOutModel["qty"]
            ) {
              updateQuantity(index, event.target.value);
              addStockOutModel();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#stock-out-form").reset();

            for (var i = 0; i < stockOutModels.length; i++) {
              var stockOutModel = stockOutModels[i];
              var brandCode = stockOutModel["brand_code"];
              var modelNo = stockOutModel["model_no"];
              var price = stockOutModel["price"];

              updateModel(i, getModels(modelNo, brandCode)[0]);
              updatePrice(i, price);
            }

            render();

            onTransactionCodeChange();
          });
        </script>
      <?php else : ?>
        <div id="stock-out-entry-not-found">Stock in voucher not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
