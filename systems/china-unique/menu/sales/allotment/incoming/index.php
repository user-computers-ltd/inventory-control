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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_ALLOTMENT_INCOMING_TITLE ?></div>
      <form>
        <table id="ia-input">
          <tr>
            <th>DO No.:</th>
            <th>Client:</th>
            <th>SO No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_ia_no[]" multiple  class="web-only">
                <?php
                  foreach ($ias as $ia) {
                    $iaNo = $ia["ia_no"];
                    $selected = assigned($filterIaNos) && in_array($iaNo, $filterIaNos) ? "selected" : "";
                    echo "<option value=\"$iaNo\" $selected>$iaNo</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo assigned($filterIaNos) ? join(", ", $filterIaNos) : "ALL"; ?></span>
            </td>
            <td>
              <select name="filter_debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterDebtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($filterDebtorCodes) {
                    return in_array($i["code"], $filterDebtorCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td>
              <select name="filter_so_no[]" multiple class="web-only">
                <?php
                  foreach ($soNos as $soNo) {
                    $no = $soNo["so_no"];
                    $selected = assigned($filterSONos) && in_array($no, $filterSONos) ? "selected" : "";
                    echo "<option value=\"$no\" $selected>$no</option>";
                  }
                ?>
              </select>
              <span class="print-only"><?php echo assigned($filterSONos) ? join(", ", $filterSONos) : "ALL"; ?></span>
            </td>
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
        <div class="time-generation print-only">Time of generation: <?php echo date("H:i:s d-m-Y"); ?></div>
      </form>
      <?php if (count($iaResults) > 0) : ?>
        <form method="post" class="ia-form">
          <button type="submit" class="web-only">Save</button>
          <?php
            foreach ($iaResults as $creditorCode => $creditor) {
              $creditorName = $creditor["name"];
              $creditorModels = $creditor["models"];

              echo "
                <div class=\"creditor\">
                  <h4>$creditorCode - $creditorName</h4>
              ";

              foreach ($creditorModels as $iaNo => $ia) {
                $date = $ia["date"];
                $models = $ia["models"];

                echo "
                  <div class=\"ia\">
                    <table class=\"ia-header\">
                      <tr>
                        <td>DO No.:</td>
                        <td>$iaNo</td>
                        <td>Date:</td>
                        <td>$date</td>
                      </tr>
                      <tr class=\"web-only\">
                        <td>Reserve:</td>
                        <td><input data-ia_no=\"$iaNo\" type=\"number\" class=\"reserve-percentage\" min=\"0\" max=\"100\" value=\"0\"/><span>%</span></td>
                      </tr>
                      <tr class=\"web-only\">
                        <td colspan=\"4\">
                          <button type=\"button\" class=\"header-button\" onclick=\"allocateByPriorities('$iaNo')\">Allocate by priorities</button>
                          <button type=\"button\" class=\"header-button\" onclick=\"allocateBySoDate('$iaNo')\">Allocate by date</button>
                          <button type=\"button\" class=\"header-button\" onclick=\"allocateBySoProportion('$iaNo')\">Allocate by proportion</button>
                          <button type=\"button\" class=\"header-button\" onclick=\"resetAllotments('$iaNo')\">Reset</button>
                        </td>
                      </tr>
                    </table>
                ";

                if (count($models) > 0) {
                  echo "
                    <table class=\"ia-results\" data-ia_no=\"$iaNo\">
                      <colgroup>
                        <col style=\"width: 70px\">
                        <col>
                        <col style=\"width: 75px\">
                        <col style=\"width: 75px\">
                        <col>
                        <col style=\"width: 70px\">
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
                          <th class=\"number\">On Hand Available Qty</th>
                          <th>SO No.</th>
                          <th>Client</th>
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
                    $qty = $model["qty_available"];
                    $onHandQty = $model["qty_on_hand_available"];
                    $totalQty += $qty;

                    $soNoColumn = "";
                    $debtorNameColumn = "";
                    $dateColumn = "";
                    $outstandingColumn = "";
                    $allotColumn = "";

                    $matchedModels = $soModels[$brandCode][$modelNo];

                    if (isset($matchedModels)) {
                      foreach ($matchedModels as $matchedModel) {
                        $soId = $matchedModel["so_id"];
                        $soNo = $matchedModel["so_no"];
                        $priority = $matchedModel["priority"];
                        $debtorCode = $matchedModel["debtor_code"];
                        $debtorName = $matchedModel["debtor_name"];
                        $date = $matchedModel["date"];

                        $soNoColumn = $soNoColumn . "
                          <div class=\"cell\" title=\"$soNo\" data-so_no=\"$soNo\"><a href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a></div>
                        ";
                        $debtorNameColumn = $debtorNameColumn . "
                          <div class=\"cell\" title=\"$debtorName\" data-so_no=\"$soNo\">$debtorName</div>
                        ";
                        $dateColumn = $dateColumn . "
                          <div class=\"cell\" title=\"$date\" data-so_no=\"$soNo\">$date</div>
                        ";
                        $outstandingColumn = $outstandingColumn . "
                          <div class=\"cell outstanding-qty number\" data-so_no=\"$soNo\">0</div>
                        ";
                        $allotColumn = $allotColumn . "
                        <div class=\"cell number\" data-so_no=\"$soNo\">
                          <input type=\"hidden\" name=\"ia_no[]\" value=\"$iaNo\" />
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
                            data-priority=\"$priority\"
                            data-date=\"$date\"
                            class=\"allot-qty number\"
                            onchange=\"onQtyChange(event, '$iaNo', '$brandCode', '$modelNo', '$soNo')\"
                            required
                          />
                        </div>
                        ";
                      }
                    }

                    echo "
                      <tr
                        class=\"ia-model\"
                        data-brand_code=\"$brandCode\"
                        data-model_no=\"$modelNo\"
                      >
                        <td title=\"$brandName\">$brandName</td>
                        <td title=\"$modelNo\">$modelNo</td>
                        <td class=\"number\">$qty</td>
                        <td class=\"number\">$onHandQty</td>
                        <td>$soNoColumn</td>
                        <td>$debtorNameColumn</td>
                        <td>$dateColumn</td>
                        <td>$outstandingColumn</td>
                        <td>$allotColumn</td>
                        <td class=\"total-model-allot-qty number\">0</td>
                      </tr>
                    ";
                  }

                  echo "
                        <tr>
                          <th></th>
                          <th class=\"number\">Total:</th>
                          <th class=\"number total-qty\">$totalQty</th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th class=\"total-allot-qty number\"></th>
                        </tr>
                        <tr>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th></th>
                          <th class=\"total-allot-qty-p number\"></th>
                        </tr>
                      </tbody>
                    </table>
                  ";
                } else {
                  echo "<div class=\"ia-no-results\">No incoming advice models</div>";
                }

                echo "</div>";
              }

              echo "</div>";
            }
          ?>
        </form>
      <?php else : ?>
        <div class="ia-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      var iaModels = <?php echo json_encode($iaModels); ?>;
      var soModels = <?php echo json_encode($soModels); ?>;
      var allotments = <?php echo json_encode($allotments); ?>;

      function getOtherIaAllottedQty(iaNo, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var otherIaNos = Object.keys(allotments).filter(function (i) { return i !== iaNo; });

        for (var i = 0; i < otherIaNos.length; i++) {
          var code = otherIaNos[i];
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

      function getOtherSoAllottedQty(iaNo, brandCode, modelNo, soNo) {
        var totalQty = 0;
        var modelAllotments =
          allotments[iaNo] &&
          allotments[iaNo][brandCode] &&
          allotments[iaNo][brandCode][modelNo] &&
          allotments[iaNo][brandCode][modelNo];
        var otherSoNos = Object.keys(modelAllotments).filter(function (i) { return i !== soNo; });

        for (var i = 0; i < otherSoNos.length; i++) {
          var code = otherSoNos[i];
          var allotment =
            allotments[iaNo] &&
            allotments[iaNo][brandCode] &&
            allotments[iaNo][brandCode][modelNo] &&
            allotments[iaNo][brandCode][modelNo][code];

          if (allotment && allotment["qty"]) {
            totalQty += parseFloat(allotment["qty"]);
          }
        }

        return totalQty;
      }

      function render() {
        var iaElements = document.querySelectorAll(".ia-results");

        for (var i = 0; i < iaElements.length; i++) {
          var iaElement = iaElements[i];
          var iaNo = iaElement.dataset.ia_no;

          renderIaTable(iaNo);
        }
      }

      function renderIaTable(iaNo) {
        var iaModelElements = document.querySelectorAll(".ia-results[data-ia_no=\"" + iaNo + "\"] .ia-model");

        for (var j = 0; j < iaModelElements.length; j++) {
          var iaModelElement = iaModelElements[j];
          var brandCode = iaModelElement.dataset.brand_code;
          var modelNo = iaModelElement.dataset.model_no;

          var allotQtyElements = iaModelElement.querySelectorAll(".allot-qty");

          for (var i = 0; i < allotQtyElements.length; i++) {
            var allotQtyElement = allotQtyElements[i];
            var soNo = allotQtyElement.dataset.so_no;

            allotments[iaNo] = allotments[iaNo] || {};
            allotments[iaNo][brandCode] = allotments[iaNo][brandCode] || {};
            allotments[iaNo][brandCode][modelNo] = allotments[iaNo][brandCode][modelNo] || {};
            allotments[iaNo][brandCode][modelNo][soNo] = allotments[iaNo][brandCode][modelNo][soNo] || {};

            var allotment = allotments[iaNo][brandCode][modelNo][soNo];
            allotment["do_no"] = allotment["do_no"] || "";
            allotment["qty"] = allotment["qty"] || 0;

            renderAllotment(iaNo, brandCode, modelNo, soNo);
          }
        }

        renderIaAllotmentSum(iaNo);
      }

      function renderAllotment(iaNo, brandCode, modelNo, soNo) {
        if (
          allotments[iaNo] &&
          allotments[iaNo][brandCode] &&
          allotments[iaNo][brandCode][modelNo] &&
          allotments[iaNo][brandCode][modelNo][soNo]
        ) {
          var reservePercentage = document.querySelector("input.reserve-percentage[data-ia_no=\"" + iaNo + "\"]").value || 0;
          var iaSelector = ".ia-results[data-ia_no=\"" + iaNo + "\"]";
          var iaModelSelector = iaSelector + " .ia-model[data-brand_code=\"" + brandCode + "\"][data-model_no=\"" + modelNo + "\"]";
          var allotmentElements = document.querySelectorAll(iaModelSelector + " .cell[data-so_no=\"" + soNo + "\"]");
          var outstandingQtyElement = document.querySelector(iaModelSelector + " .outstanding-qty[data-so_no=\"" + soNo + "\"]");
          var allotQtyElement = document.querySelector(iaModelSelector + " .allot-qty[data-so_no=\"" + soNo + "\"]");
          var allotment = allotments[iaNo][brandCode][modelNo][soNo];
          var allotQty = parseFloat(allotment["qty"]);
          var doNo = allotment["do_no"] ? allotment["do_no"] : "";
          var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
          var availableQty = Math.floor(parseFloat(iaModels[iaNo][brandCode][modelNo]["qty"]) * (1 - reservePercentage / 100));
          var otherAllotedIaQty = getOtherIaAllottedQty(iaNo, brandCode, modelNo, soNo);
          var otherAllotedSoQty = getOtherSoAllottedQty(iaNo, brandCode, modelNo, soNo);
          var allottableIaQty = availableQty - otherAllotedSoQty;
          var allottableSoQty = outstandingQty - otherAllotedIaQty;
          var maxQty = Math.min(allottableIaQty, allottableSoQty);
          allotQty = Math.min(maxQty, allotQty);

          outstandingQtyElement.innerHTML = allottableSoQty;

          for (var i = 0; i < allotmentElements.length; i++) {
            toggleClass(allotmentElements[i], "hide", allottableSoQty === 0);
          }

          allotQtyElement.max = maxQty;
          allotQtyElement.value = allotQty;

          if (doNo !== "") {
            allotQtyElement.setAttribute("readonly", true);
            allotQtyElement.title = doNo;
          } else {
            allotQtyElement.removeAttribute("readonly");
          }

          toggleClass(allotQtyElement, "packed", doNo !== "");

          allotments[iaNo][brandCode][modelNo][soNo]["qty"] = allotQty;

          var totalModelAllotQtyElement = document.querySelector(iaModelSelector + " .total-model-allot-qty");
          var allotQtyElements = document.querySelectorAll(iaModelSelector + " .allot-qty");
          var totalModelAllotQty = 0;

          for (var i = 0; i < allotQtyElements.length; i++) {
            totalModelAllotQty += parseFloat(allotQtyElements[i].value);
          }

          totalModelAllotQtyElement.innerHTML = totalModelAllotQty;
        }
      }

      function renderIaAllotmentSum(iaNo) {
        var iaSelector = ".ia-results[data-ia_no=\"" + iaNo + "\"]";
        var totalQtyElement = document.querySelector(iaSelector + " .total-qty");
        var totalAllotQtyElement = document.querySelector(iaSelector + " .total-allot-qty");
        var totalAllotQtyPElement = document.querySelector(iaSelector + " .total-allot-qty-p");
        var totalModelAllotQtyElements = document.querySelectorAll(iaSelector + " .total-model-allot-qty");
        var totalQty = totalQtyElement.innerHTML;
        var totalAllotQty = 0;

        for (var i = 0; i < totalModelAllotQtyElements.length; i++) {
          totalAllotQty += parseFloat(totalModelAllotQtyElements[i].innerHTML);
        }

        totalAllotQtyElement.innerHTML = totalAllotQty;
        totalAllotQtyPElement.innerHTML = "(" + (totalAllotQty / totalQty * 100).toFixed(2) + "%)";
      }

      function onQtyChange(event, iaNo, brandCode, modelNo, soNo) {
        allotments[iaNo][brandCode][modelNo][soNo]["qty"] = event.target.value;

        var soNos = Object.keys(soModels[brandCode][modelNo]);
        var otherSoNos = Object.keys(soModels[brandCode][modelNo]).filter(function (i) { return i !== soNo; });
        var otherIANos = Object.keys(iaModels).filter(function (i) { return i !== iaNo; });

        renderAllotment(iaNo, brandCode, modelNo, soNo);

        for (var i = 0; i < otherSoNos.length; i++) {
          renderAllotment(iaNo, brandCode, modelNo, otherSoNos[i]);
        }

        renderIaAllotmentSum(iaNo);

        for (var i = 0; i < otherIANos.length; i++) {
          renderAllotment(otherIANos[i], brandCode, modelNo, soNo);
        }
      }

      function allocateModels(iaNo, elements, sorting) {
        for (var i = 0; i < elements.length; i++) {
          var element = elements[i];
          var brandCode = element.dataset.brand_code;
          var modelNo = element.dataset.model_no;
          var allotQtyElements = element.querySelectorAll(".allot-qty");

          var allotQtyElementList = [];

          for (var j = 0; j < allotQtyElements.length; j++) {
            allotQtyElementList.push(allotQtyElements[j]);
          }

          allotQtyElementList.sort(sorting);

          for (var k = 0; k < allotQtyElementList.length; k++) {
            var allotQtyElement = allotQtyElementList[k];

            var soNo = allotQtyElement.dataset.so_no;
            var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
            var otherAllotedIaQty = getOtherIaAllottedQty(iaNo, brandCode, modelNo, soNo);
            var allottableSoQty = outstandingQty - otherAllotedIaQty;

            allotments[iaNo][brandCode][modelNo][soNo]["qty"] = allottableSoQty;

            renderAllotment(iaNo, brandCode, modelNo, soNo);
          }
        }
      }

      function allocateByPriorities(iaNo) {
        resetAllotments(iaNo);

        var iaModelElements = document.querySelectorAll(".ia-results[data-ia_no=\"" + iaNo + "\"] .ia-model");

        allocateModels(iaNo, iaModelElements, function (a, b) {
          return b.dataset.priority - a.dataset.priority;
        });

        render();
      }

      function allocateBySoDate(iaNo) {
        resetAllotments(iaNo);

        var iaModelElements = document.querySelectorAll(".ia-results[data-ia_no=\"" + iaNo + "\"] .ia-model");

        allocateModels(iaNo, iaModelElements, function (a, b) {
          return getTime(a.dataset.date) - getTime(b.dataset.date);
        });

        render();
      }

      function allocateBySoProportion(iaNo) {
        resetAllotments(iaNo);

        var iaModelElements = document.querySelectorAll(".ia-results[data-ia_no=\"" + iaNo + "\"] .ia-model");
        var reservePercentage = document.querySelector("input.reserve-percentage[data-ia_no=\"" + iaNo + "\"]").value || 0;

        for (var i = 0; i < iaModelElements.length; i++) {
          var iaModelElement = iaModelElements[i];
          var brandCode = iaModelElement.dataset.brand_code;
          var modelNo = iaModelElement.dataset.model_no;
          var availableQty = iaModels[iaNo][brandCode][modelNo]["qty"] * (1 - reservePercentage / 100);
          var allotQtyElements = iaModelElement.querySelectorAll(".allot-qty");

          for (var j = 0; j < allotQtyElements.length; j++) {
            var allotQtyElement = allotQtyElements[j];

            var soNo = allotQtyElement.dataset.so_no;

            var outstandingQty = parseFloat(soModels[brandCode][modelNo][soNo]["qty_outstanding"]);
            var otherAllotedIaQty = getOtherIaAllottedQty(iaNo, brandCode, modelNo, soNo);
            var allottableSoQty = outstandingQty - otherAllotedIaQty;
            var totalModelAllottableQty = 0;
            var soNos = Object.keys(soModels[brandCode][modelNo]);

            for (var k = 0; k < soNos.length; k++) {
              var outstandingQty2 = parseFloat(soModels[brandCode][modelNo][soNos[k]]["qty_outstanding"]);
              var otherAllotedIaQty2 = getOtherIaAllottedQty(iaNo, brandCode, modelNo, soNos[k]);
              var allottableSoQty2 = outstandingQty2 - otherAllotedIaQty2;

              totalModelAllottableQty += allottableSoQty2;
            }

            var proportion = allottableSoQty / totalModelAllottableQty;

            var round = j % 2 === 1 ? Math.floor : Math.ceil;
            allotments[iaNo][brandCode][modelNo][soNo]["qty"] = round(proportion * availableQty);

            renderAllotment(iaNo, brandCode, modelNo, soNo);
          }
        }

        render();
      }

      function resetAllotments(iaNo) {
        var iaModelElements = document.querySelectorAll(".ia-results[data-ia_no=\"" + iaNo + "\"] .ia-model");

        for (var i = 0; i < iaModelElements.length; i++) {
          var iaModelElement = iaModelElements[i];
          var brandCode = iaModelElement.dataset.brand_code;
          var modelNo = iaModelElement.dataset.model_no;
          var allotQtyElements = iaModelElement.querySelectorAll(".allot-qty");

          for (var j = 0; j < allotQtyElements.length; j++) {
            var allotQtyElement = allotQtyElements[j];

            var soNo = allotQtyElement.dataset.so_no;
            var allotment = allotments[iaNo][brandCode][modelNo][soNo];

            if (allotment["do_no"] === "") {
              allotment["qty"] = 0;
            }
          }
        }

        render();
      }

      window.addEventListener("load", function () {
        var iaForms = document.querySelectorAll(".ia-form");

        for (var i = 0; i < iaForms.length; i++) {
          iaForms[i].reset();
        }

        render();
      });
    </script>
  </body>
</html>
