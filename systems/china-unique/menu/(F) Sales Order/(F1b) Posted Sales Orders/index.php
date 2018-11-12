<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $from = $_GET["from"];
  $to = $_GET["to"];

  $range = "";

  if (assigned($from)) {
    $range = $range . " AND so_date >= \"$from\"";
  }

  if (assigned($to)) {
    $range = $range . " AND so_date <= \"$to\"";
  }

  $results = query("
    SELECT
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                        AS `Date`,
      a.so_no                                                                   AS `Order No.`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))           AS `Customer`,
      IFNULL(b.total_qty, 0)                                                    AS `Total Qty`,
      IFNULL(b.total_qty_outstanding, 0)                                        AS `Outstanding Qty`,
      a.discount                                                                AS `Discount`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                         AS `Total`
    FROM
      `so_header` AS a
      LEFT JOIN
        (SELECT
          so_no, SUM(qty) as total_qty, SUM(qty_outstanding) AS total_qty_outstanding, SUM(qty * price) as total_amt
        FROM
          `so_model`
        GROUP BY
          so_no) AS b
      ON a.so_no=b.so_no
      LEFT JOIN
        `debtor` AS c
        ON a.debtor_code=c.code
    WHERE
      a.status='POSTED'
      $range
    ORDER BY
      so_date DESC
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
            <td><label for="so-from">from:</label></td>
            <td><input type="date" name="from" value="<?php echo $from; ?>" id="so-from" /></td>
            <td><label for="so-to">to:</label></td>
            <td><input type="date" name="to" value="<?php echo $to; ?>" id="so-to" /></td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($results[0]) > 0): ?>
        <table id="so-results">
          <thead>
            <tr></tr>
            <tr>
              <?php
                foreach ($results[0] as $key => $value) {
                  if ($key == "Total Qty" || $key == "Outstanding Qty" || $key == "Discount" || $key ==  "Total") {
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
              $totalAmount = 0;

              for ($i = 0; $i < count($results); $i++) {
                $result = $results[$i];
                $qty = $result["Total Qty"];
                $outstanding = $result["Outstanding Qty"];
                $totalQty += $qty;
                $totalOutstanding += $outstanding;
                $totalAmount += $result["Total"];

                echo "<tr>";
                foreach ($result as $key => $value) {
                  if ($key == "Order No.") {
                    echo "<td><a class=\"link\" href=\"../entry.php?so_no=$value\">$value</a>";
                  } else if ($key == "Total Qty") {
                    echo "<td><span class=\"number\">" . number_format($value) . "</span></td>";
                  } else if ($key == "Discount") {
                    echo "<td><span class=\"number\">" . number_format($value, 2) . "%</span></td>";
                  } else if ($key == "Total") {
                    echo "<td><span class=\"number\">" . number_format($value, 2) . "</span></td>";
                  } else if ($key == "Outstanding Qty") {
                    $percentage = $qty > 0 ? $outstanding / $qty * 100 : 0;
                    echo "<td style=\"background-image: linear-gradient(to right, rgba(255, 0, 0, 0.2) $percentage%, rgba(255, 0, 0, 0) $percentage% )\"><span class=\"number\">" . number_format($value) . "</span></td>";
                  } else {
                    echo "<td>$value</td>";
                  }
                }
                echo "</tr>";
              }
            ?>
          </tbody>
          <tfoot>
            <tr>
              <?php
                foreach ($results[0] as $key => $value) {
                  echo "<th>";
                  if ($key == "Customer") {
                    echo "<span class=\"number\">Total:</span>";
                  } else if ($key == "Total Qty") {
                    echo "<span class=\"number\">" . number_format($totalQty) . "</span>";
                  } else if ($key == "Outstanding Qty") {
                    echo "<span class=\"number\">" . number_format($totalOutstanding) . "</span>";
                  } else if ($key ==  "Total") {
                    echo "<span class=\"number\">" . number_format($totalAmount, 2) . "</span>";
                  }
                  echo "</th>";
                }
              ?>
            </tr>
          </tfoot>
        </table>
      <?php else: ?>
        <div id="so-no-results">No Results</div>
      <?php endif ?>
    </div>
    <script>
      var
    </script>
  </body>
</html>
