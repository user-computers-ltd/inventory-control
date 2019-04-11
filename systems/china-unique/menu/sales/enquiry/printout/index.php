<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";
?>

<!DOCTYPE html>
<html lang="ch">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php if (count($enquiryHeaders) > 0) : ?>
        <?php foreach($enquiryHeaders as &$enquiryHeader) : ?>
          <?php
            $discount = $enquiryHeader["discount"];
          ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo SALES_ENQUIRY_PRINTOUT_TITLE ?></div>
            <table class="enquiry-header">
              <tr>
                <td>查詢編號:</td>
                <td><?php echo $enquiryHeader["enquiry_no"]; ?></td>
                <td>查詢日期:</td>
                <td><?php echo $enquiryHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>致:</td>
                <td><?php echo $enquiryHeader["client"]; ?></td>
                <?php if ($showPrice) : ?>
                  <td>貨幣:</td>
                  <td><?php echo $enquiryHeader["currency"]; ?></td>
                <?php endif ?>
              </tr>
              <tr>
                <td>經手人:</td>
                <td><?php echo $enquiryHeader["in_charge"]; ?></td>
                <?php if ($discount > 0 && $showPrice) : ?>
                  <td>折扣:</td>
                  <td><?php echo $discount; ?>%</td>
                <?php endif ?>
              </tr>
            </table>
            <?php if (count($enquiryModels[$enquiryHeader["enquiry_no"]]) > 0) : ?>
              <div class="enquiry-precaution">以下貨物乃現庫存，以最後確認為準。謝謝。</div>
              <table class="enquiry-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>#</th>
                    <th>品牌</th>
                    <th>型號</th>
                    <th class="number">數量</th>
                    <th class="number">可交貨數量</th>
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
                    $items = $enquiryModels[$enquiryHeader["enquiry_no"]];

                    for ($i = 0; $i < count($items); $i++) {
                      $index = $i + 1;
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
                          <td>$index</td>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          <td class=\"number\">" . number_format($qty) . "</td>
                          <td class=\"number\">" . number_format($qtyAllotted) . "</td>
                      ";

                      if ($showPrice) {
                        echo "
                          <td class=\"number\">" . rtrim(rtrim($price, "0"), ".") . "</td>
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
                      <th></th>
                      <?php if ($showPrice) : ?>
                        <td class="number">折扣: <?php echo $discount; ?>%</td>
                        <td class="number"><?php echo number_format($totalAmount * $discount / 100, 2); ?></td>
                      <?php endif ?>
                    </tr>
                  <?php endif ?>
                  <tr>
                    <th></th>
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
              <div class="enquiry-models-no-results">沒有項目</div>
            <?php endif ?>
            <table class="enquiry-footer">
              <?php if (assigned($enquiryHeader["remarks"])) : ?>
                <tr>
                  <td>備註:</td>
                  <td><?php echo $enquiryHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
          <div class="web-only printout-button-wrapper">
            <?php
              if (isSupervisor()) {
                echo generateRedirectButton(SALES_ENQUIRY_INTERNAL_PRINTOUT_URL, "內部印本");
              }

              if ($showPrice) {
                $_GET["show_price"] = "off";
                echo generateRedirectButton(CURRENT_URL, "隱藏價格");
              } else {
                $_GET["show_price"] = "on";
                echo generateRedirectButton(CURRENT_URL, "顯示價格");
              }
            ?>
            <?php if (isset($enquiryHeader["id"])) : ?>
              <form action="<?php echo SALES_ENQUIRY_URL; ?>">
                <input type="hidden" name="id" value="<?php echo $enquiryHeader["id"]; ?>" />
                <button type="submit">編輯</button>
              </form>
            <?php else : ?>
              <?php
                $_POST["status"] = "EDIT";
                echo generateRedirectButton(SALES_ENQUIRY_URL, "編輯");
              ?>
            <?php endif ?>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="enquiry-not-found">找不到結果</div>
      <?php endif ?>
    </div>
  </body>
</html>
