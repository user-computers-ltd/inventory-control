<?php
  define("SYSTEM_PATH", "../../../../");
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
      <div class="headline"><?php echo ALLOTMENT_STOCK_TITLE; ?></div>
      <form>
        <table id="stock-input">
          <tr>
            <th>Warehouse:</th>
            <th>Customer:</th>
            <th>SO No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_warehouse_code[]" multiple>
                <?php
                  foreach ($warehouses as $warehouse) {
                    $warehouseCode = $warehouse["warehouse_code"];
                    $warehouseName = $warehouse["warehouse_name"];
                    $selected = assigned($filterWarehouseCodes) && in_array($code, $filterWarehouseCodes) ? "selected" : "";
                    echo "<option value=\"$warehouseCode\" $selected>$warehouseCode -$warehouseName</option>";
                  }
                ?>
              </select>
            </td>
            <td>
              <select name="filter_debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $debtor) {
                    $debtorCode = $debtor["code"];
                    $debtorName = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($debtorCode, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$debtorCode\" $selected>$debtorCode - $debtorName</option>";
                  }
                ?>
              </select>
            </td>
            <td>
              <select name="filter_so_no[]" multiple>
                <?php
                  foreach ($sos as $so) {
                    $soNo = $so["so_no"];
                    $selected = assigned($filterSoNos) && in_array($soNo, $filterSoNos) ? "selected" : "";
                    echo "<option value=\"$soNo\" $selected>$soNo</option>";
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
          if (count($stockResults) > 0) {
            echo "<button type=\"submit\">Save</button>";

            foreach ($stockResults as $warehouseCode => $warehouse) {
              $warehouseName = $warehouse["name"];
              $models = $warehouse["models"];

              echo "
                <div class=\"warehouse\"><h4>$warehouseCode - $warehouseName</h4>
                  <table class=\"stock-header\">
                    <tr>
                      <td>
                        <button type=\"button\" class=\"header-button\" onclick=\"allocateBySoDate('$warehouseCode')\">Allocate by date</button>
                        <button type=\"button\" class=\"header-button\" onclick=\"allocateBySoProportion('$warehouseCode')\">Allocate by proportion</button>
                        <button type=\"button\" class=\"header-button\" onclick=\"resetAllotments('$warehouseCode')\">Reset</button>
                      </td>
                    </tr>
                  </table>
              ";

              if (count($models) > 0) {
                echo "
                  <table class=\"stock-results\" data-warehouse_code=\"$warehouseCode\">
                    <colgroup>
                      <col style=\"width: 70px\">
                      <col>
                      <col style=\"width: 75px\">
                      <col>
                      <col style=\"width: 90px\">
                      <col style=\"width: 80px\">
                      <col style=\"width: 75px\">
                      <col style=\"width: 75px\">
                      <col style=\"width: 75px\">
                    </colgroup>
                    <thead>
                      <tr></tr>
                      <tr>
                        <th>Brand</th>
                        <th>Model No.</th>
                        <th class=\"number\">Available Qty</th>
                        <th>SO No.</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class=\"number\">Outstanding Qty</th>
                        <th class=\"number\">Allot Qty</th>
                        <th class=\"number\">Total Allot Qty</th>
                      </tr>
                    </thead>
                    <tbody>
                ";

                $totalQty = 0;

                for ($i = 0; $i < count($models); $i++) {
                  $model = $models[$i];
                  $brandCode = $model["brand_code"];
                  $brandName = $model["brand_name"];
                  $modelNo = $model["model_no"];
                  $qty = $model["qty"];
                  $totalQty += $qty;

                  $matchedModels = isset($soModels[$brandCode][$modelNo]) ? $soModels[$brandCode][$modelNo] : array(array());
                  $firstModel = true;

                  foreach ($matchedModels as $matchedModel) {
                    $soId = $matchedModel["so_id"];
                    $soNo = $matchedModel["so_no"];
                    $debtorCode = $matchedModel["debtor_code"];
                    $debtorName = $matchedModel["debtor_name"];
                    $date = $matchedModel["date"];

                    $modelColumns = $firstModel ? "
                      <td rowspan=\"" . count($matchedModels) . "\" title=\"$brandName\">$brandName</td>
                      <td rowspan=\"" . count($matchedModels) . "\" title=\"$modelNo\">$modelNo</td>
                      <td rowspan=\"" . count($matchedModels) . "\" class=\"number\">$qty</td>
                    " : "";
                    $soColumns = isset($soNo) ? "
                      <td title=\"$soNo\"><a href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id=$soId\">$soNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$date\">$date</td>
                      <td class=\"outstanding-qty number\" data-so_no=\"$soNo\">0</td>
                      <td class=\"number\">
                        <input type=\"hidden\" name=\"warehouse_code[]\" value=\"$warehouseCode\" />
                        <input type=\"hidden\" name=\"so_no[]\" value=\"$soNo\" />
                        <input type=\"hidden\" name=\"brand_code[]\" value=\"$brandCode\" />
                        <input type=\"hidden\" name=\"model_no[]\" value=\"$modelNo\" />
                        <input
                          type=\"number\"
                          name=\"qty[]\"
                          value=\"0\"
                          min=\"0\"
                          max=\"0\"
                          data-so_no=\"$soNo\"
                          class=\"allot-qty number\"
                          onchange=\"onQtyChange(event, '$warehouseCode', '$brandCode', '$modelNo', '$soNo')\"
                          required
                        />
                      </td>
                    " : "<td colspan=\"5\"></td>";
                    $totalColumn = $firstModel ? "
                      <td rowspan=\"" . count($matchedModels) . "\" class=\"total-model-allot-qty number\">0</td>
                    " : "";

                    echo "
                      <tr class=\"stock-model\" data-brand_code=\"$brandCode\" data-model_no=\"$modelNo\">
                        $modelColumns
                        $soColumns
                        $totalColumn
                      </tr>
                    ";

                    $firstModel = false;
                  }
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
                          <th class=\"total-allot-qty number\"></th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                ";
              } else {
                echo "<div class=\"stock-no-results\">No incoming advice models</div>";
              }
            }
          } else {
            echo "<div class=\"stock-no-results\">No results</div>";
          }
        ?>
      </form>
    </div>
    <script>
      var stockModels = <?php echo json_encode($stockModels); ?>;
      var soModels = <?php echo json_encode($soModels); ?>;
      var allotments = <?php echo json_encode($allotments); ?>;

      function getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var otherWarehouseCodes = Object.keys(allotments).filter(function (i) { return i !== warehouseCode; });

        for (var i = 0; i < otherWarehouseCodes.length; i++) {
          var code = otherWarehouseCodes[i];
          var allotment =
            allotments[code] &&
            allotments[code][brandCode] &&
            allotments[code][brandCode][modelNo] &&
            allotments[code][brandCode][modelNo][soNo];

          if (allotment && allotment["qty"]) {
            totalQty += parseFloat(allotment["qty"]);
          }
        }

        return totalQty;
      }

      function getOtherSoAllottedQty(warehouseCode, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var modelAllotments =
          allotments[warehouseCode] &&
          allotments[warehouseCode][brandCode] &&
          allotments[warehouseCode][brandCode][modelNo] &&
          allotments[warehouseCode][brandCode][modelNo];
        var otherSoNos = Object.keys(modelAllotments).filter(function (i) { return i !== soNo; });

        for (var i = 0; i < otherSoNos.length; i++) {
          var code = otherSoNos[i];
          var allotment =
          allotments[warehouseCode] &&
          allotments[warehouseCode][brandCode] &&
          allotments[warehouseCode][brandCode][modelNo] &&
          allotments[warehouseCode][brandCode][modelNo][code];

          if (allotment && allotment["qty"]) {
            totalQty += parseFloat(allotment["qty"]);
          }
        }

        return totalQty;
      }

      function render() {
        var iaElements = document.querySelectorAll(".stock-results");

        for (var i = 0; i < iaElements.length; i++) {
          var iaElement = iaElements[i];
          var warehouseCode = iaElement.dataset.warehouse_code;

          renderIaTable(warehouseCode);
        }
      }

      function renderIaTable(warehouseCode) {
        var iaModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        for (var j = 0; j < iaModelElements.length; j++) {
          var iaModelElement = iaModelElements[j];
          var brandCode = iaModelElement.dataset.brand_code;
          var modelNo = iaModelElement.dataset.model_no;
          var allotQtyElement = iaModelElement.querySelector(".allot-qty");

          if (allotQtyElement) {
            var soNo = allotQtyElement.dataset.so_no;

            allotments[warehouseCode] = allotments[warehouseCode] || {};
            allotments[warehouseCode][brandCode] = allotments[warehouseCode][brandCode] || {};
            allotments[warehouseCode][brandCode][modelNo] = allotments[warehouseCode][brandCode][modelNo] || {};
            allotments[warehouseCode][brandCode][modelNo][soNo] = allotments[warehouseCode][brandCode][modelNo][soNo] || {};
            allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] || 0;

            renderAllotment(warehouseCode, brandCode, modelNo, soNo);
          }
        }

        renderWarehouseAllotmentSum(warehouseCode);
      }

      function renderAllotment(warehouseCode, brandCode, modelNo, soNo) {
        if (
          allotments[warehouseCode] &&
          allotments[warehouseCode][brandCode] &&
          allotments[warehouseCode][brandCode][modelNo] &&
          allotments[warehouseCode][brandCode][modelNo][soNo]
        ) {
          var iaModelSelector = ".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model[data-brand_code=\"" + brandCode + "\"][data-model_no=\"" + modelNo + "\"]";
          var outstandingQtyElement = document.querySelector(iaModelSelector + " .outstanding-qty[data-so_no=\"" + soNo + "\"]");
          var allotQtyElement = document.querySelector(iaModelSelector + " .allot-qty[data-so_no=\"" + soNo + "\"]");

          var allotment = allotments[warehouseCode][brandCode][modelNo][soNo];
          var allotQty = parseFloat(allotment["qty"]);
          var plNo = allotment["pl_no"] ? allotment["pl_no"] : "";
          var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
          var availableQty = parseFloat(stockModels[warehouseCode][brandCode][modelNo]["qty"]);
          var otherAllotedIaQty = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo);
          var otherAllotedSoQty = getOtherSoAllottedQty(warehouseCode, brandCode, modelNo, soNo);
          var allottableIaQty = availableQty - otherAllotedSoQty;
          var allottableSoQty = outstandingQty - otherAllotedIaQty;
          var maxQty = Math.min(allottableIaQty, allottableSoQty);
          allotQty = Math.min(maxQty, allotQty);

          outstandingQtyElement.innerHTML = allottableSoQty;
          outstandingQtyElement.title = plNo;

          allotQtyElement.max = maxQty;
          allotQtyElement.value = allotQty;
          if (plNo !== "") {
            allotQtyElement.setAttribute("readonly", true);
          } else {
            allotQtyElement.removeAttribute("readonly");
          }
          toggleClass(allotQtyElement, "packed", plNo !== "");

          allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = allotQty;

          var totalModelAllotQtyElement = document.querySelector(iaModelSelector + " .total-model-allot-qty");
          var allotQtyElements = document.querySelectorAll(iaModelSelector + " .allot-qty");
          var totalModelAllotQty = 0;

          for (var i = 0; i < allotQtyElements.length; i++) {
            totalModelAllotQty += parseFloat(allotQtyElements[i].value);
          }

          totalModelAllotQtyElement.innerHTML = totalModelAllotQty;
        }
      }

      function renderWarehouseAllotmentSum(warehouseCode) {
        var warehouseSelector = ".stock-results[data-warehouse_code=\"" + warehouseCode + "\"]";
        var totalAllotQtyElement = document.querySelector(warehouseSelector + " .total-allot-qty");
        var totalModelAllotQtyElements = document.querySelectorAll(warehouseSelector + " .total-model-allot-qty");

        var totolAllotQty = 0;

        for (var i = 0; i < totalModelAllotQtyElements.length; i++) {
          totolAllotQty += parseFloat(totalModelAllotQtyElements[i].innerHTML);
        }

        totalAllotQtyElement.innerHTML = totolAllotQty;
      }

      function onQtyChange(event, warehouseCode, brandCode, modelNo, soNo) {
        allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = event.target.value;

        var soNos = Object.keys(soModels[brandCode][modelNo]);
        var otherSoNos = Object.keys(soModels[brandCode][modelNo]).filter(function (i) { return i !== soNo; });
        var otherIANos = Object.keys(stockModels).filter(function (i) { return i !== warehouseCode; });

        renderAllotment(warehouseCode, brandCode, modelNo, soNo);

        for (var i = 0; i < otherSoNos.length; i++) {
          renderAllotment(warehouseCode, brandCode, modelNo, otherSoNos[i]);
        }

        renderWarehouseAllotmentSum(warehouseCode);

        for (var i = 0; i < otherIANos.length; i++) {
          renderAllotment(otherIANos[i], brandCode, modelNo, soNo);
        }
      }

      function allocateBySoDate(warehouseCode) {
        resetAllotments(warehouseCode);

        var warehouseModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        for (var j = 0; j < warehouseModelElements.length; j++) {
          var warehouseModelElement = warehouseModelElements[j];
          var brandCode = warehouseModelElement.dataset.brand_code;
          var modelNo = warehouseModelElement.dataset.model_no;
          var allotQtyElement = warehouseModelElement.querySelector(".allot-qty");

          if (allotQtyElement) {
            var soNo = allotQtyElement.dataset.so_no;
            var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
            var otherAllotedIaQty = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo);
            var allottableSoQty = outstandingQty - otherAllotedIaQty;

            allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = allottableSoQty;

            renderAllotment(warehouseCode, brandCode, modelNo, soNo);
          }
        }

        render();
      }

      function allocateBySoProportion(warehouseCode) {
        resetAllotments(warehouseCode);

        var warehouseModelElements = document.querySelectorAll(".stock-results[data-warehouse_code=\"" + warehouseCode + "\"] .stock-model");

        for (var i = 0; i < warehouseModelElements.length; i++) {
          var warehouseModelElement = warehouseModelElements[i];
          var brandCode = warehouseModelElement.dataset.brand_code;
          var modelNo = warehouseModelElement.dataset.model_no;
          var availableQty = stockModels[warehouseCode][brandCode][modelNo]["qty"];
          var allotQtyElement = warehouseModelElement.querySelector(".allot-qty");

          if (allotQtyElement) {
            var soNo = allotQtyElement.dataset.so_no;
            var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
            var otherAllotedIaQty = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNo);
            var allottableSoQty = outstandingQty - otherAllotedIaQty;
            var totalModelAllottableQty = 0;
            var soNos = Object.keys(soModels[brandCode][modelNo]);

            for (var j = 0; j < soNos.length; j++) {
              var outstandingQty2 = parseFloat(soModels[brandCode][modelNo][soNos[j]]["qty_outstanding"]);
              var otherAllotedIaQty2 = getOtherWarehouseAllottedQty(warehouseCode, brandCode, modelNo, soNos[j]);
              var allottableSoQty2 = outstandingQty2 - otherAllotedIaQty2;

              totalModelAllottableQty += allottableSoQty2;
            }

            var proportion = allottableSoQty / totalModelAllottableQty;

            var round = soNo !== soNos[soNos.length - 1] ? Math.round : Math.ceil;
            allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = round(proportion * availableQty);

            renderAllotment(warehouseCode, brandCode, modelNo, soNo);
          }
        }

        render();
      }

      function resetAllotments(warehouseCode) {
        var otherIANos = Object.keys(stockModels).filter(function (i) { return i !== warehouseCode; });
        var brandCodes = Object.keys(allotments[warehouseCode]);

        for (var i = 0; i < brandCodes.length; i++) {
          var brandCode = brandCodes[i];
          var modelNos = Object.keys(allotments[warehouseCode][brandCode]);

          for (var j = 0; j < modelNos.length; j++) {
            var modelNo = modelNos[j];
            var soNos = Object.keys(allotments[warehouseCode][brandCode][modelNo]);

            for (var k = 0; k < soNos.length; k++) {
              var soNo = soNos[k];
              allotments[warehouseCode][brandCode][modelNo][soNo]["qty"] = 0;
            }
          }
        }

        render();
      }

      window.onload = function () { render(); }
    </script>
  </body>
</html>
