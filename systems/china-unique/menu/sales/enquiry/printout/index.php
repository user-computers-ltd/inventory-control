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
        <table class="so-header">
          <tr>
            <td>Date:</td>
            <td><?php echo $date; ?></td>
          </tr>
        </table>
        <?php if (count($items) > 0) : ?>
          <table class="so-models">
            <thead>
              <tr></tr>
              <tr>
                <th>Brand</th>
                <th>Model No.</th>
                <th class="number">Qty</th>
                <th class="number">Available</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalQtyAllotted = 0;

                for ($i = 0; $i < count($items); $i++) {
                  $item = $items[$i];
                  $brand = $item["brand"];
                  $modelNo = $item["model_no"];
                  $qty = $item["qty"];
                  $qtyAllotted = $item["qty_allotted"];

                  $totalQty += $qty;
                  $totalQtyAllotted += $qtyAllotted;

                  echo "
                    <tr>
                      <td>$brand</td>
                      <td>$modelNo</td>
                      <td class=\"number\">" . number_format($qty) . "</td>
                      <td class=\"number\">" . number_format($qtyAllotted) . "</td>
                    </tr>
                  ";
                }
              ?>
              <tr>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalQty); ?></th>
                <th class="number"><?php echo number_format($totalQtyAllotted); ?></th>
              </tr>
            </tbody>
          </table>
        <?php else: ?>
          <div class="so-models-no-results">No models</div>
        <?php endif ?>
      </div>
      <div class="web-only">
        <?php echo generateRedirectButton(SALES_ENQUIRY_INTERNAL_PRINTOUT_URL, "Internal printout"); ?>
      </div>
    </div>
  </body>
</html>
