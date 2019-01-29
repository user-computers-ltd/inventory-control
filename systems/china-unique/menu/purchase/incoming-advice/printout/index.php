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
      <?php if (count($iaHeaders) > 0) : ?>
        <?php foreach($iaHeaders as &$iaHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo INCOMING_ADVICE_PRINTOUT_TITLE ?></div>
            <table class="ia-header">
              <tr>
                <td>IA No.:</td>
                <td><?php echo $iaHeader["ia_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $iaHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>DO No:</td>
                <td><?php echo $iaHeader["do_no"]; ?></td>
                <td>Warehouse:</td>
                <td><?php echo $iaHeader["warehouse"]; ?></td>
              </tr>
              <tr>
                <td>Status:</td>
                <td><?php echo $iaHeader["status"]; ?></td>
              </tr>
            </table>
            <?php if (count($iaModels[$iaHeader["ia_no"]]) > 0) : ?>
              <table class="ia-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>Brand</th>
                    <th>Model No.</th>
                    <th class="number">Qty</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalQty = 0;
                    $models = $iaModels[$iaHeader["ia_no"]];

                    for ($i = 0; $i < count($models); $i++) {
                      $model = $models[$i];
                      $brand = $model["brand"];
                      $modelNo = $model["model_no"];
                      $qty = $model["qty"];

                      $totalQty += $qty;

                      echo "
                        <tr>
                          <td>$brand</td>
                          <td>$modelNo</td>
                          <td class=\"number\">" . number_format($qty) . "</td>
                        </tr>
                      ";
                    }
                  ?>
                  <tr>
                    <th></th>
                    <th class="number">Total:</th>
                    <th class="number"><?php echo number_format($totalQty); ?></th>
                  </tr>
                </tbody>
              </table>
            <?php else : ?>
              <div class="ia-models-no-results">No models</div>
            <?php endif ?>
            <table class="ia-footer">
              <?php if (assigned($iaHeader["remarks"])) : ?>
                <tr>
                  <td>Remarks:</td>
                  <td><?php echo $iaHeader["remarks"]; ?></td>
                </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="ia-not-found">Incoming advice not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
