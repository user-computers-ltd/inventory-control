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
      <?php if (assigned($stockInNo)) : ?>
        <form id="stock-in-form" method="post">
          <table id="stock-in-header">
            <tr>
              <td>Voucher No.:</td>
              <td><input type="text" name="stock_in_no" value="<?php echo $stockInNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="stock_in_date" value="<?php echo $stockInDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
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
                <select id="warehouse" name="warehouse_code" required>
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
              <td class="r7-cell">Client:</td>
              <td class="r7-cell">
                <select id="creditor-code" class="option-field" name="creditor_code" required <?php echo $useDebtor ? "disabled hidden" : ""; ?>>
                  <?php
                    foreach ($creditors as $creditor) {
                      $code = $creditor["code"];
                      $label = $creditor["code"] . " - " . $creditor["name"];
                      $selected = $creditorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$label</option>";
                    }
                  ?>
                </select>
                <select id="debtor-code" class="option-field" name="creditor_code" required <?php echo $useDebtor ? "" : "disabled hidden"; ?>>
                  <?php
                    foreach ($debtors as $debtor) {
                      $code = $debtor["code"];
                      $label = $debtor["code"] . " - " . $debtor["name"];
                      $selected = $creditorCode == $code ? "selected" : "";
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
          <button type="button" onclick="addStockInModel()">Add</button>
          <table id="stock-in-models">
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
              </tr>
              <tr>
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
          <table id="stock-in-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status == "DRAFT" || $status == "SAVED") : ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo STOCK_IN_PRINTOUT_URL; ?>">Print</button>
          <?php if ($status == "SAVED") : ?>
            <button name="status" type="submit" value="POSTED">Post</button>
            <button name="status" type="submit" value="DELETED">Delete</button>
          <?php endif ?>
        </form>
        <datalist id="model-list">
          <?php
            foreach ($models as $model) {
              echo "<option value=\"" . $model["model_no"]
               . "\" data-model_no=\"" . $model["model_no"]
               . "\" data-brand_code=\"" . $model["brand_code"]
               . "\" data-normal_price=\"" . $model["normal_price"]
               . "\" data-special_price=\"" . $model["special_price"]
               . "\">" . $model["model_no"] . "</option>";
            }
          ?>
        </datalist>
        <script>
          var stockInModels = <?php echo json_encode($stockInModels); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var brands = <?php echo json_encode($brands); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var transactionCodeElement = document.querySelector("#transaction-code");
          var netAmountElement = document.querySelector("#net-amount");
          var discountElement = document.querySelector("#discount");
          var taxElement = document.querySelector("#tax");
          var creditorCodeElement = document.querySelector("#creditor-code");
          var debtorCodeElement = document.querySelector("#debtor-code");
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");
          var tableBodyElement = document.querySelector("#stock-in-models tbody");
          var discountRowElements = document.querySelectorAll(".discount-row");
          var subTotalAmountElement = document.querySelector("#sub-total-amount");
          var discountPercentageElement = document.querySelector("#discount-percentage");
          var discountAmountElement = document.querySelector("#discount-amount");
          var totalQtyElement = document.querySelector("#total-qty");
          var totalAmountElement = document.querySelector("#total-amount");
          var varianceElement = document.querySelector("#variance");
          var modelListElement = document.querySelector("#model-list");

          function getModels(modelNo, brandCode) {
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
            var miscellaneous = transactionCode !== "R1" && transactionCode !== "R3";
            var netAmount = netAmountElement.value;
            var discount = discountElement.value;
            var totalQty = 0;
            var totalAmount = 0;

            for (var i = 0; i < stockInModels.length; i++) {
              var stockInModel = stockInModels[i];
              var matchedModels = getModels(stockInModel["model_no"]);
              var newRowElement = document.createElement("tr");

              var rowInnerHTML =
                  "<tr>"
                + "<td>"
                  + "<input "
                    + "class=\"model-no\" "
                    + "type=\"text\" "
                    + "name=\"model_no[]\" "
                    + "list=\"model-list\" "
                    + "value=\"" + stockInModel["model_no"] + "\" "
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
                    + "value=\"" + stockInModel["brand_code"] + "\" "
                    + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required"
                  + ">";

              for (var j = 0; j < brands.length; j++) {
                var code = brands[j]["code"];
                var selected = stockInModel["brand_code"] === code ? " selected" : "";
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
                    + "name=\"qty[]\" "
                    + "value=\"" + stockInModel["qty"] + "\" "
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
                    + "value=\"" + stockInModel["price"].toFixed(6) + "\" "
                    + "onchange=\"onPriceChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'price[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onPriceKeyDown(event, " + i + ")\" "
                    + "required "
                    + (miscellaneous ? "disabled" : "")
                  + "/>"
                + "</td>"
                + "<td class=\"total-amount number\">" + stockInModel["total_amount"].toFixed(2) + "</td>"
                + "<td><div class=\"remove\" onclick=\"removeStockInModel(" + i + ")\">×</div></td>"
                + "</tr>";

              newRowElement.innerHTML = rowInnerHTML;

              totalQty += parseFloat(stockInModel["qty"]);
              totalAmount += parseFloat(stockInModel["price"] * stockInModel["qty"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (stockInModels.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\" id=\"stock-in-entry-no-model\">No models</td>";
              tableBodyElement.appendChild(rowElement);
            }

            for (var k = 0; k < discountRowElements.length; k++) {
              toggleClass(discountRowElements[k], "show", stockInModels.length > 0 && discount > 0);
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

            var stockInModel = stockInModels[index];

            stockInModel["model_no"] = model["model_no"] || "";
            stockInModel["brand_code"] = model["brand_code"] || "";
            stockInModel["cost_average"] = parseFloat(model["cost_average"]) || 0;
            stockInModel["normal_price"] = parseFloat(model["normal_price"]) || 0;
            stockInModel["special_price"] = parseFloat(model["special_price"]) || 0;
            stockInModel["price"] = parseFloat(model[priceStandard]) || 0;
            stockInModel["qty"] = stockInModel["qty"] || 0;
            stockInModel["total_amount"] = (stockInModel["qty"] || 0) * stockInModel["price"];
            stockInModel["qty_on_hand"] = parseFloat(model["qty_on_hand"]) || 0;
            stockInModel["qty_on_order"] = parseFloat(model["qty_on_order"]) || 0;
          }

          function updateQuantity (index, qty = 0) {
            var stockInModel = stockInModels[index];

            stockInModel["qty"] = Math.max(0, parseFloat(qty));

            if (stockInModel["price"]) {
              stockInModel["total_amount"] = stockInModel["price"] * stockInModel["qty"];
            }
          }

          function updatePrice(index, price = 0) {
            var stockInModel = stockInModels[index];

            stockInModel["price"] = Math.max(0, parseFloat(price));

            if (stockInModel["qty"]) {
              stockInModel["total_amount"] = stockInModel["price"] * stockInModel["qty"];
            }
          }

          function addStockInModel() {
            stockInModels.push({});

            updateModel(stockInModels.length - 1);
            render();
          }

          function removeStockInModel(index) {
            stockInModels.splice(index, 1);
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
            var r7Cells = document.querySelectorAll(".r7-cell");
            var optionFields = document.querySelectorAll(".option-field");
            var optionColumns = document.querySelectorAll(".option-column");
            var transactionCode = transactionCodeElement.value;
            var miscellaneous = transactionCode !== "R1" && transactionCode !== "R3";

            for (var i = 0; i < miscCells.length; i++) {
              toggleClass(miscCells[i], "hide", miscellaneous);
            }

            for (var i = 0; i < r7Cells.length; i++) {
              toggleClass(r7Cells[i], "hide", miscellaneous && transactionCode !== "R7");
            }

            for (var i = 0; i < optionFields.length; i++) {
              optionFields[i].disabled = miscellaneous;
            }

            for (var i = 0; i < optionColumns.length; i++) {
              toggleClass(optionColumns[i], "hide", miscellaneous);
            }

            creditorCodeElement.disabled = transactionCode === "R3";
            creditorCodeElement.hidden = transactionCode === "R3";
            debtorCodeElement.disabled = transactionCode === "R1" || transactionCode === "R7";
            debtorCodeElement.hidden = transactionCode === "R1" || transactionCode === "R7";
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
            for (var i = 0; i < stockInModels.length; i++) {
              var stockInModel = stockInModels[i];
              var matchedModel = getModels(stockInModel["model_no"], stockInModel["brand_code"])[0];

              updateModel(i, matchedModel);
            }

            render();
          }

          function onModelNoChange(event, index) {
            var newModelNo = event.target.value;
            var matchedModel = getModels(newModelNo)[0];
            var stockInModel = stockInModels[index];

            if (stockInModel["model_no"] !== newModelNo) {
              var existsAlready = stockInModels.filter(function (m) {
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
            var modelNo = stockInModels[index]["model_no"];
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
            var stockInModel = stockInModels[index];

            if (
              index === stockInModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              stockInModel["model_no"] &&
              stockInModel["brand_code"] &&
              stockInModel["qty"] &&
              stockInModel["price"]
            ) {
              updatePrice(index, event.target.value);
              addStockInModel();
            }
          }

          function onQuantityChange(event, index) {
            updateQuantity(index, event.target.value);
            render();
          }

          function onQuantityKeyDown(event, index) {
            var stockInModel = stockInModels[index];
            var transactionCode = transactionCodeElement.value;
            var miscellaneous = transactionCode !== "R1" && transactionCode !== "R3";

            if (
              miscellaneous &&
              index === stockInModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              stockInModel["model_no"] &&
              stockInModel["brand_code"] &&
              stockInModel["qty"]
            ) {
              updateQuantity(index, event.target.value);
              addStockInModel();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#stock-in-form").reset();

            for (var i = 0; i < stockInModels.length; i++) {
              var stockInModel = stockInModels[i];
              var brandCode = stockInModel["brand_code"];
              var modelNo = stockInModel["model_no"];
              var price = stockInModel["price"];

              updateModel(i, getModels(modelNo, brandCode)[0]);
              updatePrice(i, price);
            }

            render();

            onTransactionCodeChange();
          });
        </script>
      <?php else : ?>
        <div id="stock-in-entry-not-found">Stock in voucher not found</div>
      <?php endif ?>
    </div>
  </body>
</html>