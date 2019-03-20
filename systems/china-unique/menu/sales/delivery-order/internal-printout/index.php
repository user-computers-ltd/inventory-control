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
      <?php if (count($doHeaders) > 0) : ?>
        <?php foreach($doHeaders as &$doHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <table class="do-header">
              <tr>
                <td>編號:</td>
                <td><?php echo $doHeader["do_no"]; ?></td>
              </tr>
              <tr>
                <td>致:</td>
                <td><?php echo $doHeader["client_name"] . " (" . $doHeader["client_code"] . ")"; ?></td>
              </tr>
              <tr>
                <td>地址:</td>
                <td><?php echo $doHeader["client_address"]; ?></td>
              </tr>
              <tr>
                <td>收貨人:</td>
                <td><?php echo $doHeader["client_contact"]; ?></td>
              </tr>
              <tr>
                <td>電話:</td>
                <td><?php echo $doHeader["client_tel"]; ?></td>
              </tr>
              <tr>
                <td>日期:</td>
                <td><?php echo $doHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>貨幣:</td>
                <td><?php echo $doHeader["currency"]; ?></td>
              </tr>
            </table>
            <div class="headline"><?php echo SALES_DELIVERY_ORDER_INTERNAL_PRINTOUT_TITLE . " (" . $doHeader["warehouse"] . "發貨)" ?></div>
            <?php if (count($doModels[$doHeader["do_no"]]) > 0) : ?>
              <table class="do-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>訂單編號</th>
                    <th>品牌</th>
                    <th>型號</th>
                    <th class="number">數量</th>
                    <th class="number">含稅單價</th>
                    <th class="number">含稅總金額</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $totalCost = 0;
                    $subtotalSum = 0;
                    $discount = $doHeader["discount"];
                    $tax = $doHeader["tax"];
                    $models = $doModels[$doHeader["do_no"]];

                    $occurrenceMap = array();

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $soNo = $model["so_no"];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $qty = $model["qty"];
                      $price = $model["price"];
                      $cost = $model["cost_average"];

                      $totalQty += $qty;
                      $totalCost += $qty * $cost;
                      $subtotalSum += $qty * $price;

                      $tempQty = $qty;

                      if (!isset($occurrenceMap["$soNo - $brand - $modelNo"])) {
                        $occurrenceMap["$soNo - $brand - $modelNo"] = explode(",", $model["occurrence"]);
                      }

                      $occurrences = &$occurrenceMap["$soNo - $brand - $modelNo"];

                      for ($j = 0; $j < count($occurrences); $j++) {
                        if ($tempQty > 0 && $occurrences[$j] > 0) {
                          $showQty = min($tempQty, $occurrences[$j]);
                          echo "
                            <tr>
                              <td>$soNo</td>
                              <td>$brand</td>
                              <td>$modelNo</td>
                              <td class=\"number\">" . number_format($showQty) . "</td>
                              <td class=\"number\">" . number_format($price, 2) . "</td>
                              <td class=\"number\">" . number_format($showQty * $price, 2) . "</td>
                            </tr>
                          ";

                          $tempQty -= $showQty;
                          $occurrences[$j] -= $showQty;
                        }
                      }
                    }
                  ?>
                  <?php if ($discount > 0) : ?>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                    </tr>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <td class="number">折扣: <?php echo $discount; ?>%</td>
                      <td class="number"><?php echo number_format($subtotalSum * $discount / 100, 2); ?></td>
                    </tr>
                  <?php endif ?>
                  <tr>
                    <th></th>
                    <th></th>
                    <th class="number">總數量:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                    <th class="number">總金額:</th>
                    <th class="number">
                      <?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?>
                    </th>
                  </tr>

                </tbody>
              </table>
            <?php else : ?>
              <div class="do-models-no-results">沒有項目</div>
            <?php endif ?>
            <table class="do-footer">
              <?php if (assigned($doHeader["invoice_no"])) : ?>
                <tr>
                  <td>發票編號:</td>
                  <td><?php echo $doHeader["invoice_no"]; ?></td>
                </tr>
              <?php endif ?>
              <?php if (assigned($doHeader["remarks"])) : ?>
                <tr>
                  <td>備註:</td>
                  <td><?php echo $doHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
        <div class="web-only">
          <?php echo generateRedirectButton(SALES_DELIVERY_ORDER_PRINTOUT_URL, "外部印本"); ?>
        </div>
      <?php else : ?>
        <div id="do-not-found">找不到結果</div>
      <?php endif ?>
    </div>
  </body>
</html>
