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
      <div class="page">
        <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
        <div class="headline"><?php echo SALES_ENQUIRY_PRINTOUT_TITLE ?></div>
        <table class="enquiry-header">
          <tr>
            <td>日期:</td>
            <td><?php echo $date; ?></td>
          </tr>
          <tr>
            <td>致:</td>
            <td><?php echo $client; ?></td>
            <?php if ($showPrice) : ?>
              <td>貨幣:</td>
              <td><?php echo $currency; ?></td>
            <?php endif ?>
          </tr>
          <tr>
            <td>經手人:</td>
            <td><?php echo $inCharge; ?></td>
            <?php if ($discount > 0 && $showPrice) : ?>
              <td>折扣:</td>
              <td><?php echo $discount; ?>%</td>
            <?php endif ?>
          </tr>
        </table>
        <?php if (count($items) > 0) : ?>
          <div class="enquiry-precaution">以下貨物乃現庫存，以最後確認為準。謝謝。</div>
          <table class="enquiry-models">
            <thead>
              <tr></tr>
              <tr>
                <th>品牌</th>
                <th>型號</th>
                <th class="number">數量</th>
                <th class="number">可提供數量</th>
                <?php if ($showPrice) : ?>
                  <th class="number">含稅單價</th>
                  <th class="number">含稅總金額</th>
                <?php endif ?>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyAllotted = 0;
                $totalAmount = 0;

                for ($i = 0; $i < count($items); $i++) {
                  $item = $items[$i];
                  $brand = $item["brand"];
                  $modelNo = $item["model_no"];
                  $qty = $item["qty"];
                  $qtyAllotted = $item["qty_allotted"];
                  $price = $item["price"];
                  $subtotal = $price * $qtyAllotted;

                  $totalQty += $qty;
                  $totalQtyAllotted += $qtyAllotted;
                  $totalAmount += $subtotal;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
                      <td class=\"number\">" . number_format($qtyAllotted) . "</td>
                  ";

                  if ($showPrice) {
                    echo "
                      <td class=\"number\">" . number_format($price, 2) . "</td>
                      <td class=\"number\">" . number_format($subtotal, 2) . "</td>
                    ";
                  }

                  echo "</tr>";
                }
              ?>
              <?php if ($discount > 0) : ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <?php if ($showPrice) : ?>
                    <th></th>
                    <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
                  <?php endif ?>
                </tr>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th></th>
                  <?php if ($showPrice) : ?>
                    <td class="number">折扣: <?php echo $discount; ?>%</td>
                    <td class="number"><?php echo number_format($totalAmount * $discount / 100, 2); ?></td>
                  <?php endif ?>
                </tr>
              <?php endif ?>
              <tr>
                <th></th>
                <th class="number">總數量:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
                <?php if ($showPrice) : ?>
                  <th class="number">總金額:</th>
                  <th class="number"><?php echo number_format($totalAmount * (100 - $discount) / 100, 2); ?></th>
                <?php endif ?>
              </tr>
            </tbody>
          </table>
        <?php else : ?>
          <div class="enquiry-models-no-results">No models</div>
        <?php endif ?>
        <table class="enquiry-footer">
          <?php if (assigned($remarks)) : ?>
            <tr>
              <td>備註:</td>
              <td><?php echo $remarks; ?></td>
            </tr>
          <?php endif ?>
        </table>
      </div>
      <div class="web-only printout-button-wrapper">
        <?php echo generateRedirectButton(SALES_ENQUIRY_INTERNAL_PRINTOUT_URL, "Internal printout"); ?>
        <?php echo generateRedirectButton(SALES_ENQUIRY_URL, "Edit"); ?>
      </div>
    </div>
  </body>
</html>
