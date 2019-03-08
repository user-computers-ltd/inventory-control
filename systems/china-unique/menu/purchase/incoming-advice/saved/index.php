<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once SYSTEM_PATH . "includes/php/actions.php";
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
      <div class="headline"><?php echo INCOMING_ADVICE_SAVED_TITLE; ?></div>
      <form>
        <table id="ia-input" class="web-only">
          <tr>
            <th>From:</th>
            <th>To:</th>
          </tr>
          <tr>
            <td><input type="date" name="from" value="<?php echo $from; ?>" /></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" /></td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($iaHeaders) > 0) : ?>
        <form method="post">
          <button type="submit" name="action" value="print">Print</button>
          <table id="ia-results">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 70px">
              <col style="width: 30px">
              <col>
              <col>
              <col>
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Date</th>
                <th class="number">#</th>
                <th>IA No.</th>
                <th>DO No.</th>
                <th>Creditor</th>
                <th class="number">Total Qty</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;

                for ($i = 0; $i < count($iaHeaders); $i++) {
                  $iaHeader = $iaHeaders[$i];
                  $id = $iaHeader["id"];
                  $count = $iaHeader["count"];
                  $date = $iaHeader["date"];
                  $iaNo = $iaHeader["ia_no"];
                  $doNo = $iaHeader["do_no"];
                  $creditorName = $iaHeader["creditor_name"];
                  $qty = $iaHeader["total_qty"];

                  $totalQty += $qty;

                  echo "
                    <tr>
                      <td class=\"web-only\"><input type=\"checkbox\" name=\"ia_id[]\" value=\"$id\" /></td>
                      <td title=\"$date\">$date</td>
                      <td title=\"$count\" class=\"number\">$count</td>
                      <td title=\"$iaNo\"><a class=\"link\" href=\"" . INCOMING_ADVICE_URL . "?id=$id\">$iaNo</a></td>
                      <td title=\"$doNo\">$doNo</td>
                      <td title=\"$creditorName\">$creditorName</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th class="number"></th>
                <th></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else: ?>
        <div class="ia-model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
