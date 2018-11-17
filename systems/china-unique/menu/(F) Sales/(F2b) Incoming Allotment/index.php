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
        <table id="ia-input">
          <colgroup>
            <col style="width: 100px">
          </colgroup>
          <tr>
            <td><label>IA No.:</label></td>
            <td>
              <select name="filter_ia_no[]" multiple>
                <?php
                  foreach ($ias as $ia) {
                    $iaNo = $ia["ia_no"];
                    $selected = assigned($filterIaNos) && in_array($iaNo, $filterIaNos) ? "selected" : "";
                    echo "<option value=\"$iaNo\" $selected>$iaNo</option>";
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
          if (count($iaModels) > 0) {
            echo "<button type=\"submit\">Save</button>";

            foreach ($iaModels as $iaNo => $ia) {
              $date = $ia["date"];
              $models = $ia["models"];

              echo "
                <div class=\"ia\">
                  <table class=\"ia-header\">
                    <tr>
                      <td>IA No.:</td>
                      <td>$iaNo<button type=\"button\" class=\"header-button\" onclick=\"primilaryAllocate('$iaNo')\">Primilary Allocate</button></td>
                      <td>Date:</td>
                      <td>$date</td>
                    </tr>
                  </table>
              ";

              if (count($models) > 0) {
                echo "
                  <table class=\"ia-results\" data-ia_no=\"$iaNo\">
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
                        <th class=\"number\">Selling Price (Inc. Disc)</th>
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
                  $qty = $model["qty_available"];
                  $totalQty += $qty;

                  echo "
                    <tr class=\"ia-model\" data-brand_code=\"$brandCode\" data-model_no=\"$modelNo\" data-qty_available=\"$qty\">
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
                      <td><button type=\"button\" class=\"action-button add\" onclick=\"addAllotment('$iaNo', '$brandCode', '$modelNo')\"></button></td>
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
                echo "<div class=\"ia-no-results\">No incoming advice models</div>";
              }
            }
          } else if (!$hasFilter) {
            echo "<div class=\"ia-no-results\">Please select an incoming advice no.</div>";
          } else {
            echo "<div class=\"ia-no-results\">No results</div>";
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

      var iaTableElements = document.querySelectorAll(".ia-results");

      function getIARowElement(iaNo, brandCode, modelNo) {
        return document.querySelector(".ia-results[data-ia_no=\"" + iaNo + "\"] tr.ia-model[data-brand_code=\"" + brandCode + "\"][data-model_no=\"" + modelNo + "\"]");
      }

      function renderIASum(iaNo) {
        var iaTableElement = document.querySelector(".ia-results[data-ia_no=\"" + iaNo + "\"]");
        var iaTotalAllotQtyElement = iaTableElement.querySelector(".total-allot-qty");
        var iaRowElements = iaTableElement.querySelectorAll("tr.ia-model");

        var totolAllotQty = 0;

        for (var i = 0; i < iaRowElements.length; i++) {
          var iaRowElement = iaRowElements[i];
          totolAllotQty += parseFloat(iaRowElement.querySelector(".allot-qty-sum").innerHTML);
        }

        iaTotalAllotQtyElement.innerHTML = totolAllotQty;
      }

      function renderAllotment(iaNo, brandCode, modelNo) {
        var iaRowElement = getIARowElement(iaNo, brandCode, modelNo);
        var customerElement = iaRowElement.querySelector(".customer");
        var soNoElement = iaRowElement.querySelector(".so-no");
        var sellingPriceElement = iaRowElement.querySelector(".selling-price");
        var outstandingQtyElement = iaRowElement.querySelector(".outstanding-qty");
        var allotQtyElement = iaRowElement.querySelector(".allot-qty");
        var allotQtySumElement = iaRowElement.querySelector(".allot-qty-sum");
        var removeElement = iaRowElement.querySelector(".remove-allotment");
        var qtyAvailable = parseFloat(iaRowElement.dataset.qty_available);
        var modelAllotments = (allotments[iaNo] && allotments[iaNo][brandCode] && allotments[iaNo][brandCode][modelNo]) || [];
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
              "<input type=\"hidden\" name=\"ia_no[]\" value=\"" + iaNo + "\" />"
            + "<input type=\"hidden\" name=\"brand_code[]\" value=\"" + brandCode + "\" />"
            + "<input type=\"hidden\" name=\"model_no[]\" value=\"" + modelNo + "\" />"
            + "<select name=\"so_no[]\" onchange=\"onSoNoChange(event, '" + iaNo + "', '" + brandCode + "', '" + modelNo + "', " + i + ")\" required>"
            + "  <option value=\"\"></option>";

          for (var j = 0; j < availableSoModels.length; j++) {
            var soModel = availableSoModels[j];
            var soNo = soModel["so_no"];
            var otherAllottedQty = getOtherAllottedQty(iaNo, brandCode, modelNo, soNo);
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
          allotQtyElement.innerHTML += "<input class=\"number\" type=\"number\" value=\"" + allotment["qty"] + "\" min=\"0\" max=\"" + maxQty + "\" name=\"qty[]\" onchange=\"onQtyChange(event, '" + iaNo + "', '" + brandCode + "', '" + modelNo + "', " + i + ")\" required />";
          removeElement.innerHTML += "<button type=\"button\" class=\"action-button remove\" onclick=\"removeAllotment('" + iaNo + "', '" + brandCode + "', '" + modelNo + "', " + i + ")\"></button>";

          allotQtySum += parseFloat(allotment["qty"]);
        }

        allotQtySumElement.innerHTML = allotQtySum;
      }

      function getOtherAllottedQty(iaNo, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var otherIANos = Object.keys(allotments).filter(function (i) { return i !== iaNo; });

        for (var i = 0; i < otherIANos.length; i++) {
          var code = otherIANos[i];
          var modelAllotments = (allotments[code] && allotments[code][brandCode] && allotments[code][brandCode][modelNo]) || [];
          modelAllotments = modelAllotments.filter(function (allotment) { return allotment["so_no"] === soNo; });

          for (var j = 0; j < modelAllotments.length; j++) {
            totalQty += parseFloat(modelAllotments[j]["qty"]);
          }
        }

        return totalQty;
      }

      function updateAllotmentSoNo(iaNo, brandCode, modelNo, index, soNo = "") {
        var modelAllotments = allotments[iaNo][brandCode][modelNo];
        var allotment = modelAllotments[index];
        var availableSoModels = (soModels[brandCode] && soModels[brandCode][modelNo]) || [];
        var matchedSoModel = availableSoModels.filter(function (model) { return soNo && (model["so_no"] === soNo); })[0];
        var otherAllottedQty = getOtherAllottedQty(iaNo, brandCode, modelNo, soNo);

        allotment["so_no"] = soNo;
        allotment["debtor_name"] = (matchedSoModel && matchedSoModel["debtor_name"]) || "";
        allotment["selling_price"] = (matchedSoModel && parseFloat(matchedSoModel["selling_price"])) || 0;
        allotment["qty_outstanding"] = (matchedSoModel && parseFloat(matchedSoModel["qty_outstanding"]) - otherAllottedQty) || 0;
        allotment["qty"] = 0;
      }

      function updateAllotmentQty(iaNo, brandCode, modelNo, index, qty = 0) {
        var modelAllotments = allotments[iaNo][brandCode][modelNo];
        var allotment = modelAllotments[index];
        var qtyAvailable = parseFloat(getIARowElement(iaNo, brandCode, modelNo).dataset.qty_available);
        var allotedQty = modelAllotments.filter(function(_, k) { return index !== k; }).reduce(function (sum, a) { return sum + parseFloat(a["qty"]); }, 0);

        allotment["qty"] = Math.min(qtyAvailable - allotedQty, allotment["qty_outstanding"], qty);
      }

      function addAllotment(iaNo, brandCode, modelNo) {
        allotments[iaNo] = allotments[iaNo] || {};
        allotments[iaNo][brandCode] = allotments[iaNo][brandCode] || {};
        allotments[iaNo][brandCode][modelNo] = allotments[iaNo][brandCode][modelNo] || [];

        var models = allotments[iaNo][brandCode][modelNo];

        models.push({});

        updateAllotmentSoNo(iaNo, brandCode, modelNo, models.length - 1);
        updateAllotmentQty(iaNo, brandCode, modelNo, models.length - 1);
        renderAllotment(iaNo, brandCode, modelNo);
        renderIASum(iaNo);
      }

      function removeAllotment(iaNo, brandCode, modelNo, index) {
        var modelAllotments = allotments[iaNo][brandCode][modelNo];

        modelAllotments.splice(index, 1);

        renderAllotment(iaNo, brandCode, modelNo);
        renderIASum(iaNo);
      }

      function onSoNoChange(event, iaNo, brandCode, modelNo, index) {
        updateAllotmentSoNo(iaNo, brandCode, modelNo, index, event.target.value);
        renderAllotment(iaNo, brandCode, modelNo);
        renderIASum(iaNo);
      }

      function onQtyChange(event, iaNo, brandCode, modelNo, index) {
        updateAllotmentQty(iaNo, brandCode, modelNo, index, parseFloat(event.target.value));
        renderAllotment(iaNo, brandCode, modelNo);
        renderIASum(iaNo);

        var otherIANos = Object.keys(allotments).filter(function (i) { return i !== iaNo; });

        for (var i = 0; i < otherIANos.length; i++) {
          renderAllotment(otherIANos[i], brandCode, modelNo);
        }
      }

      function primilaryAllocate(iaNo) {
        var iaTableElement = document.querySelector(".ia-results[data-ia_no=\"" + iaNo + "\"]");
        var iaRowElements = iaTableElement.querySelectorAll("tr.ia-model");

        for (var i = 0; i < iaRowElements.length; i++) {
          var iaRowElement = iaRowElements[i];
          var brandCode = iaRowElement.dataset.brand_code;
          var modelNo = iaRowElement.dataset.model_no;
          var qtyAvailable = iaRowElement.dataset.qty_available;
          var availableSoModels = (soModels[brandCode] && soModels[brandCode][modelNo]) || [];

          allotments[iaNo] = allotments[iaNo] || {};
          allotments[iaNo][brandCode] = allotments[iaNo][brandCode] || {};
          allotments[iaNo][brandCode][modelNo] = [];
          var models = allotments[iaNo][brandCode][modelNo];

          for (var j = 0; j < availableSoModels.length; j++) {
            var soModel = availableSoModels[j];
            var soNo = soModel["so_no"];
            var otherAllottedQty = getOtherAllottedQty(iaNo, brandCode, modelNo, soNo);
            var qtyOutstanding = parseFloat(soModel["qty_outstanding"]) - otherAllottedQty;
            var qty = Math.min(qtyAvailable, qtyOutstanding);

            if (qty > 0) {
              models.push({});

              updateAllotmentSoNo(iaNo, brandCode, modelNo, models.length - 1, soNo);
              updateAllotmentQty(iaNo, brandCode, modelNo, models.length - 1, qty);

              qtyAvailable -= qty;
            }
          }

          renderAllotment(iaNo, brandCode, modelNo);
        }

        renderIASum(iaNo);
      }

      window.onload = function () {
        for (var i = 0; i < iaTableElements.length; i++) {
          var iaNo = iaTableElements[i].dataset.ia_no;
          var iaRowElements = iaTableElements[i].querySelectorAll("tr.ia-model");

          for (var j = 0; j < iaRowElements.length; j++) {
            var iaRowElement = iaRowElements[j];
            var brandCode = iaRowElement.dataset.brand_code;
            var modelNo = iaRowElement.dataset.model_no;
            var modelAllotments = (allotments[iaNo] && allotments[iaNo][brandCode] && allotments[iaNo][brandCode][modelNo]) || [];

            for (var k = 0; k < modelAllotments.length; k++) {
              var soNo = modelAllotments[k]["so_no"];
              var qty = modelAllotments[k]["qty"];

              updateAllotmentSoNo(iaNo, brandCode, modelNo, k, soNo);
              updateAllotmentQty(iaNo, brandCode, modelNo, k, qty);
            }

            renderAllotment(iaNo, brandCode, modelNo);
          }

          renderIASum(iaNo);
        }
      }
    </script>
  </body>
</html>
