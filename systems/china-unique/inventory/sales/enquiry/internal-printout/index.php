<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";

  $date = new DateTime();
  $date = $date->format("d-m-Y H:i:s");
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
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo SALES_ENQUIRY_INTERNAL_PRINTOUT_TITLE ?></div>
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
              </tr>
              <tr>
                <td>經手人:</td>
                <td><?php echo $enquiryHeader["in_charge"]; ?></td>
              </tr>
            </table>
            <div class="generation-date">產生時間: <?php echo $date; ?></div>
            <?php if (count($enquiryModels[$enquiryHeader["enquiry_no"]]) > 0) : ?>
              <table class="enquiry-models sortable">
                <colgroup>
                  <col style="width: 30px">
                  <col style="width: 80px">
                  <col>
                  <col style="width: 80px">
                  <col style="width: 80px">
                  <col style="width: 80px">
                  <col style="width: 80px">
                  <col style="width: 80px">
                  <col style="width: 80px">
                  <col style="width: 80px">
                </colgroup>
                <thead>
                  <tr></tr>
                  <tr>
                    <th rowspan="2">#</th>
                    <th rowspan="2">品牌</th>
                    <th rowspan="2">型號</th>
                    <th colspan="7" class="quantity">數量</th>
                  </tr>
                  <tr>
                    <th class="number">要求</th>
                    <th class="number">手上</th>
                    <th class="number">已預訂</th>
                    <th class="number">現有</th>
                    <th class="number">提供</th>
                    <th class="number">來貨</th>
                    <th class="number">已預訂</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $totalQtyOnHand = 0;
                    $totalQtyOnHandReserve = 0;
                    $totalQtyAvailable = 0;
                    $totalQtyAllotted = 0;
                    $items = $enquiryModels[$enquiryHeader["enquiry_no"]];

                    for ($i = 0; $i < count($items); $i++) {
                      $index = $i + 1;
                      $item = $items[$i];
                      $brand = $item["brand"];
                      $modelNo = $item["model_no"];
                      $qty = $item["qty"];
                      $qtyOnHand = $item["qty_on_hand"];
                      $qtyOnHandReserve = $item["qty_on_hand_reserve"];
                      $qtyAvailable = $item["qty_available"];
                      $qtyIncoming = $item["qty_incoming"];
                      $qtyIncomingReserve = $item["qty_incoming_reserve"];
                      $qtyAllotted = $item["qty_allotted"];

                      $totalQty += $qty;
                      $totalQtyOnHand += $qtyOnHand;
                      $totalQtyOnHandReserve += $qtyOnHandReserve;
                      $totalQtyAvailable += $qtyAvailable;
                      $totalQtyAllotted += $qtyAllotted;

                      echo "
                        <tr>
                          <td>$index</td>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          <td class=\"number\">" . number_format($qty) . "</td>
                          <td class=\"number\">" . number_format($qtyOnHand) . "</td>
                          <td class=\"number\">" . number_format($qtyOnHandReserve) . "</td>
                          <td class=\"number\">" . number_format($qtyAvailable) . "</td>
                          <td class=\"number\">" . number_format($qtyAllotted) . "</td>
                          <td class=\"number\">" . number_format($qtyIncoming) . "</td>
                          <td class=\"number\">" . number_format($qtyIncomingReserve) . "</td>
                        </tr>
                      ";
                    }
                  ?>
                </tbody>
                <tbody>
                  <tr>
                    <th></th>
                    <th></th>
                    <th class="number">總計:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                    <th class="number"><?php echo number_format($totalQtyOnHand); ?></th>
                    <th class="number"><?php echo number_format($totalQtyOnHandReserve); ?></th>
                    <th class="number"><?php echo number_format($totalQtyAvailable); ?></th>
                    <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
                    <th></th>
                    <th></th>
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
            <?php echo generateRedirectButton(SALES_ENQUIRY_PRINTOUT_URL, "外部印本"); ?>
            <?php if (isset($enquiryHeader["id"])) : ?>
              <form action="<?php echo SALES_ENQUIRY_URL; ?>">
                <input type="hidden" name="id" value="<?php echo $enquiryHeader["id"]; ?>" />
                <button type="submit">編輯</button>
              </form>
            <?php else : ?>
              <?php echo generateRedirectButton(SALES_ENQUIRY_URL, "編輯"); ?>
            <?php endif ?>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="enquiry-not-found">找不到結果</div>
      <?php endif ?>
    </div>
  </body>
</html>
