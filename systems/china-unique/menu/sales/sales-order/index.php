<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (assigned($soNo)): ?>
        <form id="so-form" method="post">
          <table id="so-header">
            <tr>
              <td>Order No.:</td>
              <td><input type="text" id="so-no" name="so_no" placeholder="Sales Order No." value="<?php echo $soNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" id="so-date" name="so_date" placeholder="Sales Date" value="<?php echo $soDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <select id="debtor-code" name="debtor_code" required>
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
                <input id="exchange-rate" name="exchange_rate" type="number" step="0.00000001" min="0.00000001" placeholder="Exchange Rate" value="<?php echo $exchangeRate; ?>" <?php echo $currencyCode === COMPANY_CURRENCY ? "readonly" : ""; ?> onchange="onExchangeRateChange()" required />
              </td>
            </tr>
            <tr>
              <td>Discount:</td>
              <td>
                <input id="discount" name="discount" type="number" step="0.01" min="0" max="100" placeholder="Discount" value="<?php echo $discount; ?>" onchange="onDiscountChange()" required /><span>%</span>
                <input id="tax" name="tax" type="number" value="<?php echo $tax; ?>" hidden required />
              </td>
              <td colspan="2">
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
                <th><span class="number">Quantity</span></th>
                <th><span class="number">Selling Price</span></th>
                <th><span class="number">Sub Total</span></th>
                <th></th>
              </tr>
            </thead>
            <tfoot>
              <tr class="discount-row">
                <td colspan="3"></td>
                <th></th>
                <th><span id="sub-total-amount" class="number"></span></th>
                <td></td>
              </tr>
              <tr class="discount-row">
                <td colspan="3"></td>
                <td><span id="discount-percentage" class="number"></span></td>
                <td><span id="discount-amount" class="number"></span></td>
                <td></td>
              </tr>
                <th></th>
                <th><span class="number">Total:</span></th>
                <th><span id="total-qty" class="number"></span></th>
                <th></th>
                <th><span id="total-amount" class="number"></span></th>
                <th></th>
              </tr>
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <table id="so-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status == "" || $status == "SAVED"): ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo assigned($status) ? $status : "DRAFT"; ?>" formaction="<?php echo SALES_ORDER_PRINTOUT_URL; ?>">Print</button>
          <?php if ($status == "SAVED"): ?>
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
          var soModels = <?php echo json_encode($soModels); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var brands = <?php echo json_encode($brands); ?>;
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

            var discount = discountElement.value;
            var totolQty = 0;
            var totolAmount = 0;

            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var matchedModels = getModels(soModel["model_no"]);
              var newRowElement = document.createElement("tr");

              var rowInnerHTML =
                  "<tr>"
                + "<td><input class=\"model-no\" type=\"text\" name=\"model_no[]\" list=\"model-list\" value=\"" + soModel["model_no"] + "\" oninput=\"onModelNoChange(event, " + i + ")\" onchange=\"onModelNoChange(event, " + i + ", true)\" onfocus=\"onFieldFocused(" + i + ", 'model_no[]')\" onblur=\"onFieldBlurred()\" autocomplete=\"on\" required /></td>"
                + "<td><select class=\"brand-code\" name=\"brand_code[]\" value=\"" + soModel["brand_code"] + "\" onchange=\"onBrandCodeChange(event, " + i + ")\" onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" onblur=\"onFieldBlurred()\" required>";

              for (var j = 0; j < brands.length; j++) {
                var code = brands[j]["code"];
                var disabled = matchedModels.map(function (model) { return model["brand_code"]; }).indexOf(code) === -1 ? " disabled hidden" : "";
                rowInnerHTML += "<option value=\"" + code + "\"" + disabled + ">" + code + "</option>";
              }

              rowInnerHTML +=
                  "</select>"
                + "</td>"
                + "<td><input class=\"qty number\" type=\"number\" min=\"0\" name=\"qty[]\" value=\"" + soModel["qty"] + "\" onchange=\"onQuantityChange(event, " + i + ")\" onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" onblur=\"onFieldBlurred()\" required /></td>"
                + "<td><input class=\"price number\" type=\"number\" step=\"0.01\" min=\"0\" name=\"price[]\" value=\"" + soModel["price"].toFixed(2) + "\" onchange=\"onPriceChange(event, " + i + ")\" onfocus=\"onFieldFocused(" + i + ", 'price[]')\" onblur=\"onFieldBlurred()\" required /></td>"
                + "<td><span class=\"total-amount number\">" + soModel["total_amount"].toFixed(2) + "</span></td>"
                + "<td><div class=\"remove\" onclick=\"removeSalesModel(" + i + ")\">×</div></td>"
                + "</tr>";

              newRowElement.innerHTML = rowInnerHTML;

              totolQty += parseFloat(soModel["qty"]);
              totolAmount += parseFloat(soModel["price"] * soModel["qty"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (soModels.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\"><span id=\"so-entry-no-model\">No models</span></td>";
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

            render();
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
            render();
          }

          function removeSalesModel(index) {
            soModels.splice(index, 1);
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

          function onPriceStandardChange() {
            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var matchedModel = getModels(soModel["model_no"], soModel["brand_code"])[0];

              if (matchedModel) {
                updateModel(i, matchedModel);
              }
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

            updateAllProfits();
          }

          function onExchangeRateChange() {
            updateAllProfits();
          }

          function onDiscountChange() {
            updateAllProfits();
          }

          function onPriceStandardChange() {
            for (var i = 0; i < soModels.length; i++) {
              var soModel = soModels[i];
              var matchedModel = getModels(soModel["model_no"], soModel["brand_code"])[0];

              updateModel(i, matchedModel);
            }

            render();
          }

          function onModelNoChange(event, index, force = false) {
            var matchedModel = getModels(event.target.value)[0];

            if (matchedModel || force) {
              updateModel(index, matchedModel);
              render();
            }
          }

          function onBrandCodeChange(event, index) {
            var modelNo = soModels[i]["model_no"];
            var brandCode = event.target.value;
            var matchedModel = modelNo && brandCode && getModels(modelNo).filter(function (model) { return model["brand_code"] === brandCode; })[0] || undefined;

            updateModel(index, matchedModel);
            render();
          }

          function onPriceChange(event, index) {
            updatePrice(index, event.target.value);
            render();
          }

          function onQuantityChange(event, index) {
            updateQuantity(index, event.target.value);
            render();
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

            render();
          }
        </script>
      <?php else: ?>
        <div id="so-entry-not-found">Sales order not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
