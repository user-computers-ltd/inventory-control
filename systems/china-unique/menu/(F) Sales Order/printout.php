<?php
  define("SYSTEM_PATH", "../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "printout_process.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="printout.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo getURLParentLocation(); ?></div>

      <?php if ($soHeader): ?>
        <table id="so-header">
          <tr>
            <td>Order No.:</td>
            <td><?php echo $soHeader["Order No."]; ?></td>
            <td>Date:</td>
            <td><?php echo $soHeader["Date"]; ?></td>
          </tr>
          <tr>
            <td>Client:</td>
            <td><?php echo $soHeader["Customer"]; ?></td>
            <td>Currency:</td>
            <td><?php echo $soHeader["Currency"]; ?></td>
          </tr>
          <tr>
            <td>Discount:</td>
            <td><?php echo $soHeader["Discount"]; ?>%</td>
            <td>Tax:</td>
            <td><?php echo $soHeader["Tax"]; ?>%</td>
          </tr>
        </table>
        <?php if (count($soModels) > 0) : ?>
          <table id="so-models">
            <thead>
              <tr></tr>
              <tr>
                <?php
                  foreach ($soModels[0] as $key => $value) {
                    if ($key == "Selling Price" || $key == "Quantity" || $key == "Outstanding" || $key == "Sub Total") {
                      echo "<th><span class=\"number\">$key</span></th>";
                    } else {
                      echo "<th>$key</th>";
                    }
                  }
                ?>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalQty = 0;
                $totalOutstanding = 0;
                $subTotal = 0;
                $discount = $soHeader["Discount"];

                for ($i = 0; $i < count($soModels); $i++) {
                  $soModel = $soModels[$i];
                  $totalQty += $soModel["Quantity"];
                  $totalOutstanding += $soModel["Outstanding"];
                  $subTotal += $soModel["Sub Total"];

                  echo "<tr>";
                  foreach ($soModel as $key => $value) {
                    if ($key == "Quantity" || $key == "Outstanding") {
                      echo "<td><span class=\"number\">" . number_format($value) . "</span></td>";
                    } else if ($key == "Selling Price" || $key == "Sub Total") {
                      echo "<td><span class=\"number\">" . number_format($value, 2) . "</span></td>";
                    } else {
                      echo "<td>$value</td>";
                    }
                  }
                  echo "</tr>";
                }

              ?>
            </tbody>
            <tfoot>
              <?php
                if ($discount > 0) {
                  $discountAmount = $subTotal * $discount / 100;
                  echo "<tr>";
                  foreach ($soModels[0] as $key => $value) {
                    if ($key == "Outstanding") {
                      echo "<th></th>";
                    } else if ($key == "Sub Total") {
                      echo "<th><span class=\"number\">" . number_format($subTotal, 2) . "</span></th>";
                    } else {
                      echo "<td></td>";
                    }
                  }
                  echo "</tr><tr>";
                  foreach ($soModels[0] as $key => $value) {
                    if ($key == "Outstanding") {
                      echo "<td><span class=\"number\">Discount $discount%</span></td>";
                    } else if ($key == "Sub Total") {
                      echo "<td><span class=\"number\">" . number_format($discountAmount, 2) . "</span></td>";
                    } else {
                      echo "<td></td>";
                    }
                  }
                  echo "</tr>";
                }
              ?>
              <tr>
                <?php
                  $totalAmount = $subTotal * (100 - $discount) / 100;

                  foreach ($soModels[0] as $key => $value) {
                    echo "<th>";
                    if ($key == "Selling Price") {
                      echo "<span class=\"number\">Total:</span>";
                    } else if ($key == "Quantity") {
                      echo "<span class=\"number\">" . number_format($totalQty) . "</span>";
                    } else if ($key == "Outstanding") {
                      echo "<span class=\"number\">" . number_format($totalOutstanding) . "</span>";
                    } else if ($key ==  "Sub Total") {
                      echo "<span class=\"number\">" . number_format($totalAmount, 2) . "</span>";
                    }
                    echo "</th>";
                  }
                ?>
              </tr>
            </tfoot>
          </table>
        <?php else: ?>
          <div id="so-models-no-results">No Models</div>
        <?php endif ?>
      <?php else: ?>
        <div id="so-not-found">Sales Order Not Found</div>
      <?php endif ?>
    </div>
    <script>
      var
    </script>
  </body>
</html>
