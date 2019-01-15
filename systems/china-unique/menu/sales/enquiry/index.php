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
      <div class="headline"><?php echo SALES_ENQUIRY_TITLE; ?></div>
      <form id="enquiry-form" method="post">
        <table id="enquiry-header">
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
            <td>Person In-charge:</td>
            <td><input type="text" name="in_charge" required/></td>
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
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <input
                id="normal-price"
                name="price-standard"
                type="radio"
                value="normal_price"
                onchange="onPriceStandardChange()"
                checked
              />
              <label for="normal-price">Normal Price</label>
              <input
                id="special-price"
                name="price-standard"
                type="radio"
                value="special_price"
                onchange="onPriceStandardChange()"
              />
              <label for="special-price">Special Price</label>
            </td>
          </tr>
        </table>
        <button type="button" onclick="addItem()">Add</button>
        <table id="enquiry-models">
          <colgroup>
            <col>
            <col style="width: 80px">
            <col style="width: 60px">
            <col style="width: 60px">
            <col style="width: 60px">
            <col style="width: 60px">
            <col style="width: 60px">
            <col style="width: 60px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 30px">
          </colgroup>
          <thead>
            <tr>
              <th rowspan="2">Model no.</th>
              <th rowspan="2">Brand code</th>
              <th colspan="6" class="quantity">Quantity</th>
              <th rowspan="2" class="number">Price</th>
              <th rowspan="2" class="number">Subtotal</th>
              <th rowspan="2"></th>
            <tr>
              <th class="number">Request</th>
              <th class="number">On Hand</th>
              <th class="number">Reserved</th>
              <th class="number">Available</th>
              <th class="number">On Order</th>
              <th class="number">Allot</th>
            </tr>
          </thead>
          <tfoot>
            <tr class="discount-row">
              <td colspan="8"></td>
              <th></th>
              <th id="sub-total-amount" class="number"></th>
              <td></td>
            </tr>
            <tr class="discount-row">
              <td colspan="7"></td>
              <td class="number">Discount:</td>
              <td id="discount-percentage" class="number"></td>
              <td id="discount-amount" class="number"></td>
              <td></td>
            </tr>
            <tr>
              <th></th>
              <th class="number">Total:</th>
              <th id="total-qty" class="number"></th>
              <th colspan="4"></th>
              <th id="total-qty-allotted" class="number"></th>
              <th></th>
              <th id="total-amount" class="number"></th>
              <th></th>
            </tr>
          </tfoot>
          <tbody>
          </tbody>
        </table>
        <table id="enquiry-footer">
          <tr>
            <td>Remarks:</td>
            <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
          </tr>
        </table>
        <button type="submit" formaction="<?php echo SALES_ENQUIRY_PRINTOUT_URL; ?>">Print</button>
        <button type="submit" formaction="<?php echo SALES_ORDER_URL; ?>">Create Sales Order</button>
      </form>
      <datalist id="model-list">
        <?php
          foreach ($models as $model) {
            echo "<option value=\"" . $model["model_no"]
             . "\" data-model_no=\"" . $model["model_no"]
             . "\" data-brand_code=\"" . $model["brand_code"]
             . "\" data-normal_price=\"" . $model["normal_price"]
             . "\" data-special_price=\"" . $model["special_price"]
             . "\" data-qty_on_hand=\"" . $model["qty_on_hand"]
             . "\" data-qty_on_reserve=\"" . $model["qty_on_reserve"]
             . "\" data-qty_on_order=\"" . $model["qty_on_order"]
             . "\">" . $model["model_no"] . "</option>";
          }
        ?>
      </datalist>
      <script>
        var soModels = [];
        var currencies = <?php echo json_encode($currencies); ?>;
        var brands = <?php echo json_encode($brands); ?>;
        var focusedRow = null;
        var focusedFieldName = null;

        var discountElement = document.querySelector("#discount");
        var currencyCodeElement = document.querySelector("#currency-code");
        var exchangeRateElement = document.querySelector("#exchange-rate");
        var tableBodyElement = document.querySelector("#enquiry-models tbody");
        var discountRowElements = document.querySelectorAll(".discount-row");
        var subTotalAmountElement = document.querySelector("#sub-total-amount");
        var discountPercentageElement = document.querySelector("#discount-percentage");
        var discountAmountElement = document.querySelector("#discount-amount");
        var totalQtyElement = document.querySelector("#total-qty");
        var totalQtyAllottedElement = document.querySelector("#total-qty-allotted");
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
          var totalQtyAllotted = 0;
          var totalAmount = 0;

          for (var i = 0; i < soModels.length; i++) {
            var soModel = soModels[i];
            var matchedModels = getModels(soModel["model_no"]);
            var newRowElement = document.createElement("tr");
            var insufficient = soModel["qty_available"] < soModel["qty"] ? "insufficient" : "";

            var rowInnerHTML =
                "<tr>"
              + "<td>"
                + "<input "
                  + "class=\"model-no\" "
                  + "type=\"text\" "
                  + "name=\"model_no[]\" "
                  + "list=\"model-list\" "
                  + "value=\"" + soModel["model_no"] + "\" "
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
                  + "value=\"" + soModel["brand_code"] + "\" "
                  + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "required "
                + ">";

            for (var j = 0; j < brands.length; j++) {
              var code = brands[j]["code"];
              var selected = soModel["brand_code"] === code ? " selected" : "";
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
                  + "name=\"qty_requested[]\" "
                  + "value=\"" + soModel["qty"] + "\" "
                  + "onchange=\"onQuantityChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'qty_requested[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td class=\"number\">" + soModel["qty_on_hand"] + "</td>"
              + "<td class=\"number\">" + soModel["qty_on_reserve"] + "</td>"
              + "<td class=\"number " + insufficient + "\">" + soModel["qty_available"] + "</td>"
              + "<td class=\"number\">" + soModel["qty_on_order"] + "</td>"
              + "<td>"
                + "<input "
                  + "class=\"qty number\" "
                  + "type=\"number\" "
                  + "min=\"0\" "
                  + "max=\"" + soModel["qty_available"] + "\" "
                  + "name=\"qty[]\" "
                  + "value=\"" + soModel["qty_allotted"] + "\" "
                  + "onchange=\"onQuantityAllottedChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td>"
                + "<input "
                  + "class=\"price number\" "
                  + "type=\"number\" "
                  + "step=\"0.01\" "
                  + "min=\"0\" "
                  + "name=\"price[]\" "
                  + "value=\"" + soModel["price"].toFixed(2) + "\" "
                  + "onchange=\"onPriceChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'price[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "onkeydown=\"onPriceKeyDown(event, " + i + ")\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td class=\"total-amount number\">" + soModel["total_amount"].toFixed(2) + "</td>"
              + "<td><div class=\"remove\" onclick=\"removeSalesModel(" + i + ")\">×</div></td>"
              + "</tr>";

            newRowElement.innerHTML = rowInnerHTML;

            totalQty += parseFloat(soModel["qty"]);
            totalQtyAllotted += parseFloat(soModel["qty_allotted"]);
            totalAmount += parseFloat(soModel["price"] * soModel["qty_allotted"]);
            console.log(soModel["total_amount"]);
            tableBodyElement.appendChild(newRowElement);

            if (i === focusedRow) {
              focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
            }
          }

          if (soModels.length === 0) {
            var rowElement = document.createElement("tr");
            rowElement.innerHTML = "<td colspan=\"10\" id=\"enquiry-entry-no-model\">No models</td>";
            tableBodyElement.appendChild(rowElement);
          }

          for (var k = 0; k < discountRowElements.length; k++) {
            toggleClass(discountRowElements[k], "show", soModels.length > 0 && discount > 0);
          }

          subTotalAmountElement.innerHTML = totalAmount.toFixed(2);

          discountPercentageElement.innerHTML = discount + "%";
          discountAmountElement.innerHTML = (totalAmount * (discount) / 100).toFixed(2);

          totalQtyElement.innerHTML = totalQty;
          totalQtyAllottedElement.innerHTML = totalQtyAllotted;
          totalAmountElement.innerHTML = (totalAmount * (100 - discount) / 100).toFixed(2);

          if (focusedElement) {
            focusedElement.focus();
          }
        }

        function updateModel (index, model = {}) {
          var priceStandard = document.querySelector("input[name='price-standard']:checked").value;

          var soModel = soModels[index];

          soModel["model_no"] = model["model_no"] || "";
          soModel["brand_code"] = model["brand_code"] || "";
          soModel["normal_price"] = parseFloat(model["normal_price"]) || 0;
          soModel["special_price"] = parseFloat(model["special_price"]) || 0;
          soModel["price"] = parseFloat(model[priceStandard]) || 0;
          soModel["qty"] = soModel["qty"] || 0;
          soModel["qty_on_hand"] = parseFloat(model["qty_on_hand"]) || 0;
          soModel["qty_on_order"] = parseFloat(model["qty_on_order"]) || 0;
          soModel["qty_on_reserve"] = parseFloat(model["qty_on_reserve"]) || 0;
          soModel["qty_available"] = soModel["qty_on_hand"] - soModel["qty_on_reserve"];
          soModel["qty_allotted"] = soModel["qty_allotted"] || 0;
          soModel["total_amount"] = (soModel["qty_allotted"] || 0) * soModel["price"];
        }

        function updateQuantity (index, qty = 0) {
          var soModel = soModels[index];

          soModel["qty"] = Math.max(0, parseFloat(qty));
          soModel["qty_allotted"] = Math.min(soModel["qty"], soModel["qty_available"]);

          if (soModel["price"]) {
            soModel["total_amount"] = soModel["price"] * soModel["qty_allotted"];
          }
        }

        function updateQuantityAllotted (index, qty = 0) {
          var soModel = soModels[index];

          soModel["qty_allotted"] = Math.max(0, parseFloat(qty));

          if (soModel["price"]) {
            soModel["total_amount"] = soModel["price"] * soModel["qty_allotted"];
          }
        }

        function updatePrice(index, price = 0) {
          var soModel = soModels[index];

          soModel["price"] = Math.max(0, parseFloat(price));

          if (soModel["qty_available"]) {
            soModel["total_amount"] = soModel["price"] * soModel["qty_available"];
          }
        }

        function addItem() {
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
        }

        function onExchangeRateChange() {
        }

        function onDiscountChange() {
          render();
        }

        function onModelNoChange(event, index) {
          var newModelNo = event.target.value;
          var matchedModel = getModels(newModelNo)[0];
          var soModel = soModels[index];

          if (soModel["model_no"] !== newModelNo) {
            var existsAlready = soModels.filter(function (m) {
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
          var modelNo = soModels[index]["model_no"];
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

        function onQuantityChange(event, index) {
          updateQuantity(index, event.target.value);
          render();
        }

        function onQuantityAllottedChange(event, index) {
          updateQuantityAllotted(index, event.target.value);
          render();
        }

        function onPriceChange(event, index) {
          updatePrice(index, event.target.value);
          render();
        }

        function onPriceKeyDown(event, index) {
          var soModel = soModels[index];

          if (
            index === soModels.length - 1 &&
            (event.which || event.keyCode) === 9 &&
            soModel["model_no"] &&
            soModel["brand_code"] &&
            soModel["qty"] &&
            soModel["price"]
          ) {
            updatePrice(index, event.target.value);
            addItem();
          }
        }

        window.onload = function () {
          document.querySelector("#enquiry-form").reset();

          for (var i = 0; i < soModels.length; i++) {
            var soModel = soModels[i];
            var brandCode = soModel["brand_code"];
            var modelNo = soModel["model_no"];

            updateModel(i, getModels(modelNo, brandCode)[0]);
          }

          render();
        }
      </script>
    </div>
  </body>
</html>
