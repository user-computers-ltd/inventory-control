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
      <?php if (assigned($doNo)) : ?>
        <form id="delivery-form" method="post">
          <table id="do-header">
            <tr>
              <td>Order No.:</td>
              <td><input type="text" name="do_no" value="<?php echo $doNo; ?>" required /></td>
              <td>Date:</td>
              <td><input type="date" name="do_date" value="<?php echo $doDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <?php echo $debtor; ?>
                <input type="hidden" name="debtor_code" value="<?php echo $debtorCode; ?>" />
              </td>
              <td>Currency:</td>
              <td>
                <?php echo "$currencyCode @ $exchangeRate"; ?>
                <input type="hidden" name="currency_code" value="<?php echo $currencyCode; ?>" />
                <input type="hidden" name="exchange_rate" value="<?php echo $exchangeRate; ?>" />
              </td>
            </tr>
            <tr>
              <td>Address:</td>
              <td><textarea name="address"><?php echo $address; ?></textarea></td>
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
              <td>Contact:</td>
              <td><input type="text" name="contact" value="<?php echo $contact; ?>" required /></td>
              <td>Tax:</td>
              <td>
                <input
                  id="tax"
                  name="tax"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  value="<?php echo $tax; ?>"
                  required
                /><span>%</span>
                <input
                  id="warehouse-code"
                  name="warehouse_code"
                  type="hidden"
                  value="<?php echo $warehouseCode; ?>"
                  required
                />
            </tr>
            <tr>
              <td>Tel:</td>
              <td><input type="text" name="tel" value="<?php echo $tel; ?>" required /></td>
              <td>Status:</td>
              <td><?php echo $isProvisional ? "PROVISIONAL" : $status; ?></td>
            </tr>
          </table>
          <button type="button" onclick="addItem()">Add</button>
          <table id="do-models">
            <colgroup>
              <col style="width: 30px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 150px">
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
                <td id="discount-percentage" class="number"><?php echo $discount; ?></td>
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
              <td><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
            <?php if ($status === "SAVED") : ?>
              <tr>
                <td>
                  <input name="id" type="hidden" value="<?php echo $id; ?>" />
                </td>
                <td>
                  <input id="delete-allotments" name="delete_allotments" type="checkbox" checked/>
                  <label for="delete-allotments">Clear allotments on delete</label>
                </td>
              </tr>
            <?php endif ?>
          </table>
          <button name="status" type="submit" value="SAVED">Save</button>
          <button name="status" type="submit" value="<?php echo $status; ?>" formaction="<?php echo SALES_DELIVERY_ORDER_PRINTOUT_URL; ?>">Print</button>
          <?php if ($status === "SAVED" && !$hasIncoming) : ?>
            <div id="post-wrapper">
              <button name="status" type="submit" value="POSTED" style="display: none;"></button>
              <button type="button" onclick="confirmPost(event)">Post</button>
            </div>
          <?php endif ?>
          <?php if ($status === "SAVED") : ?>
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
            $iaId = preg_replace("/\//", "", $iaNo);
            echo "<datalist class=\"ia-voucher-list\" data-ia_no=\"$iaId\">";

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
          var iaNos = <?php echo json_encode($debtorIaNos); ?>;

          var focusedRow = null;
          var focusedFieldName = null;

          var discountElement = document.querySelector("#discount");
          var tableBodyElement = document.querySelector("#do-models tbody");
          var discountRowElements = document.querySelectorAll(".discount-row");
          var subTotalAmountElement = document.querySelector("#sub-total-amount");
          var discountPercentageElement = document.querySelector("#discount-percentage");
          var discountAmountElement = document.querySelector("#discount-amount");
          var totalQtyElement = document.querySelector("#total-qty");
          var totalAmountElement = document.querySelector("#total-amount");

          var deliveryFormElement = document.querySelector("#delivery-form");
          var postButtonElement = deliveryFormElement.querySelector("button[value=\"POSTED\"]");
          var deleteButtonElement = deliveryFormElement.querySelector("button[value=\"DELETED\"]");

          function getModels(iaNo, modelNo, brandCode) {
            var modelListElement = document.querySelector(iaNo ? ".ia-voucher-list[data-ia_no=\"" + iaNo.replace("/", "") + "\"]" : "#stock-model-list");
            var modelNoSearch = modelNo ? "[value=\"" + modelNo + "\"]" : "";
            var brandCodeSearch = brandCode ? "[data-brand_code=\"" + brandCode + "\"]" : "";
            var matchedModelElements = modelListElement.querySelectorAll("option" + modelNoSearch + brandCodeSearch);
            var models = [];
            for (var i = 0; i < matchedModelElements.length; i++) {
              models.push(matchedModelElements[i].dataset);
            }

            return models;
          }

          function getAvailableModels(iaNo, modelNo, brandCode, soNo) {
            return getModels(iaNo, modelNo, brandCode).filter(function (m) {
              var soNos = soNo ? [ soNo ] : m["so_nos"].split(",");

              for (var i = 0; i < soNos.length; i++) {
                if (doModels.filter(function (dm) {
                  return iaNo === dm["ia_no"] && m["model_no"] === dm["model_no"] && m["brand_code"] === dm["brand_code"] && soNos[i] === dm["so_no"];
                }).length > 0) {
                  return false;
                }
              }

              return true;
            });
          }

          function render() {
            var focusedElement = null;

            tableBodyElement.innerHTML = "";

            var discount = discountElement.value;
            var totalQty = 0;
            var totalAmount = 0;

            for (var i = 0; i < doModels.length; i++) {
              var doModel = doModels[i];
              var iaNo = doModel["ia_no"];
              var modelNo = doModel["model_no"];
              var brandCode = doModel["brand_code"];
              var soNo = doModel["so_no"];
              var modelListId = iaNo ? ".ia-voucher-list[data-ia_no=\"" + iaNo.replace("/", "") + "\"]" : "#stock-model-list";
              var modelListElement = document.querySelector(modelListId);
              var newRowElement = document.createElement("tr");
              var selected = iaNo === "" ? " selected" : "";
              var disabled = iaNo !== "" && getAvailableModels("").length === 0 ? " disabled" : "";
              var rowInnerHTML =
                  "<tr>"
                + "<td>" + (i + 1) + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"ia-no\" "
                    + "name=\"ia_no[]\" "
                    + "onchange=\"onSourceChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'ia_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    <?php if (!assigned($id)) : ?> + " readonly"<?php endif ?>
                  + ">"
                  + "<option value=\"\"" +  selected + disabled + ">On Hand</option>";

              for (var j = 0; j < iaNos.length; j++) {
                var value = iaNos[j];
                var selected = iaNo === value ? " selected" : "";
                var disabled = iaNo !== value && getAvailableModels(value).length === 0 ? " disabled" : "";
                rowInnerHTML += "<option value=\"" + value + "\"" + selected + disabled + ">" + value + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"model-no\" "
                    + "name=\"model_no[]\" "
                    + "onchange=\"onModelNoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'model_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                    <?php if (!assigned($id)) : ?> + " readonly"<?php endif ?>
                  + ">";

              for (var j = 0; j < modelListElement.children.length; j++) {
                var value = modelListElement.children[j].value;
                var selected = modelNo === value ? " selected" : "";
                var disabled = modelNo !== value && getAvailableModels(iaNo, value).length === 0 ? " disabled" : "";
                rowInnerHTML += "<option value=\"" + value + "\"" + selected + disabled + ">" + value + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"brand-code\" "
                    + "name=\"brand_code[]\" "
                    + "value=\"" + brandCode + "\" "
                    + "onchange=\"onBrandCodeChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'brand_code[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                    <?php if (!assigned($id)) : ?> + " readonly"<?php endif ?>
                  + ">";

              for (var j = 0; j < brands.length; j++) {
                var value = brands[j]["code"];
                var selected = brandCode === value ? " selected" : "";
                var hidden = getModels(iaNo, modelNo).map(function (m) {
                  return m["brand_code"];
                }).indexOf(value) === -1 ? " hidden" : "";
                var disabled = brandCode !== value && getAvailableModels(iaNo, modelNo, value).length === 0 ? " disabled" : "";
                rowInnerHTML += "<option value=\"" + value + "\"" + selected + disabled + hidden + ">" + value + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<select "
                    + "class=\"so-no\" "
                    + "name=\"so_no[]\" "
                    + "value=\"" + soNo + "\" "
                    + "onchange=\"onSONoChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'so_no[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "required "
                    <?php if (!assigned($id)) : ?> + " readonly"<?php endif ?>
                  + ">";

              for (var j = 0; j < doModel["so_nos"].length; j++) {
                var value = doModel["so_nos"][j];
                var selected = soNo === value ? " selected" : "";
                var disabled = soNo !== value && getAvailableModels(iaNo, modelNo, brandCode, value).length === 0 ? " disabled" : "";
                rowInnerHTML += "<option value=\"" + value + "\"" + selected + disabled + ">" + value + "</option>";
              }

              rowInnerHTML += "</select>"
                + "</td>"
                + "<td>"
                  + "<input "
                    + "class=\"qty number\" "
                    + "type=\"<?php if (assigned($id)) : ?>number<?php else : ?>hidden<?php endif ?>\" "
                    + "min=\"0\""
                    + "max=\"" + (doModel["qty_max"]) + "\" "
                    + "name=\"qty[]\" "
                    + "value=\"" + doModel["qty"] + "\" "
                    + "onchange=\"onQuantityChange(event, " + i + ")\" "
                    + "onfocus=\"onFieldFocused(" + i + ", 'qty[]')\" "
                    + "onblur=\"onFieldBlurred()\" "
                    + "onkeydown=\"onQuantityKeyDown(event, " + i + ")\" "
                    + "required"
                    <?php if (!assigned($id)) : ?> + " readonly"<?php endif ?>
                  + "/>"
                   <?php if (!assigned($id)) : ?> + "<span class=\"number\">" + doModel["qty"] + "</span>"<?php endif ?>
                + "</td>"
                + "<td class=\"number\">"
                  + doModel["price"]
                  + "<input "
                    + "class=\"qty number\" "
                    + "type=\"hidden\" "
                    + "name=\"price[]\" "
                    + "value=\"" + doModel["price"] + "\" "
                    + "required "
                  + "/>"
                + "</td>"
                + "<td class=\"total-amount number\">" + (doModel["amount"]).toFixed(2) + "</td>"
                + "<td><?php if (assigned($id)) : ?><div class=\"remove\" onclick=\"removeItem(" + i + ")\">×</div><?php endif ?></td>"
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

            discountPercentageElement.innerHTML = discount + "%";
            discountAmountElement.innerHTML = (totalAmount * (discount) / 100).toFixed(2);

            totalQtyElement.innerHTML = totalQty;
            totalAmountElement.innerHTML = (totalAmount * (100 - discount) / 100).toFixed(2);

            if (focusedElement) {
              focusedElement.focus();
            }
          }

          function updateSource(index, iaNo) {
            var doModel = doModels[index];
            doModel["ia_no"] = iaNo || "";
          }

          function updateModel(index, model = {}) {
            var doModel = doModels[index];

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
            var matchedModels = getAvailableModels("").map(function (m) { m["ia_no"] = ""; return m; });

            for (var i = 0; i < iaNos.length; i++) {
              var iaNo = iaNos[i];
              matchedModels = matchedModels.concat(getAvailableModels(iaNo).map(function (m) { m["ia_no"] = iaNo; return m; }));
            }

            if (matchedModels.length > 0) {
              var matchedModel = matchedModels[0];
              doModels.push({});
              var index = doModels.length - 1;

              updateSource(index, matchedModel["ia_no"]);
              updateModel(index, matchedModel);
              render();
            } else {
              showMessageDialog("No more models available");
            }
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

          function onDiscountChange() {
            render();
          }

          function onSourceChange(event, index) {
            var newIANo = event.target.value;
            var doModel = doModels[index];
            var modelNo = doModel["model_no"];
            var brandCode = doModel["brand_code"];
            var soNo = doModel["so_no"];
            var matchedModels = getAvailableModels(newIANo);

            if (doModel["ia_no"] !== newIANo && matchedModels.length > 0) {
              updateSource(index, newIANo);
              updateModel(index, matchedModels[0]);
              render();
            }

            onFieldBlurred();
          }

          function onModelNoChange(event, index) {
            var newModelNo = event.target.value;
            var doModel = doModels[index];
            var iaNo = doModel["ia_no"];
            var brandCode = doModel["brand_code"];
            var soNo = doModel["so_no"];
            var matchedModels = getAvailableModels(iaNo, newModelNo);

            if (doModel["model_no"] !== newModelNo && matchedModels.length > 0) {
              updateModel(index, matchedModels[0]);
              render();
            }

            onFieldBlurred();
          }

          function onBrandCodeChange(event, index) {
            var newBrandCode = event.target.value;
            var doModel = doModels[index];
            var iaNo = doModel["ia_no"];
            var modelNo = doModel["model_no"];
            var soNo = doModel["so_no"];
            var matchedModels = getAvailableModels(iaNo, modelNo, newBrandCode);

            if (doModel["brand_code"] !== newBrandCode && matchedModels.length > 0) {
              updateModel(index, matchedModels[0]);
              render();
            }
          }

          function onSONoChange(event, index) {
            var newSoNo = event.target.value;
            var doModel = doModels[index];
            var iaNo = doModel["ia_no"];
            var modelNo = doModel["model_no"];
            var brandCode = doModel["brand_code"];
            var matchedModels = getAvailableModels(iaNo, modelNo, brandCode, newSoNo);

            if (doModel["so_no"] !== newSoNo && matchedModels.length > 0) {
              var matchedModel = matchedModels[0];

              matchedModel["so_no"] = newSoNo;
              updateModel(index, matchedModel);
              render();
            }
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
              doModel["model_no"] &&
              doModel["brand_code"] &&
              doModel["so_no"] &&
              doModel["qty"]
            ) {
              updateQuantity(index, event.target.value);
              addItem();
            }
          }

          window.addEventListener("load", function () {
            document.querySelector("#delivery-form").reset();

            var newDoModels = [];

            for (var i = 0; i < doModels.length; i++) {
              var doModel = doModels[i];
              var matchedModel = getModels(doModel["ia_no"], doModel["model_no"], doModel["brand_code"])[0];

              if (matchedModel) {
                matchedModel["ia_no"] = doModel["ia_no"];
                matchedModel["so_no"] = doModel["so_no"];
                updateModel(i, matchedModel);

                newDoModels.push(doModels[i]);
              }
            }

            doModels = newDoModels;

            render();
          });

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
