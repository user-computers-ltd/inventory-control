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
      <div class="headline"><?php echo SALES_ALLOTMENT_REPORT_CUSTOMER_TITLE ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
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
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
      </form>
      <?php
        if (count($allotments) > 0) {
          foreach ($allotments as $debtorCode => $debtorAllotments) {
            $debtorName = $debtorAllotments["name"];
            $debtorAllotmentModels = $debtorAllotments["models"];

            echo "<div class=\"so-client\"><h4>$debtorCode - $debtorName</h4>";

            foreach ($debtorAllotmentModels as $currencyCode => $currencyAllotments) {
              $exchangeRate = $currencyAllotments["rate"];
              $currencyAllotmentModels = $currencyAllotments["models"];

              foreach ($currencyAllotmentModels as $discount => $discountAllotments) {
                $discountFactor = (100 - $discount) / 100;

                foreach ($discountAllotments as $tax => $taxAllotments) {
                  $taxFactor = (100 + $tax) / 100;

                  foreach ($taxAllotments as $warehouseCode => $warehouseAllotments) {

                    foreach ($warehouseAllotments as $doNo => $do) {
                      $doId = $do["id"];
                      $doAllotments = $do["models"];
                      $option = "";

                      if ($doId == "") {
                        $option = "
                          <button name=\"action\" type=\"submit\" value=\"create\" style=\"display: none\"></button>
                          <button type=\"button\" onclick=\"confirmCreate(event)\" class=\"web-only\">Create Delivery Order</button>
                          <button name=\"action\" type=\"submit\" value=\"delete\" style=\"display: none\"></button>
                          <button type=\"button\" onclick=\"confirmDelete(event)\" class=\"web-only\">Delete Allotments</button>
                        ";
                      } else {
                        $option = "
                          <span class=\"delivery-order\">
                            Delivery Order: <a href=\"" . SALES_DELIVERY_ORDER_URL . "?id=$doId\">$doNo</a>
                          </span>
                        ";
                      }
                      echo "
                        <form method=\"post\">
                          <div class=\"so-client-header\">
                            $option
                            <span class=\"currency\">$currencyCode @ $exchangeRate</span>
                            <span class=\"discount\">Discount: $discount%</span>
                            <span class=\"warehouse\">Warehouse: $warehouseCode</span>
                            <input name=\"debtor_code\" value=\"$debtorCode\" hidden />
                            <input name=\"currency_code\" value=\"$currencyCode\" hidden />
                            <input name=\"exchange_rate\" value=\"$exchangeRate\" hidden />
                            <input name=\"discount\" value=\"$discount\" hidden />
                            <input name=\"tax\" value=\"$tax\" hidden />
                            <input name=\"warehouse_code\" value=\"$warehouseCode\" hidden />
                          </div>
                      ";

                      if (count($doAllotments) > 0) {
                        echo "
                          <table class=\"so-client-results\">
                            <colgroup>
                              <col class=\"web-only\" style=\"width: 30px\">
                              <col style=\"width: 80px\">
                              <col style=\"width: 70px\">
                              <col style=\"width: 120px\">
                              <col>
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                            </colgroup>
                            <thead>
                              <tr></tr>
                              <tr>
                                <th class=\"web-only\">
                                  " . ($doId == "" ? "<input
                                    type=\"checkbox\"
                                    onchange=\"disableAllAllotments(event)\"
                                    checked
                                  />" : "") . "
                                </th>
                                <th>DO No. / On Hand</th>
                                <th>Brand</th>
                                <th>Model No.</th>
                                <th>Order No.</th>
                                <th class=\"number\">Outstanding Qty</th>
                                <th class=\"number\">Allotted Qty</th>
                                <th class=\"number\">Price</th>
                                <th class=\"number\">Allotted Subtotal</th>
                              </tr>
                            </thead>
                            <tbody>
                        ";

                        $totalOutstanding = 0;
                        $totalAllottedQty = 0;
                        $totalAmt = 0;

                        $d = 0;
                        foreach ($doAllotments as $models) {

                          for ($i = 0; $i < count($models); $i++) {
                            $allotment = $models[$i];
                            $iaNo = $allotment["ia_no"];
                            $soId = $allotment["so_id"];
                            $soNo = $allotment["so_no"];
                            $brandCode = $allotment["brand_code"];
                            $brandName = $allotment["brand_name"];
                            $modelNo = $allotment["model_no"];
                            $price = $allotment["price"];
                            $outstandingQty = $allotment["outstanding_qty"];
                            $qty = $allotment["qty"];
                            $iaNo = $allotment["ia_no"];
                            $warehouseCode = $allotment["warehouse_code"];
                            $subTotal = $price * $qty;
                            $costAverage = $allotment["cost_average"];
                            $totalCost = $costAverage * $qty;
                            $removeIndex = $d * count($models) + $i;
                            $totalOutstanding += $outstandingQty;
                            $totalAllottedQty += $qty;
                            $totalAmt += $subTotal;

                            $checkboxColumn = $doId == "" ? "
                              <td class=\"web-only\">
                                <input
                                  type=\"checkbox\"
                                  onchange=\"disableAllotment(event)\"
                                  data-ia_no=\"$iaNo\"
                                  data-so_no=\"$soNo\"
                                  data-brand_code=\"$brandCode\"
                                  data-model_no=\"$modelNo\"
                                  data-price=\"$price\"
                                  data-qty=\"$qty\"
                                  checked
                                />
                              </td>
                            " : "<td class=\"web-only\"></td>";

                            $sourceColumn = assigned($iaNo) ? "
                              <td><a class=\"link\">$iaNo</a></td>
                            " : "<td>On Hand</td>";

                            $modelColumns = $i == 0 ? "
                              <td rowspan=\"" . count($models) . "\" title=\"$brandName\">
                                $brandName
                              </td>
                              <td rowspan=\"" . count($models) . "\" title=\"$modelNo\">
                                $modelNo
                              </td>
                              <td rowspan=\"" . count($models) . "\" title=\"$soNo\">
                                <a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a>
                              </td>
                              <td rowspan=\"" . count($models) . "\" class=\"number\" title=\"$outstandingQty\">
                              " . number_format($outstandingQty) . "
                              </td>
                            " : "";

                            echo "
                              <tr>
                                $checkboxColumn
                                $sourceColumn
                                $modelColumns
                                <td class=\"number\">
                                  " . number_format($qty) . "
                                  <input type=\"hidden\" name=\"ia_no[]\" value=\"$iaNo\" />
                                  <input type=\"hidden\" name=\"so_no[]\" value=\"$soNo\" />
                                  <input type=\"hidden\" name=\"brand_code[]\" value=\"$brandCode\" />
                                  <input type=\"hidden\" name=\"model_no[]\" value=\"$modelNo\" />
                                  <input type=\"hidden\" name=\"price[]\" value=\"$price\" />
                                  <input type=\"hidden\" name=\"qty[]\" value=\"$qty\" />
                                </td>
                                <td class=\"number\">" . number_format($price, 2) . "</td>
                                <td class=\"number\">" . number_format($subTotal, 2) . "</td>
                              </tr>
                            ";
                          }

                          $d++;
                        }

                        if ($discount > 0) {
                          echo "
                            <tr>
                              <th class=\"web-only\"></th>
                              <th></th>
                              <th></th>
                              <th></th>
                              <th></th>
                              <th></th>
                              <th></th>
                              <th></th>
                              <th class=\"number\">" . number_format($totalAmt, 2) . "</th>
                            </tr>
                            <tr>
                              <th class=\"web-only\"></th>
                              <th></th>
                              <th></th>
                              <th></th>
                              <td></td>
                              <td></td>
                              <td></td>
                              <td class=\"number\">Disc. $discount%</td>
                              <td class=\"number\">" . number_format($totalAmt * $discount / 100, 2) . "</td>
                            </tr>
                          ";
                        }

                        echo "
                              <tr>
                                <th class=\"web-only\"></th>
                                <th></th>
                                <th></th>
                                <th></th>
                                <th class=\"number\">Total:</th>
                                <th class=\"number\">" . number_format($totalOutstanding) . "</th>
                                <th class=\"number\">" . number_format($totalAllottedQty) . "</th>
                                <th></th>
                                <th class=\"number\">" . number_format($totalAmt * $discountFactor, 2) . "</th>
                              </tr>
                            </tbody>
                          </table>
                        ";
                      } else {
                        echo "<div class=\"so-client-no-results\">No sales details</div>";
                      }

                      echo "</form>";
                    }
                  }
                }
              }
            }

            echo "</div>";
          }
        } else {
          echo "<div class=\"so-client-no-results\">No results</div>";
        }
      ?>
    </div>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/loading-screen/index.php"; ?>
    <script>
      function generateItems(formElement, deliveryOrder = true) {
        var discount = formElement.querySelector("input[name=\"discount\"]").value;
        var checkedItems = formElement.querySelectorAll("td input[type=\"checkbox\"]:checked");
        var listElement = "";

        if (checkedItems.length > 0) {
          listElement += "<table class=\"so-client-results\" style=\"width: 800px\"><thead><tr><th>DO No. / On Hand</th><th>Brand</th><th>Model No.</th><th>SO No.</th><th class=\"number\">Price</th><th class=\"number\">Quantity</th><th class=\"number\">Subtotal</th></thead>";
          var totalQty = 0;
          var total = 0;

          for (var i = 0; i < checkedItems.length; i++) {
            var dataset = checkedItems[i].dataset;
            var status = dataset["ia_no"] ?  dataset["ia_no"] : "On Hand";
            var brand = dataset["brand_code"];
            var model = dataset["model_no"];
            var soNo = dataset["so_no"];
            var price = dataset["price"] || 0;
            var qty = dataset["qty"];
            var subtotal = price * qty;

            listElement += "<tr><td>" + status + "</td><td>" + brand + "</td><td>" + model + "</td><td>" + soNo + "</td><td class=\"number\">" + price + "</td><td class=\"number\">" + qty + "</td><td class=\"number\">" + subtotal.toFixed(2) + "</td></tr>";

            totalQty += parseInt(qty, 10);
            total += subtotal;
          }

          if (deliveryOrder) {
            var discountAmount = total * discount / 100;
            var grandtotal = total - discountAmount;

            if (discountAmount > 0) {
              listElement += "<tr><th></th><th></th><th></th><th></th><th></th><th></th><th class=\"number\">" + total.toFixed(2) + "</th></tr>";
              listElement += "<tr><th></th><th></th><th></th><th></th><th></th><td class=\"number\">Discount: " + discount + "%</td><td class=\"number\">" + discountAmount.toFixed(2) + "</td></tr>";
            }

            listElement += "<tr><th></th><th></th><th></th><th></th><th class=\"number\">Total:</th><th class=\"number\">" + totalQty + "</th><th class=\"number\">" + grandtotal.toFixed(2) + "</th></tr>";
          }

          listElement += "</table>";
        }

        return listElement;
      }

      function confirmCreate(event) {
        var formElement = event.target.closest("form");
        var listElement = generateItems(formElement);
        var createButtonElement = formElement.querySelector("button[value=\"create\"]");

        if (listElement) {
          showConfirmDialog("<b>Are you sure you want to create delivery order for the following allotments?</b><br/><br/>" + listElement, function () {
            createButtonElement.click();
            setLoadingMessage("Creating...")
            toggleLoadingScreen(true);
          });
        }
      }

      function confirmDelete(event) {
        var formElement = event.target.closest("form");
        var listElement = generateItems(formElement, false);
        var deleteButtonElement = formElement.querySelector("button[value=\"delete\"]");

        if (listElement) {
          showConfirmDialog("<b>Are you sure you want to delete the following allotments?</b><br/><br/>" + listElement, function () {
            deleteButtonElement.click();
            setLoadingMessage("Deleting...")
            toggleLoadingScreen(true);
          });
        }
      }

      function disableAllAllotments(event) {
        var formElement = event.target.closest("form");
        var checkboxes = formElement.querySelectorAll("input[type=\"checkbox\"]");

        for (var i = 0; i < checkboxes.length; i++) {
          checkboxes[i].checked = event.target.checked;
          disableInputsFromCheckbox(checkboxes[i]);
        }
      }

      function disableAllotment(event) {
        disableInputsFromCheckbox(event.target);
      }

      function disableInputsFromCheckbox(checkboxElement) {
        var inputs = checkboxElement.parentNode.parentNode.querySelectorAll("input[type=\"hidden\"]");

        for (var i = 0; i < inputs.length; i++) {
          inputs[i].disabled = !checkboxElement.checked;
        }
      }
    </script>
  </body>
</html>
