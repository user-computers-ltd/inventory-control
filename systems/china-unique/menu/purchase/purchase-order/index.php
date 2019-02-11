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
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (assigned($poNo)) : ?>
        <form id="po-form" method="post">
          <table id="po-header">
            <tr>
              <td>Order No.:</td>
              <td><input type="text" name="po_no" value="<?php echo $poNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="po_date" value="<?php echo $poDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Supplier:</td>
              <td>
                <select id="creditor-code" name="creditor_code" required>
                  <?php
                    foreach ($creditors as $creditor) {
                      $code = $creditor["code"];
                      $label = $creditor["code"] . " - " . $creditor["name"];
                      $selected = $creditorCode == $code ? "selected" : "";
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
              <td>Discount:</td>
              <td>
                <input
                  id="discount"
                  name="discount"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value="<?php echo $discount; ?>"
                  onchange="onDiscountChange()"
                  required
                /><span>%</span>
                <input id="tax" name="tax" type="hidden" value="<?php echo $tax; ?>" required />
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <input
                  id="primary-cost"
                  name="price_standard"
                  type="radio"
                  value="primary_cost"
                  onchange="onPriceStandardChange()"
                  checked
                />
                <label for="primary-cost">Primary Cost</label>
                <input
                  id="secondary-cost"
                  name="price_standard"
                  type="radio"
                  value="secondary_cost"
                  onchange="onPriceStandardChange()"
                />
                <label for="secondary-cost">Secondary Cost</label>
              </td>
            </tr>
          </table>
          <button type="button" onclick="addItem()">Add</button>
          <table id="po-models">
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
                <th class="number">Price</th>
                <th class="number">Subtotal</th>
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
                <td colspan="2"></td>
                <td class="number">Discount:</td>
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
            </tfoot>
            <tbody>
            </tbody>
          </table>
          <table id="po-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status == "DRAFT" || $status == "SAVED") : ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo PURCHASE_ORDER_PRINTOUT_URL; ?>">Print</button>
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
               . "\" data-primary_cost=\"" . $model["primary_cost"]
               . "\" data-secondary_cost=\"" . $model["secondary_cost"]
               . "\">" . $model["model_no"] . "</option>";
            }
          ?>
        </datalist>
        <script>
          var poModels = <?php echo json_encode($poModels); ?>;
          var currencies = <?php echo json_encode($currencies); ?>;
          var brands = <?php echo json_encode($brands); ?>;
          var focusedRow = null;
          var focusedFieldName = null;

          var discountElement = document.querySelector("#discount");
          var taxElement = document.querySelector("#tax");
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");
          var tableBodyElement = document.querySelector("#po-models tbody");
          var discountRowElements = document.querySelectorAll(".discount-row");
          var subTotalAmountElement = document.querySelector("#sub-total-amount");
          var discountPercentageElement = document.querySelector("#discount-percentage");
          var discountAmountElement = document.querySelector("#discount-amount");
          var totalQtyElement = document.querySelector("#total-qty");
          var totalAmountElement = document.querySelector("#total-amount");
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
            var totalQty = 0;
            var totalAmount = 0;

            var existingPrices = {};

            for (var i = 0; i < poModels.length; i++) {
              var poModel = poModels[i];
              var matchedModels = getModels(poModel["model_no"]);
              var newRowElement = document.createElement("tr");
              var ongoingDelivery = poModel["qty_delivered"] && poModel["qty_delivered"] > 0;
              var modelPrice = existingPrices[poModel["brand_code"] + " - " + poModel["model_no"]];

              if (modelPrice === undefined) {
                existingPrices[poModel["brand_code"] + " - " + poModel["model_no"]] = poModel["price"];
              }

              var rowInnerHTML =
                  "<tr>"
                + "<td>"
                  + "<input "
                    + "class=\"model-no\" "
                    + "type=\"text\" "
                    + "name=\"model_no[]\" "
                    + "list=\"model-list\" "
                    + "value=\"" + poModel["model_no"] + "\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'model_no[]')\" "
                    + "onblur=\"onModelNoChange(event, " + i + ")\" "
                    + "autocomplete=\"on\" "
                    + "required "
                    + (ongoingDelivery ? "readonly" : "")
                  + "/>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"brand-code\" "
                    + "name=\"brand_code[]\" "
                    + "value=\"" + poModel["brand_code"] + "\" "
                    + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                    + (ongoingDelivery ? "readonly" : "")
                  + ">";

              for (var j = 0; j < brands.length; j++) {
                var code = brands[j]["code"];
                var selected = poModel["brand_code"] === code ? " selected" : "";
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
                    + "min=\"" + poModel["qty_delivered"] + "\" "
                    + "name=\"qty[]\" "
                    + "value=\"" + poModel["qty"] + "\" "
                    + "onchange=\"onQuantityChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onQuantityKeyDown(event, " + i + ")\" "
                    + "required "
                    + (modelPrice !== undefined ? "data-duplicate=\"true\"" : "")
                  + "/>"
                + "</td>"
                + "<td class=\"number\">"
                  + "<input "
                    + "class=\"price number\" "
                    + "type=\"number\" "
                    + "step=\"0.01\" "
                    + "min=\"0\" "
                    + "name=\"price[]\" "
                    + "value=\"" + (modelPrice !== undefined ? modelPrice : poModel["price"]).toFixed(2) + "\" "
                    + "onchange=\"onPriceChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'price[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onPriceKeyDown(event, " + i + ")\" "
                    + "required "
                    + (modelPrice !== undefined ? "readonly" : "")
                  + "/>"
                + "</td>"
                + "<td class=\"total-amount number\">" + poModel["total_amount"].toFixed(2) + "</td>"
                + "<td><div class=\"remove\" onclick=\"removeSalesModel(" + i + ")\">×</div></td>"
                + "</tr>";

              newRowElement.innerHTML = rowInnerHTML;

              totalQty += parseFloat(poModel["qty"]);
              totalAmount += parseFloat(poModel["price"] * poModel["qty"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (poModels.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\" id=\"po-entry-no-model\">No models</td>";
              tableBodyElement.appendChild(rowElement);
            }

            for (var k = 0; k < discountRowElements.length; k++) {
              toggleClass(discountRowElements[k], "show", poModels.length > 0 && discount > 0);
            }

            subTotalAmountElement.innerHTML = totalAmount.toFixed(2);

            discountPercentageElement.innerHTML = discount + "%";
            discountAmountElement.innerHTML = (totalAmount * (discount) / 100).toFixed(2);

            totalQtyElement.innerHTML = totalQty;
            totalAmountElement.innerHTML = (totalAmount * (100 - discount) / 100).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateModel(index, model = {}) {
            var priceStandard = document.querySelector("input[name='price_standard']:checked").value;

            var poModel = poModels[index];

            poModel["model_no"] = model["model_no"] || "";
            poModel["brand_code"] = model["brand_code"] || "";
            poModel["cost_average"] = parseFloat(model["cost_average"]) || 0;
            poModel["primary_cost"] = parseFloat(model["primary_cost"]) || 0;
            poModel["secondary_cost"] = parseFloat(model["secondary_cost"]) || 0;
            poModel["price"] = parseFloat(model[priceStandard]) || 0;
            poModel["qty"] = poModel["qty"] || 0;
            poModel["total_amount"] = (poModel["qty"] || 0) * poModel["price"];
            poModel["qty_on_hand"] = parseFloat(model["qty_on_hand"]) || 0;
            poModel["qty_on_order"] = parseFloat(model["qty_on_order"]) || 0;
          }

          function updateQuantity (index, qty = 0) {
            var poModel = poModels[index];

            poModel["qty"] = Math.max(0, parseFloat(qty));

            if (poModel["price"]) {
              poModel["total_amount"] = poModel["price"] * poModel["qty"];
            }
          }

          function updatePrice(index, price = 0) {
            var poModel = poModels[index];

            poModel["price"] = Math.max(0, parseFloat(price));

            if (poModel["qty"]) {
              poModel["total_amount"] = poModel["price"] * poModel["qty"];
            }
          }

          function addItem() {
            poModels.push({});

            updateModel(poModels.length - 1);
            render();
          }

          function removeSalesModel(index) {
            poModels.splice(index, 1);
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
            for (var i = 0; i < poModels.length; i++) {
              var poModel = poModels[i];
              var matchedModel = getModels(poModel["model_no"], poModel["brand_code"])[0];

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
          }

          function onExchangeRateChange() {
          }

          function onDiscountChange() {
            render();
          }

          function onModelNoChange(event, index) {
            var newModelNo = event.target.value;
            var matchedModel = getModels(newModelNo)[0];
            var poModel = poModels[index];

            if (poModel["model_no"] !== newModelNo) {
              updateModel(index, matchedModel);
              render();
            }

            onFieldBlurred();
          }

          function onBrandCodeChange(event, index) {
            var modelNo = poModels[index]["model_no"];
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
            var poModel = poModels[index];

            if (
              index === poModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              poModel["model_no"] &&
              poModel["brand_code"] &&
              poModel["qty"] &&
              poModel["price"]
            ) {
              updatePrice(index, event.target.value);
              addItem();
            }
          }

          function onQuantityChange(event, index) {
            updateQuantity(index, event.target.value);
            render();
          }

          function onQuantityKeyDown(event, index) {
            var poModel = poModels[index];

            if (
              event.target.dataset["duplicate"] === "true" &&
              index === poModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              poModel["model_no"] &&
              poModel["brand_code"] &&
              poModel["qty"]
            ) {
              updateQuantity(index, event.target.value);
              addItem();
            }
          }

          window.onload = function () {
            document.querySelector("#po-form").reset();

            for (var i = 0; i < poModels.length; i++) {
              var poModel = poModels[i];
              var brandCode = poModel["brand_code"];
              var modelNo = poModel["model_no"];
              var price = poModel["price"];

              updateModel(i, getModels(modelNo, brandCode)[0]);
              updatePrice(i, price);
            }

            render();
          }
        </script>
      <?php else : ?>
        <div id="po-entry-not-found">Sales order not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
