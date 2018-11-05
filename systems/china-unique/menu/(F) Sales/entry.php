<?php
  define("SYSTEM_PATH", "../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "entry_process.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="entry.css">
  </head>
  <body>
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo getURLParentLocation() . " Entry"; ?></div>
      <?php if (assigned($so_no)): ?>
        <form id="so-form">
          <table id="so-header">
            <tr>
              <td>Order No.:</td>
              <td><input type="text" id="so-no" name="so_no" placeholder="Sales Order No." value="<?php echo $so_no; ?>" readonly required /></td>
              <td>Date:</td>
              <td><input type="date" id="so-date" name="so_date" placeholder="Sales Date" value="<?php echo $so_date; ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <select id="debtor-code" name="debtor_code" required>
                  <?php
                    foreach ($debtors as $debtor) {
                      $code = $debtor["code"];
                      $label = $debtor["code"] . " - " . $debtor["name"];
                      $selected = $debtor_code == $code ? "selected" : "";
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
                      $selected = $currency_code == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code</option>";
                    }
                  ?>
                </select>
                <input id="exchange-rate" name="exchange_rate" type="number" step="0.00000001" min="0.00000001" placeholder="Exchange Rate" value="<?php echo $exchange_rate; ?>" <?php echo $currency_code === COMPANY_CURRENCY ? "readonly" : ""; ?> onchange="onExchangeRateChange()" required />
              </td>
            </tr>
            <tr>
              <td>Discount:</td>
              <td><input id="discount" name="discount" type="number" step="1" min="0" max="100" placeholder="Discount" value="<?php echo $discount; ?>" onchange="onDiscountChange()" required /></td>
              <td>Tax:</td>
              <td><input id="tax" name="tax" type="number" step="1" placeholder="Tax" min="0" max="100" value="<?php echo $tax; ?>" onchange="onTaxChange()" required /></td>
            </tr>
          </table>
          <table id="so-sub-header">
            <tr>
              <td>
                <input id="normal-price" name="price-standard" type="radio" value="normal_price" <?php echo $priceStandard == "normal_price" ? "checked" : ""; ?> onchange="onPriceStandardChange()" />
                <label for="normal-price">Normal Price</label>
                <input id="special-price" name="price-standard" type="radio" value="special_price" <?php echo $priceStandard == "special_price" ? "checked" : ""; ?> onchange="onPriceStandardChange()" />
                <label for="special-price">Special Price</label>
              </td>
            </tr>
          </table>
          <button type="button" onclick="addSalesModel()">Add</button>
          <table id="so-models">
            <colgroup>
              <col style="width: 120px">
              <col>
              <col style="width: 60px">
              <col style="width: 100px">
              <col>
              <col>
              <col>
              <col>
              <col>
              <col style="width: 50px">
            </colgroup>
            <thead>
              <tr>
                <th>Model no.</th>
                <th>Brand code</th>
                <th><span class="number">Quantity</span></th>
                <th><span class="number">Selling Price</span></th>
                <th><span class="number">Sub Total</span></th>
                <th><span class="number">Unit Price</span></th>
                <th><span class="number">Profit</span></th>
                <th><span class="number">On Hand</span></th>
                <th><span class="number">On Order</span></th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr class="discount-row">
                <td colspan="3"></td>
                <th></th>
                <th><span id="sub-total-amount" class="number"></span></th>
                <td colspan="5"></td>
              </tr>
              <tr class="discount-row">
                <td colspan="3"></td>
                <td><span id="discount-percentage" class="number"></span></td>
                <td><span id="discount-amount" class="number"></span></td>
                <td colspan="5"></td>
              </tr>
                <th></th>
                <th><span class="number">Total:</span></th>
                <th><span id="total-qty" class="number"></span></th>
                <th></th>
                <th><span id="total-amount" class="number"></span></th>
                <th colspan="5"></th>
              </tr>
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <table id="so-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks" placeholder="Remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <button name="status" type="submit" value="SAVED">Save</button>
          <?php if (assigned($status)) : ?>
            <button name="status" type="submit" value="POSTED">Post</button>
            <button name="status" type="submit" value="DELETED">Delete</button>
          <?php endif ?>
          <button type="button" onclick="printout()">Print</button>
        </form>
        <table id="so-model-sample">
          <tr>
            <td><input class="model-no" type="text" name="model_no[]" list="model-list" oninput="onModelNoChange(event)" onchange="onModelNoChange(event, true)" onfocus="onFieldFocused(event)" onblur="onFieldBlurred(event)" autocomplete="on" required /></td>
            <td>
              <select class="brand-code" name="brand_code[]" onchange="onBrandCodeChange(event)" onfocus="onFieldFocused(event)" onblur="onFieldBlurred(event)" required>
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    echo "<option value=\"$code\">$code</option>";
                  }
                ?>
              </select>
            </td>
            <td><input class="qty number" type="number" min="0" name="qty[]" onchange="onQuantityChange(event)" onfocus="onFieldFocused(event)" onblur="onFieldBlurred(event)" required /></td>
            <td><input class="price number" type="number" step="0.01" min="0" name="price[]" onchange="onPriceChange(event)" onfocus="onFieldFocused(event)" onblur="onFieldBlurred(event)" required /></td>
            <td><span class="total-amount number"></span></td>
            <td><span class="cost-average number"></span></td>
            <td><span class="profit number"></span></td>
            <td><span class="qty-on-hand number"></span></td>
            <td><span class="qty-on-order number"></span></td>
            <td><div class="remove" onclick="removeSalesModel(event)">×</div></td>
          </tr>
        </table>
        <datalist id="model-list">
          <?php
            foreach ($models as $model) {
              echo "<option value=\"" . $model["model_no"]
               . "\" data-model_no=\"" . $model["model_no"]
               . "\" data-brand_code=\"" . $model["brand_code"]
               . "\" data-normal_price=\"" . $model["normal_price"]
               . "\" data-special_price=\"" . $model["special_price"]
               . "\" data-cost_average=\"" . $model["cost_average"]
               . "\" data-qty_on_hand=\"" . $model["qty_on_hand"]
               . "\" data-qty_on_order=\"" . $model["qty_on_order"]
               . "\">" . $model["model_no"] . "</option>";
            }
          ?>
        </datalist>
        <script>
          var soModels = <?php echo json_encode($so_models); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var formElement = document.querySelector("#so-form");
          var discountElement = document.querySelector("#discount");
          var taxElement = document.querySelector("#tax");
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");
          var tableBodyElement = document.querySelector("#so-models tbody");
          var discountRowElements = document.querySelectorAll(".discount-row");
          var subTotolAmountElement = document.querySelector("#sub-total-amount");
          var discountPercentageElement = document.querySelector("#discount-percentage");
          var discountAmountElement = document.querySelector("#discount-amount");
          var totolQtyElement = document.querySelector("#total-qty");
          var totolAmountElement = document.querySelector("#total-amount");
          var sampleRowElement = document.querySelector("#so-model-sample tr");
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

          function drawTable() {
            var focusedElement = null;

            tableBodyElement.innerHTML = "";

            var discount = discountElement.value;
            var totolQty = 0;
            var totolAmount = 0;

            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var matchedModels = getModels(soModel["model_no"]);
              var rowElement = sampleRowElement.cloneNode(true);
              var brandOptions = rowElement.querySelectorAll(".brand-code option");

              for (var j = 0; j < brandOptions.length; j++) {
                var brand = brandOptions[j];
                var unmatched = matchedModels.map(function (m) { return m["brand_code"]; }).indexOf(brand.value) === -1;

                brandOptions[j].disabled = unmatched;
                brandOptions[j].hidden = unmatched;
              }

              rowElement.dataset.index = i;
              rowElement.querySelector(".model-no").value = soModel["model_no"];
              rowElement.querySelector(".brand-code").value = soModel["brand_code"];
              rowElement.querySelector(".cost-average").innerHTML = soModel["cost_average"].toFixed(2);
              rowElement.querySelector(".price").value = soModel["price"].toFixed(2);
              rowElement.querySelector(".profit").innerHTML = soModel["profit"].toFixed(2) + "%";
              rowElement.querySelector(".qty").value = soModel["qty"];
              rowElement.querySelector(".total-amount").innerHTML = soModel["total_amount"].toFixed(2);
              rowElement.querySelector(".qty-on-hand").innerHTML = soModel["qty_on_hand"];
              rowElement.querySelector(".qty-on-order").innerHTML = soModel["qty_on_order"];

              if (i === focusedRow) {
                focusedElement = rowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }

              totolQty += parseFloat(soModel["qty"]);
              totolAmount += parseFloat(soModel["price"] * soModel["qty"]);

              tableBodyElement.appendChild(rowElement);
            }

            if (soModels.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\"><span id=\"so-entry-no-model\">No Models</span></td>";
              tableBodyElement.appendChild(rowElement);
            }

            for (var k = 0; k < discountRowElements.length; k++) {
              toggleClass(discountRowElements[k], "show", soModels.length > 0 && discount > 0);
            }

            subTotolAmountElement.innerHTML = totolAmount.toFixed(2);

            discountPercentageElement.innerHTML = "Discount " + discount + "%";
            discountAmountElement.innerHTML = (totolAmount * (discount) / 100).toFixed(2);

            totolQtyElement.innerHTML = totolQty;
            totolAmountElement.innerHTML = (totolAmount * (100 - discount) / 100).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateModel(index, model = {}) {
            var priceStandard = document.querySelector("input[name='price-standard']:checked").value;
            var tax = taxElement.value;
            var rate = exchangeRateElement.value;
            var profit = 0;

            if (model["cost_average"]) {
              var avgCostIncTax = model["cost_average"] * (1 + tax / 100);
              var price = model[priceStandard];

              profit = (price * rate - avgCostIncTax) / avgCostIncTax * 100;
            }

            var soModel = soModels[index];

            soModel["model_no"] = model["model_no"] || "";
            soModel["brand_code"] = model["brand_code"] || "";
            soModel["cost_average"] = parseFloat(model["cost_average"]) || 0;
            soModel["normal_price"] = parseFloat(model["normal_price"]) || 0;
            soModel["special_price"] = parseFloat(model["special_price"]) || 0;
            soModel["price"] = parseFloat(model[priceStandard]) || 0;
            soModel["profit"] = profit;
            soModel["qty"] = soModel["qty"] || 0;
            soModel["total_amount"] = (soModel["qty"] || 0) * soModel["price"];
            soModel["qty_on_hand"] = parseFloat(model["qty_on_hand"]) || 0;
            soModel["qty_on_order"] = parseFloat(model["qty_on_order"]) || 0;
          }

          function updatePrice(index, price = 0) {
            var soModel = soModels[index];
            var tax = taxElement.value;
            var rate = exchangeRateElement.value;
            var profit = 0;

            if (soModel["cost_average"]) {
              var avgCostIncTax = soModel["cost_average"] * (1 + tax / 100);

              profit = (price * rate - avgCostIncTax) / avgCostIncTax * 100;
            }

            soModel["price"] = parseFloat(price);
            soModel["profit"] = profit;

            if (soModel["qty"]) {
              soModel["total_amount"] = soModel["price"] * soModel["qty"];
            }
          }

          function updateAllProfits() {
            for (var i = 0; i < soModels.length; i++) {
              updatePrice(i, soModels[i]["price"]);
            }

            drawTable();
          }

          function updateQuantity (index, quantity = 0) {
            var soModel = soModels[index];

            soModel["qty"] = parseFloat(quantity);

            if (soModel["price"]) {
              soModel["total_amount"] = soModel["price"] * soModel["qty"];
            }
          }

          function addSalesModel() {
            soModels.push({});

            updateModel(soModels.length - 1);
            drawTable();
          }

          function removeSalesModel(event) {
            var rowElement = event.target.parentElement.parentElement;
            var rowIndex = parseInt(rowElement.dataset.index, 10);

            soModels.splice(rowIndex, 1);
            drawTable();
          }

          function onFieldFocused(event) {
            var focusedElement = event.target;
            var rowElement = focusedElement.parentElement.parentElement;

            focusedRow = parseInt(rowElement.dataset.index, 10);
            focusedFieldName = focusedElement.name;
          }

          function onFieldBlurred(event) {
            focusedRow = null;
            focusedFieldName = null;
          }

          function onPriceStandardChange() {
            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var matchedModel = getModels(soModel["model_no"], soModel["brand_code"])[0];

              if (matchedModel) {
                updateModel(i, matchedModel);
              }
            }

            drawTable();
          }

          function onCurrencyCodeChange() {
            var currencyCode = currencyCodeElement.value;

            exchangeRateElement.value = currencies[currencyCode];
            if (currencyCode === "<?php echo COMPANY_CURRENCY; ?>") {
              exchangeRateElement.setAttribute("readonly", true);
            } else {
              exchangeRateElement.removeAttribute("readonly");
            }

            updateAllProfits();
          }

          function onExchangeRateChange() {
            updateAllProfits();
          }

          function onDiscountChange() {
            updateAllProfits();
          }

          function onTaxChange() {
            updateAllProfits();
          }

          function onPriceStandardChange() {
            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var matchedModel = getModels(soModel["model_no"], soModel["brand_code"])[0];

              updateModel(i, matchedModel);
            }

            drawTable();
          }

          function onModelNoChange(event, force = false) {
            var modelNoElement = event.target;
            var rowElement = modelNoElement.parentElement.parentElement;
            var index = parseInt(rowElement.dataset.index, 10);
            var matchedModel = getModels(modelNoElement.value)[0];

            if (matchedModel || force) {
              updateModel(index, matchedModel);
              drawTable();
            }
          }

          function onBrandCodeChange(event) {
            var brandCodeElement = event.target;
            var rowElement = brandCodeElement.parentElement.parentElement;
            var index = parseInt(rowElement.dataset.index, 10);
            var modelNo = rowElement.querySelector(".model-no").value;
            var brandCode = brandCodeElement.value;
            var matchedModel = modelNo && brandCode && getModels(modelNo).filter(function (m) { return m["brand_code"] === brandCode; })[0] || undefined;

            updateModel(index, matchedModel);
            drawTable();
          }

          function onPriceChange(event) {
            var priceElement = event.target;
            var rowElement = priceElement.parentElement.parentElement;
            var index = parseInt(rowElement.dataset.index, 10);
            var price = priceElement.value;

            updatePrice(index, price);
            drawTable();
          }

          function onQuantityChange(event) {
            var qtyElement = event.target;
            var rowElement = qtyElement.parentElement.parentElement;
            var index = parseInt(rowElement.dataset.index, 10);
            var qty = qtyElement.value;

            updateQuantity(index, qty);
            drawTable();
          }

          function printout() {
            window.open("printout.php?" + serialize(formElement));
          }

          window.onload = function () {
            document.querySelector("#so-form").reset();

            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var brandCode = soModel["brand_code"];
              var modelNo = soModel["model_no"];
              var price = soModel["price"];

              updateModel(i, getModels(modelNo, brandCode)[0]);
              updatePrice(i, price);
            }

            drawTable();
          }
        </script>
      <?php else: ?>
        <div id="so-entry-not-found">Sales Order Not Found</div>
      <?php endif ?>
    </div>
  </body>
</html>
