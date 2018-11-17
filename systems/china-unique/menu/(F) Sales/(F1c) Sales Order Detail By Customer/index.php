<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrCol = "(in " . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];

  $whereClause = "";
  $hasFilter = false;

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "b.debtor_code='$d'"; }, $debtorCodes)) . ")";
    $hasFilter = true;
  }

  $results = array();

  if ($hasFilter) {
    $results = query("
      SELECT
        CONCAT(b.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))                     AS `customer`,
        DATE_FORMAT(b.so_date, '%d-%m-%Y')                                                  AS `date`,
        a.so_no                                                                             AS `so_no`,
        d.name                                                                              AS `brand`,
        a.model_no                                                                          AS `model_no`,
        a.qty                                                                               AS `qty`,
        a.qty_outstanding                                                                   AS `outstanding_qty`,
        b.discount                                                                          AS `discount`,
        b.currency_code                                                                     AS `currency`,
        a.price                                                                             AS `selling_price`,
        a.qty_outstanding * a.price * (100 - b.discount) / 100                              AS `outstanding_amt`,
        a.qty_outstanding * a.price * (100 - b.discount) / 100 * b.exchange_rate            AS `outstanding_amt_base`
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
  }

  $soModels = array();

  foreach ($results as $soModel) {
    $customer = $soModel["customer"];

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
          <colgroup>
            <col style="width: 100px">
          </colgroup>
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
              echo "
                <table class=\"so-customer-results\">
                <colgroup>
                  <col style=\"width: 70px\">
                  <col>
                  <col style=\"width: 70px\">
                  <col style=\"width: 90px\">
                  <col>
                  <col>
                  <col>
                  <col>
                  <col>
                  <col>
                  <col>
                </colgroup>
                <thead>
                  <tr></tr>
                  <tr>
                    <th>SO Date</th>
                    <th>Order No.</th>
                    <th>Brand</th>
                    <th>Model No.</th>
                    <th class=\"number\">Qty</th>
                    <th class=\"number\">Outstanding Qty</th>
                    <th class=\"number\">Discount</th>
                    <th class=\"number\">Currency</th>
                    <th class=\"number\">Selling Price</th>
                    <th class=\"number\">Outstanding Amt</th>
                    <th class=\"number\">$InBaseCurrCol</th>
                  </tr>
                </thead>
                <tbody>
              ";

              $totalQty = 0;
              $totalOutstanding = 0;
              $totalAmtBase = 0;

              for ($i = 0; $i < count($models); $i++) {
                $model = $models[$i];
                $date = $model["date"];
                $soNo = $model["so_no"];
                $brand = $model["brand"];
                $modelNo = $model["model_no"];
                $qty = $model["qty"];
                $outstandingQty = $model["outstanding_qty"];
                $discount = $model["discount"];
                $currency = $model["currency"];
                $sellingPrice = $model["selling_price"];
                $outstandingAmt = $model["outstanding_amt"];
                $outstandingAmtBase = $model["outstanding_amt_base"];

                $totalQty += $qty;
                $totalOutstanding += $outstandingQty;
                $totalAmtBase += $outstandingAmtBase;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$soNo\"><a class=\"link\" href=\"../printout.php?so_no=$soNo\">$soNo</a></td>
                    <td title=\"$brand\">$brand</td>
                    <td title=\"$modelNo\">$modelNo</td>
                    <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                    <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                    <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                    <td title=\"$currency\" class=\"number\">$currency</td>
                    <td title=\"$sellingPrice\" class=\"number\">" . number_format($sellingPrice, 2) . "</td>
                    <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                    <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                  </tr>
                ";
              }

              echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQty) . "</th>
                      <th class=\"number\">" . number_format($totalOutstanding) . "</th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">" . number_format($totalAmtBase, 2) . "</th>
                    </tr>
                  </tfoot>
                </table>
              ";
            } else {
              echo "<div class=\"so-customer-no-results\">No sales details</div>";
            }

            echo "</div>";
          }
        } else if (!$hasFilter) {
          echo "<div class=\"so-customer-no-results\">Please select a customer</div>";
        } else {
          echo "<div class=\"so-customer-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
