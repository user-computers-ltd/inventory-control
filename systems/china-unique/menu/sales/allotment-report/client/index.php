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
      <div class="headline"><?php echo SALES_ALLOTMENT_REPORT_CUSTOMER_TITLE ?></div>
      <form>
        <table id="so-input" class="web-only">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $code => $name) {
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
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
                        $option = "<button type=\"submit\">Create " . SALES_DELIVERY_ORDER_PRINTOUT_TITLE . "</button>";
                      } else {
                        $option = "
                          <span class=\"delivery-order\">
                          " . SALES_DELIVERY_ORDER_PRINTOUT_TITLE . ": <a href=\"" . SALES_DELIVERY_ORDER_URL . "?id=$doId\">$doNo</a>
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
                              <col class=\"web-only\" style=\"width: 30px\">
                            </colgroup>
                            <thead>
                              <tr></tr>
                              <tr>
                                <th class=\"web-only\"></th>
                                <th>DO No. / On Hand</th>
                                <th>Brand</th>
                                <th>Model No.</th>
                                <th>Order No.</th>
                                <th class=\"number\">Outstanding Qty</th>
                                <th class=\"number\">Allotted Qty</th>
                                <th class=\"number\">Price</th>
                                <th class=\"number\">Allotted Subtotal</th>
                                <th class=\"web-only\"></th>
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
                                <input type=\"checkbox\" onchange=\"disableAllotment(event)\" checked />
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

                            $removeColumn = $doId == "" ? "
                              <td class=\"web-only\">
                                <button type=\"submit\" name=\"remove_index\" value=\"$removeIndex\" class=\"remove\">×</button>
                              </td>
                            " : "<td class=\"web-only\"></td>";

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
                                $removeColumn
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
                              <th class=\"web-only\"></th>
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
                              <th class=\"web-only\"></th>
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
                                <th class=\"web-only\"></th>
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
    <script>
      function disableAllotment(event) {
        var inputs = event.target.parentNode.parentNode.querySelectorAll("input[type=\"hidden\"]");

        for (var i = 0; i < inputs.length; i++) {
          inputs[i].disabled = !event.target.checked;
        }
      }
    </script>
  </body>
</html>
