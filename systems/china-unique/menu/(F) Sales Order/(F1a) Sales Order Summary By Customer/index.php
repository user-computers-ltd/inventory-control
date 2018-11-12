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
    AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code='$d'"; }, $debtorCodes)) . "
    )";
  }

  $soHeaders = query("
    SELECT
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `Customer`,
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                                  AS `Date`,
      a.so_no                                                                             AS `Order No.`,
      IFNULL(b.total_qty, 0)                                                              AS `Total Qty`,
      IFNULL(b.total_qty_outstanding, 0)                                                  AS `Outstanding Qty`,
      a.discount                                                                          AS `Discount`,
      a.currency_code                                                                     AS `Currency`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100                       AS `Outstanding Amt`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `$InBaseCurrCol`
    FROM
      `so_header` AS a
      LEFT JOIN
        (SELECT
          so_no, SUM(qty) as total_qty, SUM(qty_outstanding) AS total_qty_outstanding, SUM(qty_outstanding * price) as total_outstanding_amt
        FROM
          `so_model`
        GROUP BY
          so_no) AS b
      ON a.so_no=b.so_no
      LEFT JOIN
        `debtor` AS c
      ON a.debtor_code=c.code
    WHERE
      a.debtor_code IS NOT NULL
      $whereClause
    ORDER BY
      a.so_date DESC
  ");

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
      <?php if (count($soHeaders[0]) > 0): ?>
        <table id="so-results">
          <colgroup>
            <?php
              foreach ($soHeaders[0] as $key => $value) {
                if ($key == "Customer") {
                  echo "<col style=\"width: 120px\">";
                } else if ($key == "Date") {
                  echo "<col style=\"width: 70px\">";
                } else {
                  echo "<col>";
                }
              }
            ?>
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <?php
                foreach ($soHeaders[0] as $key => $value) {
                  if ($key == "Total Qty" || $key == "Outstanding Qty" || $key == "Discount" || $key == "Currency" || $key == "Outstanding Amt" || $key == $InBaseCurrCol) {
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
              $totalAmtBase = 0;

              for ($i = 0; $i < count($soHeaders); $i++) {
                $result = $soHeaders[$i];
                $totalQty += $result["Total Qty"];
                $totalOutstanding += $result["Outstanding Qty"];
                $totalAmtBase += $result[$InBaseCurrCol];

                echo "<tr>";
                foreach ($result as $key => $value) {
                  echo "<td title=\"$value\">";
                  if ($key == "Order No.") {
                    echo "<a class=\"link\" href=\"../entry.php?so_no=$value\">$value</a>";
                  } else if ($key == "Total Qty" || $key == "Outstanding Qty") {
                    echo "<span class=\"number\">" . number_format($value) . "</span>";
                  } else if ($key == "Discount") {
                    echo "<span class=\"number\">" . number_format($value, 2) . "%</span>";
                  } else if ($key == "Currency") {
                    echo "<span class=\"number\">$value</span>";
                  } else if ($key == "Outstanding Amt" || $key == $InBaseCurrCol) {
                    echo "<span class=\"number\">" . number_format($value, 2) . "</span>";
                  } else {
                    echo "<span>$value<span>";
                  }
                  echo "</td>";
                }
                echo "</tr>";
              }
            ?>
          </tbody>
          <tfoot>
            <tr>
              <?php
                foreach ($soHeaders[0] as $key => $value) {
                  echo "<th>";
                  if ($key == "Order No.") {
                    echo "<span class=\"number\">Total:</span>";
                  } else if ($key == "Total Qty") {
                    echo "<span class=\"number\">" . number_format($totalQty) . "</span>";
                  } else if ($key == "Outstanding Qty") {
                    echo "<span class=\"number\">" . number_format($totalOutstanding) . "</span>";
                  } else if ($key == $InBaseCurrCol) {
                    echo "<span class=\"number\">" . number_format($totalAmtBase, 2) . "</span>";
                  }
                  echo "</th>";
                }
              ?>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <div class=\"so-customer-no-results\">No Results</div>
      <?php endif ?>
    </div>
  </body>
</html>
