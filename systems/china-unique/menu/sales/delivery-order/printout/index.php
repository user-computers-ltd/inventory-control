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
      <?php if (count($doHeaders) > 0) : ?>
        <?php foreach($doHeaders as &$doHeader) : ?>
          <div class="do-page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo SALES_DELIVERY_ORDER_PRINTOUT_TITLE . " (" . $doHeader["warehouse"] . "發貨)" ?></div>
            <table class="do-header">
              <tr>
                <td>編號:</td>
                <td><?php echo $doHeader["do_no"]; ?></td>
              </tr>
              <tr>
                <td>致:</td>
                <td><?php echo $doHeader["client_name"]; ?></td>
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
            </table>
            <?php if (count($doModels[$doHeader["do_no"]]) > 0) : ?>
              <table class="do-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>品牌</th>
                    <th>型號</th>
                    <th>訂單編號</th>
                    <th class="number">數量</th>
                    <th class="number">含稅單價</th>
                    <th class="number">含稅總金額</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $subtotalSum = 0;
                    $discount = $doHeader["discount"];
                    $models = $doModels[$doHeader["do_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $soNo = $model["so_no"];
                      $qty = $model["qty"];
                      $price = $model["price"];
                      $subtotal = $qty * $price;

                      $totalQty += $qty;
                      $subtotalSum += $subtotal;

                      echo "
                        <tr>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          <td>$soNo</td>
                          <td class=\"number\">" . number_format($qty) . "</td>
                          <td class=\"number\">" . number_format($price, 2) . "</td>
                          <td class=\"number\">" . number_format($subtotal, 2) . "</td>
                        </tr>
                      ";
                    }
                  ?>
                </tbody>
                <tfoot>
                  <?php if ($discount > 0) : ?>
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <th></th>
                      <th class="number"><?php echo number_format($subtotalSum, 2); ?></th>
                    </tr>
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td class="number">折扣 <?php echo $discount; ?>%</td>
                      <td class="number"><?php echo number_format($subtotalSum * $discount / 100, 2); ?></td>
                    </tr>
                  <?php endif ?>
                  <tr>
                    <th></th>
                    <th></th>
                    <th class="number">總數量:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                    <th class="number">總金額:</th>
                    <th class="number"><?php echo number_format($subtotalSum * (100 - $discount) / 100, 2); ?></th>
                  </tr>
                </tfoot>
              </table>
            <?php else: ?>
              <div class="do-models-no-results">No models</div>
            <?php endif ?>
            <table class="do-footer">
              <?php if (assigned($doHeader["invoice_no"])) : ?>
                <tr>
                  <td>Invoice No.:</td>
                  <td><?php echo $doHeader["invoice_no"]; ?></td>
                </tr>
              <?php endif ?>
              <?php if (assigned($doHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $doHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
            <span>敬請簽收:</span><br/>
            <br/><br/><br/><br/>
            <span>____________________________________</span><br/>
            <span><?php echo $doHeader["client_name"]; ?></span>
          </div>
        <?php endforeach; ?>
        <div class="web-only">
          <?php echo generateRedirectButton(SALES_DELIVERY_ORDER_INTERNAL_PRINTOUT_URL, "Internal printout"); ?>
        </div>
      <?php else: ?>
        <div id="do-not-found">Delivery order not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
