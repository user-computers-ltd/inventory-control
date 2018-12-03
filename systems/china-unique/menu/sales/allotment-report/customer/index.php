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
      <div class="headline"><?php echo ALLOTMENT_REPORT_CUSTOMER_TITLE ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Customer:</th>
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

            echo "<div class=\"so-customer\"><h4>$debtorCode - $debtorName</h4>";

            foreach ($debtorAllotmentModels as $currencyCode => $currencyAllotments) {
              $exchangeRate = $currencyAllotments["rate"];
              $currencyAllotmentModels = $currencyAllotments["models"];

              foreach ($currencyAllotmentModels as $discount => $discountAllotments) {
                $discountFactor = (100 - $discount) / 100;

                foreach ($discountAllotments as $tax => $taxAllotments) {
                  $taxFactor = (100 + $tax) / 100;

                  foreach ($taxAllotments as $warehouseCode => $warehouseAllotments) {

                    foreach ($warehouseAllotments as $plNo => $plAllotments) {
                      $option = "";

                      if ($plNo == "") {
                        $option = "<button type=\"submit\">Create " . PACKING_LIST_PRINTOUT_TITLE . "</button>";
                      } else {
                        $option = "<span class=\"packing-list\">" . PACKING_LIST_PRINTOUT_TITLE . ": <a href=\"" . PACKING_LIST_URL . "?pl_no=$plNo\">$plNo</a></span>";
                      }
                      echo "
                        <form method=\"post\">
                          <div class=\"so-customer-header\">
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

                      if (count($plAllotments) > 0) {
                        echo "
                          <table class=\"so-customer-results\">
                            <colgroup>
                              <col style=\"width: 80px\">
                              <col style=\"width: 70px\">
                              <col style=\"width: 120px\">
                              <col>
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                              <col style=\"width: 80px\">
                            </colgroup>
                            <thead>
                              <tr></tr>
                              <tr>
                                <th>DO No. / On Hand</th>
                                <th>Brand</th>
                                <th>Model No.</th>
                                <th>Order No.</th>
                                <th class=\"number\">Outstanding Qty</th>
                                <th class=\"number\">Allotted Qty</th>
                                <th class=\"number\">Price</th>
                                <th class=\"number\">Allotted Subtotal</th>
                                <th class=\"number\">$InBaseCurrCol</th>
                              </tr>
                            </thead>
                            <tbody>
                        ";

                        $totalOutstanding = 0;
                        $totalAllottedQty = 0;
                        $totalAmt = 0;

                        foreach ($plAllotments as $key => $models) {

                          for ($i = 0; $i < count($models); $i++) {
                            $allotment = $models[$i];
                            $iaNo = $allotment["ia_no"];
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
                            $subTotalBase = $subTotal * $exchangeRate;
                            $costAverage = $allotment["cost_average"];
                            $totalCost = $costAverage * $qty;
                            $margin = 100 - ($price * $discountFactor / $taxFactor * $exchangeRate / $costAverage) * 100;

                            $totalOutstanding += $outstandingQty;
                            $totalAllottedQty += $qty;
                            $totalAmt += $subTotal;

                            $modelColumns = $i == 0 ? "
                              <td rowspan=\"" . count($models) . "\" title=\"$brandName\">
                                $brandName
                              </td>
                              <td rowspan=\"" . count($models) . "\" title=\"$modelNo\">
                                $modelNo
                              </td>
                              <td rowspan=\"" . count($models) . "\" title=\"$soNo\">
                                <a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?so_no=$soNo\">$soNo</a>
                              </td>
                              <td rowspan=\"" . count($models) . "\" class=\"number\" title=\"$outstandingQty\">
                              " . number_format($outstandingQty) . "
                              </td>
                            " : "";

                            $sourceColumn = "<td>" . (assigned($iaNo) ? "<a class=\"link\">$iaNo</a>" : "On Hand") . "</td>";

                            echo "
                              <tr>
                                $sourceColumn
                                $modelColumns
                                <td class=\"number\">
                                  " . number_format($qty) . "
                                  <input name=\"ia_no[]\" value=\"$iaNo\" hidden />
                                  <input name=\"so_no[]\" value=\"$soNo\" hidden />
                                  <input name=\"brand_code[]\" value=\"$brandCode\" hidden />
                                  <input name=\"model_no[]\" value=\"$modelNo\" hidden />
                                  <input name=\"price[]\" value=\"$price\" hidden />
                                  <input name=\"qty[]\" value=\"$qty\" hidden />
                                </td>
                                <td class=\"number\">" . number_format($price, 2) . "</td>
                                <td class=\"number\">" . number_format($subTotal, 2) . "</td>
                                <td class=\"number\">" . number_format($subTotalBase, 2) . "</td>
                              </tr>
                            ";
                          }
                        }

                        echo "
                            </tbody>
                            <tfoot>
                        ";

                        if ($discount > 0) {
                          echo "
                            <tr>
                              <td colspan=\"6\"></td>
                              <td></td>
                              <th class=\"number\">" . number_format($totalAmt, 2) . "</th>
                              <td></td>
                            </tr>
                            <tr>
                              <td colspan=\"6\"></td>
                              <td class=\"number\">Disc. $discount%</td>
                              <td class=\"number\">" . number_format($totalAmt * $discount / 100, 2) . "</td>
                              <td></td>
                            </tr>
                          ";
                        }

                        echo "
                              <tr>
                                <td colspan=\"3\"></th>
                                <th class=\"number\">Total:</th>
                                <th class=\"number\">" . number_format($totalOutstanding) . "</th>
                                <th class=\"number\">" . number_format($totalAllottedQty) . "</th>
                                <th></th>
                                <th class=\"number\">" . number_format($totalAmt * $discountFactor, 2) . "</th>
                                <th class=\"number\">" . number_format($totalAmt * $discountFactor * $exchangeRate, 2) . "</th>
                              </tr>
                            </tfoot>
                          </table>
                        ";
                      } else {
                        echo "<div class=\"so-customer-no-results\">No sales details</div>";
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
          echo "<div class=\"so-customer-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
