<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrCol = "(in " . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];

  $whereClause = "";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "b.debtor_code='$d'"; }, $debtorCodes)) . ")";
  }

  $results = query("
    SELECT
      CONCAT(b.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `Customer`,
      DATE_FORMAT(b.so_date, '%d-%m-%Y')                                                  AS `SO Date`,
      a.so_no                                                                             AS `Order No.`,
      d.name                                                                              AS `Brand`,
      a.model_no                                                                          AS `Model No.`,
      a.qty                                                                               AS `Qty`,
      a.qty_outstanding                                                                   AS `Outstanding Qty`,
      b.discount                                                                          AS `Discount`,
      b.currency_code                                                                     AS `Currency`,
      e.cost_average                                                                      AS `Unit Price`,
      a.qty_outstanding * a.price * (100 - b.discount) / 100                              AS `Outstanding Amt`,
      a.qty_outstanding * a.price * (100 - b.discount) / 100 * b.exchange_rate            AS `$InBaseCurrCol`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
      ON b.debtor_code=c.code
    LEFT JOIN
      `brand` AS d
    ON a.brand_code=d.code
    LEFT JOIN
      `model` AS e
    ON a.model_no=e.model_no AND a.brand_code=e.brand_code
    WHERE
      b.debtor_code IS NOT NULL
      $whereClause
    ORDER BY
      CONCAT(b.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown')) ASC,
      b.so_date DESC,
      a.model_no ASC
  ");

  $soModels = array();

  foreach ($results as $soModel) {
    $customer = $soModel["Customer"];
    unset($soModel["Customer"]);

    if (!isset($soModels[$customer])) {
      $soModels[$customer] = array();
    }

    array_push($soModels[$customer], $soModel);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(b.english_name, 'Unknown')   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      `debtor` AS b
      ON a.debtor_code=b.code
    ORDER BY
      a.debtor_code ASC
  ");
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
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo getURLParentLocation(); ?></div>
      <form>
        <table id="so-input">
          <tr>
            <td><label for="so-debtors">Customer:</label></td>
            <td>
              <select name="debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($debtorCodes) && in_array($code, $debtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php
        if (count($soModels) > 0) {
          foreach ($soModels as $customer => $models) {
            echo "<div class=\"so-customer\"><h4>$customer</h4>";

            if (count($models) > 0) {
              echo "<table class=\"so-customer-results\"><colgroup>";

              foreach ($models[0] as $key => $value) {
                if ($key == "SO Date" || $key == "Brand") {
                  echo "<col style=\"width: 70px\">";
                } else if ($key == "Model No.") {
                  echo "<col style=\"width: 100px\">";
                } else {
                  echo "<col>";
                }
              }

              echo "</colgroup><thead><tr></tr><tr>";
              foreach ($models[0] as $key => $value) {
                if ($key == "Qty" || $key == "Outstanding Qty" || $key == "Discount" || $key == "Currency" || $key == "Unit Price" || $key == "Outstanding Amt" || $key == $InBaseCurrCol) {
                  echo "<th><span class=\"number\">$key</span></th>";
                } else {
                  echo "<th>$key</th>";
                }
              }
              echo "</tr></thead><tbody>";

              $totalQty = 0;
              $totalOutstanding = 0;
              $totalAmtBase = 0;

              for ($i = 0; $i < count($models); $i++) {
                $soModel = $models[$i];
                $totalQty += $soModel["Qty"];
                $totalOutstanding += $soModel["Outstanding Qty"];
                $totalAmtBase += $soModel[$InBaseCurrCol];

                echo "<tr>";
                foreach ($soModel as $key => $value) {
                  echo "<td title=\"$value\">";
                  if ($key == "Order No.") {
                    echo "<a class=\"link\" href=\"../entry.php?so_no=$value\">$value</a>";
                  } else if ($key == "Qty" || $key == "Outstanding Qty") {
                    echo "<span class=\"number\">" . number_format($value) . "</span>";
                  } else if ($key == "Discount") {
                    echo "<span class=\"number\">" . number_format($value, 2) . "%</span>";
                  } else if ($key == "Currency") {
                    echo "<span class=\"number\">$value</span>";
                  } else if ($key == "Unit Price" || $key == "Outstanding Amt" || $key == $InBaseCurrCol) {
                    echo "<span class=\"number\">" . number_format($value, 2) . "</span>";
                  } else {
                    echo "<span>$value<span>";
                  }
                  echo "</td>";
                }
                echo "</tr>";
              }

              echo "</tbody><tfoot><tr>";

              foreach ($models[0] as $key => $value) {
                echo "<th>";
                if ($key == "Model No.") {
                  echo "<span class=\"number\">Total:</span>";
                } else if ($key == "Qty") {
                  echo "<span class=\"number\">" . number_format($totalQty) . "</span>";
                } else if ($key == "Outstanding Qty") {
                  echo "<span class=\"number\">" . number_format($totalOutstanding) . "</span>";
                } else if ($key == $InBaseCurrCol) {
                  echo "<span class=\"number\">" . number_format($totalAmtBase, 2) . "</span>";
                }
                echo "</th>";
              }

              echo "</tr></tfoot></table>";
            } else {
              echo "<div class=\"so-customer-no-results\">No Sales Details</div>";
            }

            echo "</div>";
          }
        } else {
          echo "<div class=\"so-customer-no-results\">No Results</div>";
        }
      ?>
    </div>
  </body>
</html>
