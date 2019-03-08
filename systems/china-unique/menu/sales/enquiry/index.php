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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo $headline; ?></div>
      <form id="enquiry-form" method="post">
        <?php if (assigned($id)) : ?>
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <?php endif ?>
        <table id="enquiry-header">
          <tr>
            <tr>
              <td>Order No.:</td>
              <td><input type="text" name="enquiry_no" value="<?php echo $enquiryNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="enquiry_date" value="<?php echo $enquiryDate; ?>" max="<?php echo date("Y-m-d"); ?>" required readonly /></td>
            </tr>
            <tr>
              <td>Client Code:</td>
              <td>
                <select id="debtor-code" name="debtor_code" onchange="onDebtorCodeChange()" equired>
                  <?php
                    foreach ($debtors as $code => $name) {
                      $selected = $debtorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code</option>";
                    }
                  ?>
                </select>
              </td>
              <td>Client Name:</td>
              <td>
                <input
                  id="debtor-name"
                  name="debtor_name"
                  type="text"
                  value="<?php echo $debtorName; ?>"
                  required
                />
              </td>
            </tr>
            <tr>
              <td>Person In-charge:</td>
              <td><input type="text" name="in_charge" value="<?php echo $inCharge; ?>" required/></td>
              <td>Currency:</td>
              <td>
                <select
                  id="currency-code"
                  name="currency_code"
                  onchange="onCurrencyCodeChange()"
                  required
                >
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
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <input
                id="normal-price"
                name="price_standard"
                type="radio"
                value="normal_price"
                onchange="onPriceStandardChange()"
                <?php echo $priceStandard === "normal_price" ? "checked" : ""; ?>
              />
              <label for="normal-price">Normal Price</label>
              <input
                id="special-price"
                name="price_standard"
                type="radio"
                value="special_price"
                onchange="onPriceStandardChange()"
                <?php echo $priceStandard === "special_price" ? "checked" : ""; ?>
              />
              <label for="special-price">Special Price</label>
              <input
                id="end-user-price"
                name="price_standard"
                type="radio"
                value="end_user_price"
                onchange="onPriceStandardChange()"
                <?php echo $priceStandard === "end_user_price" ? "checked" : ""; ?>
              />
              <label for="end-user-price">End User Price</label>
            </td>
          </tr>
        </table>
        <button type="button" onclick="addItem()">Add</button>
        <table id="enquiry-models">
          <colgroup>
            <col style="width: 30px">
            <col>
            <col style="width: 80px">
            <col style="width: 60px">
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
              <th rowspan="2">#</th>
              <th rowspan="2">Model no.</th>
              <th rowspan="2">Brand code</th>
              <th colspan="7" class="quantity">Quantity</th>
              <th rowspan="2" class="number">Price</th>
              <th rowspan="2" class="number">Subtotal</th>
              <th rowspan="2"></th>
            </tr>
            <tr>
              <th class="number">Request</th>
              <th class="number">On Hand</th>
              <th class="number">Reserved</th>
              <th class="number">Available</th>
              <th class="number">Allot</th>
              <th class="number">Incoming</th>
              <th class="number">Allotment</th>
            </tr>
          </thead>
          <tfoot>
            <tr class="discount-row">
              <td colspan="10"></td>
              <th></th>
              <th id="sub-total-amount" class="number"></th>
              <td></td>
            </tr>
            <tr class="discount-row">
              <td colspan="9"></td>
              <td class="number">Discount:</td>
              <td id="discount-percentage" class="number"></td>
              <td id="discount-amount" class="number"></td>
              <td></td>
            </tr>
            <tr>
              <th></th>
              <th></th>
              <th class="number">Total:</th>
              <th id="total-qty" class="number"></th>
              <th colspan="3"></th>
              <th id="total-qty-allotted" class="number"></th>
              <th colspan="3"></th>
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
        <button name="status" type="submit" value="SAVED">Save</button>
        <button type="submit" formaction="<?php echo SALES_ENQUIRY_PRINTOUT_URL; ?>">Print</button>
        <?php if (isSupervisor()) : ?>
          <button type="submit" formaction="<?php echo SALES_ORDER_URL; ?>">Create Sales Order</button>
        <?php endif ?>
        <button name="status" type="submit" value="DELETED">Delete</button>
      </form>
      <datalist id="model-list">
        <?php
          foreach ($models as $model) {
            echo "<option value=\"" . $model["model_no"]
             . "\" data-model_no=\"" . $model["model_no"]
             . "\" data-brand_code=\"" . $model["brand_code"]
             . "\" data-normal_price=\"" . $model["normal_price"]
             . "\" data-special_price=\"" . $model["special_price"]
             . "\" data-end_user_price=\"" . $model["end_user_price"]
             . "\" data-qty_on_hand=\"" . $model["qty_on_hand"]
             . "\" data-qty_on_hand_reserve=\"" . $model["qty_on_hand_reserve"]
             . "\" data-qty_incoming=\"" . $model["qty_incoming"]
             . "\" data-qty_incoming_reserve=\"" . $model["qty_incoming_reserve"]
             . "\">" . $model["model_no"] . "</option>";
          }
        ?>
      </datalist>
      <script>
        var enquiryModels = <?php echo json_encode($enquiryModels); ?>;
        var currencies = <?php echo json_encode($currencies); ?>;
        var brands = <?php echo json_encode($brands); ?>;
        var debtors = <?php echo json_encode($debtors); ?>;
        var focusedRow = null;
        var focusedFieldName = null;

        var debtorCodeElement = document.querySelector("#debtor-code");
        var debtorNameElement = document.querySelector("#debtor-name");
        var debtorNameFieldElement = document.querySelector(".debtor-name input");
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
          var matchedModelElements = modelListElement.querySelectorAll("option[value=\"" + modelNo.toUpperCase() + "\"]" + brandCodeSearch);
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

          for (var i = 0; i < enquiryModels.length; i++) {
            var enquiryModel = enquiryModels[i];
            var matchedModels = getModels(enquiryModel["model_no"]);
            var newRowElement = document.createElement("tr");
            var insufficient = enquiryModel["qty_available"] < enquiryModel["qty"] ? "insufficient" : "";

            var rowInnerHTML =
                "<tr>"
              + "<td>" + (i + 1) + "</td>"
              + "<td>"
                + "<input "
                  + "class=\"model-no\" "
                  + "type=\"text\" "
                  + "name=\"model_no[]\" "
                  + "list=\"model-list\" "
                  + "value=\"" + enquiryModel["model_no"] + "\" "
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
                  + "value=\"" + enquiryModel["brand_code"] + "\" "
                  + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "required "
                + ">";

            for (var j = 0; j < brands.length; j++) {
              var code = brands[j]["code"];
              var selected = enquiryModel["brand_code"] === code ? " selected" : "";
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
                  + "value=\"" + enquiryModel["qty"] + "\" "
                  + "onchange=\"onQuantityChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td class=\"number\">" + enquiryModel["qty_on_hand"] + "</td>"
              + "<td class=\"number\">" + enquiryModel["qty_on_hand_reserve"] + "</td>"
              + "<td class=\"number " + insufficient + "\">" + enquiryModel["qty_available"] + "</td>"
              + "<td>"
                + "<input "
                  + "class=\"qty number\" "
                  + "type=\"number\" "
                  + "min=\"0\" "
                  + "max=\"" + enquiryModel["qty_available"] + "\" "
                  + "name=\"qty_allotted[]\" "
                  + "value=\"" + enquiryModel["qty_allotted"] + "\" "
                  + "onchange=\"onQuantityAllottedChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'qty_allotted[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td class=\"number\">" + enquiryModel["qty_incoming"] + "</td>"
              + "<td class=\"number\">" + enquiryModel["qty_incoming_reserve"] + "</td>"
              + "<td>"
                + "<input "
                  + "class=\"price number\" "
                  + "type=\"number\" "
                  + "step=\"0.01\" "
                  + "min=\"0\" "
                  + "name=\"price[]\" "
                  + "value=\"" + enquiryModel["price"].toFixed(2) + "\" "
                  + "onchange=\"onPriceChange(event, " + i + ")\" "
                  + "onfocus=\"onFieldFocused(" + i + ", 'price[]')\" "
                  + "onblur=\"onFieldBlurred()\" "
                  + "onkeydown=\"onPriceKeyDown(event, " + i + ")\" "
                  + "required "
                + "/>"
              + "</td>"
              + "<td class=\"total-amount number\">" + enquiryModel["total_amount"].toFixed(2) + "</td>"
              + "<td><div class=\"remove\" onclick=\"removeSalesModel(" + i + ")\">×</div></td>"
              + "</tr>";

            newRowElement.innerHTML = rowInnerHTML;

            totalQty += parseFloat(enquiryModel["qty"]);
            totalQtyAllotted += parseFloat(enquiryModel["qty_allotted"]);
            totalAmount += parseFloat(enquiryModel["price"] * enquiryModel["qty_allotted"]);

            tableBodyElement.appendChild(newRowElement);

            if (i === focusedRow) {
              focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
            }
          }

          if (enquiryModels.length === 0) {
            var rowElement = document.createElement("tr");
            rowElement.innerHTML = "<td colspan=\"12\" id=\"enquiry-entry-no-model\">No models</td>";
            tableBodyElement.appendChild(rowElement);
          }

          for (var k = 0; k < discountRowElements.length; k++) {
            toggleClass(discountRowElements[k], "show", enquiryModels.length > 0 && discount > 0);
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
          var priceStandard = document.querySelector("input[name='price_standard']:checked").value;

          var enquiryModel = enquiryModels[index];

          enquiryModel["model_no"] = model["model_no"] || "";
          enquiryModel["brand_code"] = model["brand_code"] || "";
          enquiryModel["normal_price"] = parseFloat(model["normal_price"]) || 0;
          enquiryModel["special_price"] = parseFloat(model["special_price"]) || 0;
          enquiryModel["price"] = parseFloat(model[priceStandard]) || 0;
          enquiryModel["qty"] = enquiryModel["qty"] || 0;
          enquiryModel["qty_on_hand"] = parseFloat(model["qty_on_hand"]) || 0;
          enquiryModel["qty_on_hand_reserve"] = parseFloat(model["qty_on_hand_reserve"]) || 0;
          enquiryModel["qty_incoming"] = parseFloat(model["qty_incoming"]) || 0;
          enquiryModel["qty_incoming_reserve"] = parseFloat(model["qty_incoming_reserve"]) || 0;
          enquiryModel["qty_available"] = Math.max(0, enquiryModel["qty_on_hand"] - enquiryModel["qty_on_hand_reserve"]);
          enquiryModel["qty_allotted"] = enquiryModel["qty_allotted"] || 0;
          enquiryModel["total_amount"] = (enquiryModel["qty_allotted"] || 0) * enquiryModel["price"];
        }

        function updateQuantity (index, qty = 0) {
          var enquiryModel = enquiryModels[index];

          enquiryModel["qty"] = Math.max(0, parseFloat(qty));
          enquiryModel["qty_allotted"] = Math.min(enquiryModel["qty"], enquiryModel["qty_available"]);

          if (enquiryModel["price"]) {
            enquiryModel["total_amount"] = enquiryModel["price"] * enquiryModel["qty_allotted"];
          }
        }

        function updateQuantityAllotted (index, qty = 0) {
          var enquiryModel = enquiryModels[index];

          enquiryModel["qty_allotted"] = Math.max(0, parseFloat(qty));

          if (enquiryModel["price"]) {
            enquiryModel["total_amount"] = enquiryModel["price"] * enquiryModel["qty_allotted"];
          }
        }

        function updatePrice(index, price = 0) {
          var enquiryModel = enquiryModels[index];

          enquiryModel["price"] = Math.max(0, parseFloat(price));

          if (enquiryModel["qty_available"]) {
            enquiryModel["total_amount"] = enquiryModel["price"] * enquiryModel["qty_allotted"];
          }
        }

        function addItem() {
          enquiryModels.push({});

          updateModel(enquiryModels.length - 1);
          render();
        }

        function removeSalesModel(index) {
          enquiryModels.splice(index, 1);
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
          for (var i = 0; i < enquiryModels.length; i++) {
            var enquiryModel = enquiryModels[i];
            var matchedModel = getModels(enquiryModel["model_no"], enquiryModel["brand_code"])[0];

            if (matchedModel) {
              updateModel(i, matchedModel);
            }
          }

          render();
        }

        function onDebtorCodeChange() {
          var debtorCode = debtorCodeElement.value;
          var debtorName = debtors[debtorCode];

          debtorNameElement.value = debtorCode === "1" ? "" : debtorName;
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
          var enquiryModel = enquiryModels[index];

          if (enquiryModel["model_no"] !== newModelNo) {
            var existsAlready = enquiryModels.filter(function (m) {
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
          var modelNo = enquiryModels[index]["model_no"];
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
          var enquiryModel = enquiryModels[index];

          if (
            index === enquiryModels.length - 1 &&
            (event.which || event.keyCode) === 9 &&
            enquiryModel["model_no"] &&
            enquiryModel["brand_code"] &&
            enquiryModel["qty"] &&
            enquiryModel["price"]
          ) {
            updatePrice(index, event.target.value);
            addItem();
          }
        }

        window.onload = function () {
          document.querySelector("#enquiry-form").reset();

          for (var i = 0; i < enquiryModels.length; i++) {
            var enquiryModel = enquiryModels[i];
            var brandCode = enquiryModel["brand_code"];
            var modelNo = enquiryModel["model_no"];
            var price = enquiryModel["price"];

            updateModel(i, getModels(modelNo, brandCode)[0]);

            if (price != -1) {
              updatePrice(i, price);
            }
          }

          render();
        }
      </script>
    </div>
  </body>
</html>
