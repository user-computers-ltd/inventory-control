<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once "process.php";
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
      <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_ENQUIRY_SAVED_TITLE; ?></div>
      <form>
        <table id="enquiry-input" class="web-only">
          <tr>
            <th>從:</th>
            <th>至:</th>
          </tr>
          <tr>
            <td><input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" /></td>
            <td><button type="submit">搜索</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($enquiryHeaders) > 0) : ?>
        <form id="enquiry-form" method="post">
          <button type="submit" name="action" value="print">印本</button>
          <button type="submit" name="action" value="delete" style="display: none;"></button>
          <button type="button" onclick="confirmDelete(event)">刪除</button>
          <table id="enquiry-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col>
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>查詢日期</th>
                <th>查詢編號</th>
                <th>客戶</th>
                <th class="number">總數量</th>
                <th class="number">可提供總數量</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyAllotted = 0;

                for ($i = 0; $i < count($enquiryHeaders); $i++) {
                  $enquiryHeader = $enquiryHeaders[$i];
                  $id = $enquiryHeader["id"];
                  $date = $enquiryHeader["date"];
                  $enquiryNo = $enquiryHeader["enquiry_no"];
                  $debtorName = $enquiryHeader["debtor_name"];
                  $qty = $enquiryHeader["qty"];
                  $qtyAllotted = $enquiryHeader["qty_allotted"];

                  $totalQty += $qty;
                  $totalQtyAllotted += $qtyAllotted;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"enquiry_id[]\" data-enquiry_no=\"$enquiryNo\" value=\"$id\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$enquiryNo\"><a class=\"link\" href=\"" . SALES_ENQUIRY_URL . "?id=$id\">$enquiryNo</a></td>
                      <td title=\"$debtorName\">$debtorName</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$qtyAllotted\" class=\"number\">" . number_format($qtyAllotted) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th></th>
                <th class="number">總計:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
        <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
        <?php include_once ROOT_PATH . "includes/components/loading-screen/index.php"; ?>
        <script>
          var enquiryFormElement = document.querySelector("#enquiry-form");
          var deleteButtonElement = enquiryFormElement.querySelector("button[value=\"delete\"]");

          function confirmDelete(event) {
            var checkedItems = enquiryFormElement.querySelectorAll("input[name=\"enquiry_id[]\"]:checked");

            if (checkedItems.length > 0) {

              var listElement = "<ul>";

              for (var i = 0; i < checkedItems.length; i++) {
                listElement += "<li>" + checkedItems[i].dataset["enquiry_no"] + "</li>";
              }

              listElement += "</ul>";

              showConfirmDialog("<b>你確定要刪除以下列表嗎?</b><br/><br/>" + listElement, function () {
                deleteButtonElement.click();
                setLoadingMessage("刪除中...")
                toggleLoadingScreen(true);
              });
            }
          }
        </script>
      <?php else : ?>
        <div class="enquiry-client-no-results">找不到結果</div>
      <?php endif ?>
    </div>
  </body>
</html>
