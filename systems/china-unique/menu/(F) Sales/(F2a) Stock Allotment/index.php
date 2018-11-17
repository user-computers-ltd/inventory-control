<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "index_process.php";
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
      <div class="headline"><?php echo getURLParentLocation(); ?></div>
      <form>
        <table id="stock-input">
          <colgroup>
            <col style="width: 100px">
          </colgroup>
          <tr>
            <td><label>Model No.:</label></td>
            <td>
              <select name="filter_model_no[]" multiple>
                <?php
                  foreach ($modelNos as $modelNo) {
                    $modelNo = $modelNo["model_no"];
                    $selected = assigned($filterModelNos) && in_array($modelNo, $filterModelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <form method="post">
        <?php
          if (count($stockModels) > 0) {
            echo "<button type=\"submit\">Save</button>";

            foreach ($stockModels as $warehouseCode => $warehouse) {
              $warehouseName = $warehouse["name"];
              $models = $warehouse["models"];

              echo "
                <div class=\"stock\">
                  <table class=\"stock-header\">
                    <tr>
                      <td>Warehouse:</td>
                      <td>$warehouseName<button type=\"button\" class=\"header-button\" onclick=\"primilaryAllocate('$warehouseCode')\">Primilary Allocate</button></td>
                    </tr>
                  </table>
              ";

              if (count($models) > 0) {
                echo "
                  <table class=\"stock-results\" data-warehouse_code=\"$warehouseCode\">
                    <colgroup>
                      <col style=\"width: 70px\">
                      <col style=\"width: 90px\">
                      <col style=\"width: 80px\">
                      <col>
                      <col>
                      <col style=\"width: 80px\">
                      <col style=\"width: 80px\">
                      <col style=\"width: 80px\">
                      <col style=\"width: 30px\">
                      <col style=\"width: 80px\">
                      <col style=\"width: 30px\">
                    </colgroup>
                    <thead>
                      <tr></tr>
                      <tr>
                        <th>Brand</th>
                        <th>Model No.</th>
                        <th class=\"number\">Available Qty</th>
                        <th>SO No.</th>
                        <th>Customer</th>
                        <th class=\"number\">Selling Price (Inc. Disc.)</th>
                        <th class=\"number\">Outstanding Qty</th>
                        <th class=\"number\">Allot Qty</th>
                        <th></th>
                        <th class=\"number\">Total Allot Qty</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                ";

                $totalQty = 0;

                for ($i = 0; $i < count($models); $i++) {
                  $model = $models[$i];
                  $brandCode = $model["brand_code"];
                  $brand = $model["brand_name"];
                  $modelNo = $model["model_no"];
                  $qty = $model["qty"];
                  $totalQty += $qty;

                  echo "
                    <tr class=\"stock-model\" data-brand_code=\"$brandCode\" data-model_no=\"$modelNo\" data-qty_available=\"$qty\">
                      <td title=\"$brand\">$brand</td>
                      <td title=\"$modelNo\">$modelNo</td>
                      <td class=\"number\">$qty</td>
                      <td><div class=\"so-no\"></div></td>
                      <td><div class=\"customer\"></div></td>
                      <td><div class=\"selling-price\"></div></td>
                      <td><div class=\"outstanding-qty\"></div></td>
                      <td><div class=\"allot-qty\"></div></td>
                      <td><div class=\"remove-allotment\"></div></td>
                      <td class=\"allot-qty-sum number\"></td>
                      <td><button type=\"button\" class=\"action-button add\" onclick=\"addAllotment('$warehouseCode', '$brandCode', '$modelNo')\"></button></td>
                    </tr>
                  ";
                }

                echo "
                      </tbody>
                      <tfoot>
                        <tr>
                          <th></th>
                          <th class=\"number\">Total:</th>
                          <th class=\"number\">$totalQty</th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th class=\"total-allot-qty number\"></th>
                          <th></th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                ";
              } else {
                echo "<div class=\"stock-no-results\">No models found</div>";
              }
            }
          } else if (!$hasFilter) {
            echo "<div class=\"stock-no-results\">Please select a model</div>";
          } else {
            echo "<div class=\"stock-no-results\">No results</div>";
          }
        ?>
      </form>
    </div>
    <script>
      var soModels = <?php echo json_encode($soModels); ?>;
      var allotments = <?php echo json_encode($allotments); ?>;

      var focusedIaNo = null;
      var focusedBrandCode = null;
      var focusedModelNo = null;
      var focusedFieldName = null;

      var stockTableElements = document.querySelectorAll(".stock-results");

      function getStockRowElement(warehouseCode, brandCode, modelNo) {
        return document.querySelector(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] tr.stock-model[data-brand_code=\"" + brandCode + "\"][data-model_no=\"" + modelNo + "\"]");
      }

      function renderStockSum(warehouseCode) {
        var stockTableElement = document.querySelector(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"]");
        var stockTotalAllotQtyElement = stockTableElement.querySelector(".total-allot-qty");
        var stockRowElements = stockTableElement.querySelectorAll("tr.stock-model");

        var totolAllotQty = 0;

        for (var i = 0; i < stockRowElements.length; i++) {
          var stockRowElement = stockRowElements[i];
          totolAllotQty += parseFloat(stockRowElement.querySelector(".allot-qty-sum").innerHTML);
        }

        stockTotalAllotQtyElement.innerHTML = totolAllotQty;
      }

      function renderAllotment(warehouseCode, brandCode, modelNo) {
        var stockRowElement = getStockRowElement(warehouseCode, brandCode, modelNo);
        var customerElement = stockRowElement.querySelector(".customer");
        var soNoElement = stockRowElement.querySelector(".so-no");
        var sellingPriceElement = stockRowElement.querySelector(".selling-price");
        var outstandingQtyElement = stockRowElement.querySelector(".outstanding-qty");
        var allotQtyElement = stockRowElement.querySelector(".allot-qty");
        var allotQtySumElement = stockRowElement.querySelector(".allot-qty-sum");
        var removeElement = stockRowElement.querySelector(".remove-allotment");
        var qtyAvailable = parseFloat(stockRowElement.dataset.qty_available);
        var modelAllotments = (allotments[warehouseCode] && allotments[warehouseCode][brandCode] && allotments[warehouseCode][brandCode][modelNo]) || [];
        var availableSoModels = (soModels[brandCode] && soModels[brandCode][modelNo]) || [];
        var allotQtySum = 0;

        soNoElement.innerHTML = "";
        customerElement.innerHTML = "";
        sellingPriceElement.innerHTML = "";
        outstandingQtyElement.innerHTML = "";
        allotQtyElement.innerHTML = "";
        removeElement.innerHTML = "";

        for (var i = 0; i < modelAllotments.length; i++) {
          var allotment = modelAllotments[i];
          var allotedQty = modelAllotments.filter(function(_, k) { return i !== k; }).reduce(function (sum, a) { return sum + a["qty"]; }, 0);

          var maxQty = Math.min(qtyAvailable - allotedQty, allotment["qty_outstanding"]);

          var soNoInnerHTML =
              "<input type=\"hidden\" name=\"warehouse_code[]\" value=\"" + warehouseCode + "\" />"
            + "<input type=\"hidden\" name=\"brand_code[]\" value=\"" + brandCode + "\" />"
            + "<input type=\"hidden\" name=\"model_no[]\" value=\"" + modelNo + "\" />"
            + "<select name=\"so_no[]\" onchange=\"onSoNoChange(event, '" + warehouseCode + "', '" + brandCode + "', '" + modelNo + "', " + i + ")\" required>"
            + "  <option value=\"\"></option>";

          for (var j = 0; j < availableSoModels.length; j++) {
            var soModel = availableSoModels[j];
            var soNo = soModel["so_no"];
            var otherAllottedQty = getOtherAllottedQty(warehouseCode, brandCode, modelNo, soNo);
            var qtyOutstanding = parseFloat(soModel["qty_outstanding"]) - otherAllottedQty;
            var soNoSelected = allotment["so_no"] === soNo ? "selected" : "";
            var disabled = modelAllotments.filter(function (a) { return a["so_no"] === soNo && allotment["so_no"] !== soNo; }).length > 0 ? " disabled" : "";

            if (qtyOutstanding > 0) {
              soNoInnerHTML += "<option value=\"" + soNo + "\" " + soNoSelected + disabled + ">" + soNo + "</option>";
            }
          }

          soNoInnerHTML += "</select>";

          soNoElement.innerHTML += soNoInnerHTML;
          customerElement.innerHTML += "<div title=\"" + allotment["debtor_name"] + "\">" + allotment["debtor_name"] + "</div>";
          sellingPriceElement.innerHTML += "<div title=\"" + allotment["selling_price"] + "\" class=\"number\">" + allotment["selling_price"].toFixed(2) + "</div>";
          outstandingQtyElement.innerHTML += "<div title=\"" + allotment["qty_outstanding"] + "\" class=\"number\">" + allotment["qty_outstanding"] + "</div>";
          allotQtyElement.innerHTML += "<input class=\"number\" type=\"number\" value=\"" + allotment["qty"] + "\" min=\"0\" max=\"" + maxQty + "\" name=\"qty[]\" onchange=\"onQtyChange(event, '" + warehouseCode + "', '" + brandCode + "', '" + modelNo + "', " + i + ")\" required />";
          removeElement.innerHTML += "<button type=\"button\" class=\"action-button remove\" onclick=\"removeAllotment('" + warehouseCode + "', '" + brandCode + "', '" + modelNo + "', " + i + ")\"></button>";

          allotQtySum += parseFloat(allotment["qty"]);
        }

        allotQtySumElement.innerHTML = allotQtySum;
      }

      function getOtherAllottedQty(warehouseCode, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var otherWarehouseCodes = Object.keys(allotments).filter(function (c) { return c !== warehouseCode; });

        for (var i = 0; i < otherWarehouseCodes.length; i++) {
          var code = otherWarehouseCodes[i];
          var modelAllotments = (allotments[code] && allotments[code][brandCode] && allotments[code][brandCode][modelNo]) || [];
          modelAllotments = modelAllotments.filter(function (allotment) { return allotment["so_no"] === soNo; });

          for (var j = 0; j < modelAllotments.length; j++) {
            totalQty += parseFloat(modelAllotments[j]["qty"]);
          }
        }

        return totalQty;
      }

      function updateAllotmentSoNo(warehouseCode, brandCode, modelNo, index, soNo = "") {
        var modelAllotments = allotments[warehouseCode][brandCode][modelNo];
        var allotment = modelAllotments[index];
        var availableSoModels = (soModels[brandCode] && soModels[brandCode][modelNo]) || [];
        var matchedSoModel = availableSoModels.filter(function (model) { return soNo && (model["so_no"] === soNo); })[0];
        var otherAllottedQty = getOtherAllottedQty(warehouseCode, brandCode, modelNo, soNo);

        allotment["so_no"] = soNo;
        allotment["debtor_name"] = (matchedSoModel && matchedSoModel["debtor_name"]) || "";
        allotment["selling_price"] = (matchedSoModel && parseFloat(matchedSoModel["selling_price"])) || 0;
        allotment["qty_outstanding"] = (matchedSoModel && parseFloat(matchedSoModel["qty_outstanding"]) - otherAllottedQty) || 0;
        allotment["qty"] = 0;
      }

      function updateAllotmentQty(warehouseCode, brandCode, modelNo, index, qty = 0) {
        var modelAllotments = allotments[warehouseCode][brandCode][modelNo];
        var allotment = modelAllotments[index];
        var qtyAvailable = parseFloat(getStockRowElement(warehouseCode, brandCode, modelNo).dataset.qty_available);
        var allotedQty = modelAllotments.filter(function(_, k) { return index !== k; }).reduce(function (sum, a) { return sum + parseFloat(a["qty"]); }, 0);

        allotment["qty"] = Math.min(qtyAvailable - allotedQty, allotment["qty_outstanding"], qty);
      }

      function addAllotment(warehouseCode, brandCode, modelNo) {
        allotments[warehouseCode] = allotments[warehouseCode] || {};
        allotments[warehouseCode][brandCode] = allotments[warehouseCode][brandCode] || {};
        allotments[warehouseCode][brandCode][modelNo] = allotments[warehouseCode][brandCode][modelNo] || [];

        var models = allotments[warehouseCode][brandCode][modelNo];

        models.push({});

        updateAllotmentSoNo(warehouseCode, brandCode, modelNo, models.length - 1);
        updateAllotmentQty(warehouseCode, brandCode, modelNo, models.length - 1);
        renderAllotment(warehouseCode, brandCode, modelNo);
        renderStockSum(warehouseCode);
      }

      function removeAllotment(warehouseCode, brandCode, modelNo, index) {
        var modelAllotments = allotments[warehouseCode][brandCode][modelNo];

        modelAllotments.splice(index, 1);

        renderAllotment(warehouseCode, brandCode, modelNo);
        renderStockSum(warehouseCode);
      }

      function onSoNoChange(event, warehouseCode, brandCode, modelNo, index) {
        updateAllotmentSoNo(warehouseCode, brandCode, modelNo, index, event.target.value);
        renderAllotment(warehouseCode, brandCode, modelNo);
        renderStockSum(warehouseCode);
      }

      function onQtyChange(event, warehouseCode, brandCode, modelNo, index) {
        updateAllotmentQty(warehouseCode, brandCode, modelNo, index, parseFloat(event.target.value));
        renderAllotment(warehouseCode, brandCode, modelNo);
        renderStockSum(warehouseCode);

        var otherWarehouseCodes = Object.keys(allotments).filter(function (c) { return c !== warehouseCode; });

        for (var i = 0; i < otherWarehouseCodes.length; i++) {
          renderAllotment(otherWarehouseCodes[i], brandCode, modelNo);
        }
      }

      function primilaryAllocate(warehouseCode) {
        var stockTableElement = document.querySelector(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"]");
        var stockRowElements = stockTableElement.querySelectorAll("tr.stock-model");

        for (var i = 0; i < stockRowElements.length; i++) {
          var stockRowElement = stockRowElements[i];
          var brandCode = stockRowElement.dataset.brand_code;
          var modelNo = stockRowElement.dataset.model_no;
          var qtyAvailable = stockRowElement.dataset.qty_available;
          var availableSoModels = (soModels[brandCode] && soModels[brandCode][modelNo]) || [];

          allotments[warehouseCode] = allotments[warehouseCode] || {};
          allotments[warehouseCode][brandCode] = allotments[warehouseCode][brandCode] || {};
          allotments[warehouseCode][brandCode][modelNo] = [];
          var models = allotments[warehouseCode][brandCode][modelNo];

          for (var j = 0; j < availableSoModels.length; j++) {
            var soModel = availableSoModels[j];
            var soNo = soModel["so_no"];
            var otherAllottedQty = getOtherAllottedQty(warehouseCode, brandCode, modelNo, soNo);
            var qtyOutstanding = parseFloat(soModel["qty_outstanding"]) - otherAllottedQty;
            var qty = Math.min(qtyAvailable, qtyOutstanding);

            if (qty > 0) {
              models.push({});

              updateAllotmentSoNo(warehouseCode, brandCode, modelNo, models.length - 1, soNo);
              updateAllotmentQty(warehouseCode, brandCode, modelNo, models.length - 1, qty);

              qtyAvailable -= qty;
            }
          }

          renderAllotment(warehouseCode, brandCode, modelNo);
        }

        renderStockSum(warehouseCode);
      }

      window.onload = function () {
        for (var i = 0; i < stockTableElements.length; i++) {
          var warehouseCode = stockTableElements[i].dataset.warehouse_code;
          var stockRowElements = stockTableElements[i].querySelectorAll("tr.stock-model");

          for (var j = 0; j < stockRowElements.length; j++) {
            var stockRowElement = stockRowElements[j];
            var brandCode = stockRowElement.dataset.brand_code;
            var modelNo = stockRowElement.dataset.model_no;
            var modelAllotments = (allotments[warehouseCode] && allotments[warehouseCode][brandCode] && allotments[warehouseCode][brandCode][modelNo]) || [];

            for (var k = 0; k < modelAllotments.length; k++) {
              var soNo = modelAllotments[k]["so_no"];
              var qty = modelAllotments[k]["qty"];

              updateAllotmentSoNo(warehouseCode, brandCode, modelNo, k, soNo);
              updateAllotmentQty(warehouseCode, brandCode, modelNo, k, qty);
            }

            renderAllotment(warehouseCode, brandCode, modelNo);
          }

          renderStockSum(warehouseCode);
        }
      }
    </script>
  </body>
</html>
