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
      <div class="headline"><?php echo ALLOTMENT_CUSTOMER_TITLE ?></div>
      <form>
        <table id="so-input">
          <colgroup>
            <col style="width: 100px">
          </colgroup>
          <tr>
            <td><label for="so-debtors">Customer:</label></td>
            <td>
              <select name="filter_debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $code => $name) {
                    $selected = assigned($debtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
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

                foreach ($discountAllotments as $tax => $taxAllotments) {

                  foreach ($taxAllotments as $warehouseCode => $warehouseAllotments) {
                    echo "
                      <form method=\"post\">
                        <div class=\"so-customer-header\">
                          <button type=\"submit\">Create Packing List</button>
                          <span class=\"currency\">$currencyCode @ $exchangeRate</span>
                          <span class=\"discount\">Discount: $discount%</span>
                          <span class=\"tax\">Tax: $tax%</span>
                          <span class=\"warehouse\">Warehouse: $warehouseCode</span>
                          <input name=\"debtor_code\" value=\"$debtorCode\" hidden />
                          <input name=\"currency_code\" value=\"$currencyCode\" hidden />
                          <input name=\"exchange_rate\" value=\"$exchangeRate\" hidden />
                          <input name=\"discount\" value=\"$discount\" hidden />
                          <input name=\"tax\" value=\"$tax\" hidden />
                          <input name=\"warehouse_code\" value=\"$warehouseCode\" hidden />
                        </div>
                    ";

                    if (count($warehouseAllotments) > 0) {
                      echo "
                        <table class=\"so-customer-results\">
                          <colgroup>
                            <col style=\"width: 70px\">
                            <col style=\"width: 70px\">
                            <col style=\"width: 90px\">
                            <col>
                            <col>
                            <col>
                            <col>
                            <col>
                          </colgroup>
                          <thead>
                            <tr></tr>
                            <tr>
                              <th>Order No.</th>
                              <th>Brand</th>
                              <th>Model No.</th>
                              <th class=\"number\">Outstanding Qty</th>
                              <th class=\"number\">Allotted Qty</th>
                              <th class=\"number\">Allotted Subtotal</th>
                              <th class=\"number\">$InBaseCurrCol</th>
                              <th class=\"number\">IA No. / On Hand</th>
                            </tr>
                          </thead>
                          <tbody>
                      ";

                      $totalOutstanding = 0;
                      $totalAllottedQty = 0;
                      $totalAmt = 0;

                      foreach ($warehouseAllotments as $key => $models) {

                        for ($i = 0; $i < count($models); $i++) {
                          $allotment = $models[$i];
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
                          $unitPrice = $allotment["unit_price"];
                          $totalCost = $unitPrice * $qty;
                          $margin = 100 - $price * (100 - $discount) / 100 * $exchangeRate / $unitPrice * 100;

                          $totalOutstanding += $outstandingQty;
                          $totalAllottedQty += $qty;
                          $totalAmt += $subTotal;

                          $modelColumns = $i == 0 ? "
                            <td rowspan=\"" . count($models) . "\" title=\"$soNo\">
                              <a class=\"link\" href=\"../printout.php?so_no=$soNo\">$soNo</a>
                            </td>
                            <td rowspan=\"" . count($models) . "\" title=\"$brandName\">
                              $brandName
                            </td>
                            <td rowspan=\"" . count($models) . "\" title=\"$modelNo\">
                              $modelNo
                            </td>
                            <td rowspan=\"" . count($models) . "\" class=\"number\" title=\"$outstandingQty\">
                            " . number_format($outstandingQty) . "
                            </td>
                          " : "";

                          $sourceColumn = "<td class=\"number\">" . (assigned($iaNo) ? "<a class=\"link\">$iaNo</a>" : "On Hand") . "</td>";

                          echo "
                            <tr>
                              $modelColumns
                              <td class=\"number\">
                                " . number_format($qty) . "
                                <input name=\"so_no[]\" value=\"$soNo\" hidden />
                                <input name=\"brand_code[]\" value=\"$brandCode\" hidden />
                                <input name=\"model_no[]\" value=\"$modelNo\" hidden />
                                <input name=\"price[]\" value=\"$price\" hidden />
                                <input name=\"qty[]\" value=\"$qty\" hidden />
                              </td>
                              <td class=\"number\">" . number_format($subTotal, 2) . "</td>
                              <td class=\"number\">" . number_format($subTotalBase, 2) . "</td>
                              $sourceColumn
                            </tr>
                          ";
                        }
                      }

                      echo "
                          </tbody>
                          <tfoot>
                            <tr>
                              <th></th>
                              <th></th>
                              <th class=\"number\">Total:</th>
                              <th class=\"number\">" . number_format($totalOutstanding) . "</th>
                              <th class=\"number\">" . number_format($totalAllottedQty) . "</th>
                              <th class=\"number\">" . number_format($totalAmt, 2) . "</th>
                              <th class=\"number\">" . number_format($totalAmt * $exchangeRate, 2) . "</th>
                              <th></th>
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

            echo "</div>";
          }
        } else {
          echo "<div class=\"so-customer-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
