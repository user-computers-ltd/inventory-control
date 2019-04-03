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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_DELIVERY_ORDER_PRINTOUT_TITLE; ?></div>
      <?php if (isset($doHeader)) : ?>
        <?php
          $discount = $doHeader["discount"];
          $status = $doHeader["status"];
        ?>
        <form id="delivery-form" method="post">
          <table id="do-header">
            <tr>
              <td>Order No.:</td>
              <td><input type="text" name="do_no" value="<?php echo $doHeader["do_no"]; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="do_date" value="<?php echo $doHeader["do_date"]; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td><?php echo $doHeader["debtor_code"] . " - " . $doHeader["debtor_name"]; ?></td>
              <td>Currency:</td>
              <td><?php echo $doHeader["currency_code"] . " @ " . $doHeader["exchange_rate"]; ?></td>
            </tr>
            <tr>
              <td>Address:</td>
              <td><textarea name="address"><?php echo $doHeader["debtor_address"]; ?></textarea></td>
              <td>Discount:</td>
              <td><?php echo $doHeader["discount"]; ?>%</td>
            </tr>
            <tr>
              <td>Contact:</td>
              <td><input type="text" name="contact" value="<?php echo $doHeader["debtor_contact"]; ?>" required /></td>
              <td>Tax:</td>
              <td>
                <input
                  id="tax"
                  name="tax"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value="<?php echo $doHeader["tax"]; ?>"
                  required
                /><span>%</span>
                <input
                  id="warehouse-code"
                  name="warehouse_code"
                  type="hidden"
                  value="<?php echo $doHeader["warehouse_code"]; ?>"
                  required
                />
            </tr>
            <tr>
              <td>Tel:</td>
              <td><input type="text" name="tel" value="<?php echo $doHeader["debtor_tel"]; ?>" required /></td>
              <td>Status:</td>
              <td><?php echo $doHeader["is_provisional"] ? "PROVISIONAL" : $status; ?></td>
            </tr>
          </table>
          <!-- <button type="button" onclick="addItem()">Add</button> -->
          <table id="do-models">
            <colgroup>
              <col style="width: 30px">
              <col>
              <col>
              <col style="width: 80px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 30px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th>#</th>
                <th>DO No. / On Hand</th>
                <th>Model No.</th>
                <th>Brand</th>
                <th>Order No.</th>
                <th class="number">Qty</th>
                <th class="number">Price</th>
                <th class="number">Subtotal</th>
                <th class="web-only"></th>
              </tr>
            </thead>
            <tfoot>
              <tr class="discount-row">
                <td colspan="6"></td>
                <th></th>
                <th id="sub-total-amount" class="number"></th>
                <td></td>
              </tr>
              <tr class="discount-row">
                <td colspan="5"></td>
                <td class="number">Discount:</td>
                <td id="discount-percentage" class="number"><?php echo $doHeader["discount"]; ?></td>
                <td id="discount-amount" class="number"></td>
                <td></td>
              </tr>
              <tr>
                <td colspan="4"></td>
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
          <table id="do-footer">
            <tr>
              <td>Remarks:</td>
              <td><textarea id="remarks" name="remarks"><?php echo $doHeader["remarks"]; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status == "SAVED") : ?>
            <button name="status" type="submit" value="SAVED">Save</button>
          <?php endif ?>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo SALES_DELIVERY_ORDER_PRINTOUT_URL . "?id[]=$id"; ?>">Print</button>
          <?php if ($status == "SAVED" && !$hasIncoming) : ?>
          <div id="post-wrapper">
            <button name="status" type="submit" value="POSTED" style="display: none;"></button>
            <button type="button" onclick="confirmPost(event)">Post</button>
          </div>
          <?php endif ?>
          <?php if ($status == "SAVED") : ?>
            <button name="status" type="submit" value="DELETED" style="display: none;"></button>
            <button type="button" onclick="confirmDelete(event)">Delete</button>
          <?php endif ?>
        </form>
        <?php
          echo "<datalist id=\"stock-model-list\">";

          foreach ($stockModels as $model) {
            echo "<option value=\"" . $model["model_no"]
              . "\" data-brand_code=\"" . $model["brand_code"]
              . "\" data-model_no=\"" . $model["model_no"]
              . "\" data-so_nos=\"" . $model["so_nos"]
              . "\" data-prices=\"" . $model["prices"]
              . "\" data-qty_outstandings=\"" . $model["qty_outstandings"]
              . "\" data-qty_so_allotteds=\"" . $model["qty_so_allotteds"]
              . "\" data-qty_stock=\"" . $model["qty_stock"]
              . "\" data-qty_stock_allotted=\"" . $model["qty_stock_allotted"]
              . "\">" . $model["model_no"] . "</option>";
          }

          echo "</datalist>";

          foreach ($iaVouchers as $iaNo => $models) {

            echo "<datalist class=\"ia-voucher-list\" data-ia_no=\"$iaNo\">";

            foreach ($models as $model) {
              echo "<option value=\"" . $model["model_no"]
                . "\" data-brand_code=\"" . $model["brand_code"]
                . "\" data-model_no=\"" . $model["model_no"]
                . "\" data-so_nos=\"" . $model["so_nos"]
                . "\" data-prices=\"" . $model["prices"]
                . "\" data-qty_outstandings=\"" . $model["qty_outstandings"]
                . "\" data-qty_so_allotteds=\"" . $model["qty_so_allotteds"]
                . "\" data-qty_stock=\"" . $model["qty_ia"]
                . "\" data-qty_stock_allotted=\"" . $model["qty_ia_allotted"]
                . "\">" . $model["model_no"] . "</option>";
            }

            echo "</datalist>";
          }
        ?>
        <script>
          var doModels = <?php echo json_encode($doModels); ?>;
          var brands = <?php echo json_encode($brands); ?>;
          var iaNos = <?php echo json_encode($iaNos); ?>;
          var discount = <?php echo $doHeader["discount"]; ?>;

          var focusedRow = null;
          var focusedFieldName = null;

          var tableBodyElement = document.querySelector("#do-models tbody");
          var discountRowElements = document.querySelectorAll(".discount-row");
          var subTotalAmountElement = document.querySelector("#sub-total-amount");
          var discountAmountElement = document.querySelector("#discount-amount");
          var totalQtyElement = document.querySelector("#total-qty");
          var totalAmountElement = document.querySelector("#total-amount");

          var deliveryFormElement = document.querySelector("#delivery-form");
          var postButtonElement = deliveryFormElement.querySelector("button[value=\"POSTED\"]");
          var deleteButtonElement = deliveryFormElement.querySelector("button[value=\"DELETED\"]");

          function getModels(iaNo, modelNo, brandCode) {
            var modelListElement = document.querySelector(iaNo ? ".ia-voucher-list[data-ia_no=\"" + iaNo + "\"]" : "#stock-model-list");
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

            var totalQty = 0;
            var totalAmount = 0;

            for (var i = 0; i < doModels.length; i++) {
              var doModel = doModels[i];
              var modelListId = doModel["ia_no"] ? "ia-voucher-list-" + doModel["ia_no"] : "stock-model-list";
              var matchedModels = getModels(doModel["ia_no"], doModel["model_no"]);
              var newRowElement = document.createElement("tr");
              var isIA = doModel["ia_no"] !== "";
              var rowInnerHTML =
                  "<tr>"
                + "<td>" + (i + 1) + "</td>"
                + "<td>"
                 + (doModel["ia_no"] === "" ? "On Hand" : doModel["ia_no"])
                  + "<select "
                    + "style=\"display: none;\""
                    + "class=\"ia-no\" "
                    + "name=\"ia_no[]\" "
                    + "value=\"" + doModel["ia_no"] + "\" "
                    + "onchange=\"onIANoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'ia_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                  + ">"
                  + "<option value=\"\"" + (!isIA ? " selected" : "") + ">On Hand</option>";

              for (var j = 0; j < iaNos.length; j++) {
                var selected = doModel["ia_no"] === iaNos[j] ? " selected" : "";
                rowInnerHTML += "<option value=\"" + iaNos[j] + "\"" + selected + ">" + iaNos[j] + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"model-no\" "
                    + "type=\"text\" "
                    + "name=\"model_no[]\" "
                    + "list=\"" + modelListId + "\" "
                    + "value=\"" + doModel["model_no"] + "\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'model_no[]')\" "
                    + "onblur=\"onModelNoChange(event, " + i + ")\" "
                    + "autocomplete=\"on\" "
                    + "required "
                    + "readonly "
                  + "/>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"brand-code\" "
                    + "name=\"brand_code[]\" "
                    + "value=\"" + doModel["brand_code"] + "\" "
                    + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                    + "readonly "
                  + ">";

              for (var j = 0; j < brands.length; j++) {
                var code = brands[j]["code"];
                var selected = doModel["brand_code"] === code ? " selected" : "";
                var disabled = matchedModels.map(function (model) {
                  return model["brand_code"];
                }).indexOf(code) === -1 ? " disabled hidden" : "";

                rowInnerHTML += "<option value=\"" + code + "\"" + selected + disabled + ">" + code + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + doModel["so_no"]
                  + "<select "
                    + "style=\"display: none;\""
                    + "class=\"so-no\" "
                    + "name=\"so_no[]\" "
                    + "value=\"" + doModel["so_no"] + "\" "
                    + "onchange=\"onSONoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'so_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                  + ">";

              for (var j = 0; j < doModel["so_nos"].length; j++) {
                var soNo = doModel["so_nos"][j];
                var selected = doModel["so_no"] === soNo ? " selected" : "";
                rowInnerHTML += "<option value=\"" + soNo + "\"" + selected + ">" + soNo + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"qty number\" "
                    + "type=\"number\" "
                    + "min=\"0\""
                    + "max=\"" + (doModel["qty_max"]) + "\" "
                    + "name=\"qty[]\" "
                    + "value=\"" + doModel["qty"] + "\" "
                    + "onchange=\"onQuantityChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onQuantityKeyDown(event, " + i + ")\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td class=\"number\">"
                  + doModel["price"].toFixed(2)
                  + "<input "
                    + "class=\"qty number\" "
                    + "type=\"hidden\" "
                    + "name=\"price[]\" "
                    + "value=\"" + doModel["price"] + "\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td class=\"total-amount number\">" + (doModel["amount"]).toFixed(2) + "</td>"
                + "<td><div class=\"remove\" onclick=\"removeItem(" + i + ")\">×</div></td>"
                + "</tr>";

              newRowElement.innerHTML = rowInnerHTML;

              totalQty += parseFloat(doModel["qty"]);
              totalAmount += parseFloat(doModel["price"] * doModel["qty"]);

              tableBodyElement.appendChild(newRowElement);

              if (i === focusedRow) {
                focusedElement = newRowElement.querySelector("[name=\"" + focusedFieldName + "\"]");
              }
            }

            if (doModels.length === 0) {
              var rowElement = document.createElement("tr");
              rowElement.innerHTML = "<td colspan=\"10\" id=\"so-entry-no-model\">No models</td>";
              tableBodyElement.appendChild(rowElement);
            }

            for (var k = 0; k < discountRowElements.length; k++) {
              toggleClass(discountRowElements[k], "show", doModels.length > 0 && discount > 0);
            }

            subTotalAmountElement.innerHTML = totalAmount.toFixed(2);

            discountAmountElement.innerHTML = (totalAmount * (discount) / 100).toFixed(2);

            totalQtyElement.innerHTML = totalQty;
            totalAmountElement.innerHTML = (totalAmount * (100 - discount) / 100).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateModel(index, model = {}) {
            var doModel = doModels[index];

            doModel["ia_no"] = model["ia_no"] || "";
            doModel["model_no"] = model["model_no"] || "";
            doModel["brand_code"] = model["brand_code"] || "";
            doModel["so_nos"] = model["so_nos"] && model["so_nos"].split(",") || [];
            doModel["prices"] = model["prices"] && model["prices"].split(",") || [];
            doModel["qty_outstandings"] = model["qty_outstandings"] && model["qty_outstandings"].split(",") || [];
            doModel["qty_so_allotteds"] = model["qty_so_allotteds"] && model["qty_so_allotteds"].split(",") || [];
            doModel["qty_stock"] = parseInt(model["qty_stock"], 10) || 0;
            doModel["qty_stock_allotted"] = parseInt(model["qty_stock_allotted"], 10) || 0;
            doModel["so_no"] = model["so_no"] || "";

            var soIndex = doModel["so_nos"].indexOf(doModel["so_no"]);

            if (soIndex === -1) {
              soIndex = 0;
              doModel["so_no"] = doModel["so_nos"][0];
            }

            doModel["price"] = parseFloat(doModel["prices"][soIndex]) || 0;
            doModel["qty_outstanding"] = parseInt(doModel["qty_outstandings"][soIndex], 10);
            doModel["qty_allotted"] = parseInt(doModel["qty_so_allotteds"][soIndex], 10);
            doModel["qty_max"] = Math.min(doModel["qty_outstanding"] - doModel["qty_allotted"], doModel["qty_stock"] - doModel["qty_stock_allotted"]);
            doModel["qty"] = Math.min(doModel["qty_max"], parseInt(doModel["qty"], 10)) || 0;
            doModel["amount"] = doModel["price"] * doModel["qty"];
          }

          function updateQuantity (index, qty = 0) {
            var doModel = doModels[index];

            doModel["qty"] = Math.max(0, Math.min(doModel["qty_max"], parseFloat(qty)));

            if (doModel["price"]) {
              doModel["amount"] = doModel["price"] * doModel["qty"];
            }
          }

          function addItem() {
            doModels.push({});

            updateModel(doModels.length - 1);
            render();
          }

          function removeItem(index) {
            doModels.splice(index, 1);
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

          function onIANoChange(event, index) {
            var newIANo = event.target.value;
            var doModel = doModels[index];
            var matchedModel = getModels(newIANo, doModel["model_no"])[0];

            if (doModel["ia_no"] !== newIANo) {
              updateModel(index, matchedModel);
              render();
            }

            onFieldBlurred();
          }

          function onModelNoChange(event, index) {
            var newModelNo = event.target.value;
            var doModel = doModels[index];
            var matchedModel = getModels(doModel["ia_no"], newModelNo)[0];

            if (doModel["model_no"] !== newModelNo) {
              updateModel(index, matchedModel);
              render();
            }

            onFieldBlurred();
          }

          function onBrandCodeChange(event, index) {
            var doModel = doModels[index];
            var brandCode = event.target.value;
            var matchedModel =
              doModel["model_no"] &&
              brandCode &&
              getModels(doModel["ia_no"], doModel["model_no"]).filter(function (model) {
                return model["brand_code"] === brandCode;
              })[0] || undefined;

            updateModel(index, matchedModel);
            render();
          }

          function onSONoChange(event, index) {
            var doModel = doModels[index];
            var soNo = event.target.value;
            var matchedModel = getModels(doModel["ia_no"], doModel["model_no"], doModel["brand_code"])[0];
            matchedModel["so_no"] = soNo;
            updateModel(index, matchedModel);
            render();
          }

          function onQuantityChange(event, index) {
            updateQuantity(index, event.target.value);
            render();
          }

          function onQuantityKeyDown(event, index) {
            var doModel = doModels[index];

            if (
              index === doModels.length - 1 &&
              (event.which || event.keyCode) === 9 &&
              doModel["ia_no"] &&
              doModel["model_no"] &&
              doModel["brand_code"] &&
              doModel["so_no"] &&
              doModel["qty"]
            ) {
              updateQuantity(index, event.target.value);
              addItem();
            }
          }

          window.onload = function () {
            document.querySelector("#delivery-form").reset();

            for (var i = 0; i < doModels.length; i++) {
              var doModel = doModels[i];

              var matchedModel = getModels(doModel["ia_no"], doModel["model_no"], doModel["brand_code"])[0];
              matchedModel["ia_no"] = doModel["ia_no"];
              matchedModel["so_no"] = doModel["so_no"];
              updateModel(i, matchedModel);
            }

            render();
          }

          function confirmPost(event) {
            showConfirmDialog("<b>Are you sure you want to post?", function () {
              postButtonElement.click();
              setLoadingMessage("Posting...");
              toggleLoadingScreen(true);
            });
          }

          function confirmDelete(event) {
            showConfirmDialog("<b>Are you sure you want to delete?", function () {
              deleteButtonElement.click();
              setLoadingMessage("Deleting...");
              toggleLoadingScreen(true);
            });
          }
        </script>
      <?php else : ?>
        <div id="do-not-found"><?php echo SALES_DELIVERY_ORDER_PRINTOUT_TITLE; ?> not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
