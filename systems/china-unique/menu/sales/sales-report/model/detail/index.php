<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $ids = $_GET["id"];

  $whereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($id) { return "d.id=\"$id\""; }, $ids)) . ")";
  }

  $results = query("
    SELECT
      DATE_FORMAT(b.so_date, '%d-%m-%Y')                                          AS `date`,
      a.brand_code                                                                AS `brand_code`,
      c.name                                                                      AS `brand_name`,
      a.model_no                                                                  AS `model_no`,
      b.id                                                                        AS `so_id`,
      b.so_no                                                                     AS `so_no`,
      a.qty                                                                       AS `qty`,
      a.qty_outstanding                                                           AS `qty_outstanding`,
      b.discount                                                                  AS `discount`,
      b.currency_code                                                             AS `currency`,
      a.qty_outstanding * a.price                                                 AS `amt_outstanding`,
      a.qty_outstanding * a.price * (100 - b.discount) / 100 * b.exchange_rate    AS `amt_outstanding_base`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `model` AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    WHERE
      b.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.model_no ASC,
      b.so_date DESC
  ");

  $soModels = array();

  foreach ($results as $soModel) {
    $brandCode = $soModel["brand_code"];
    $brandName = $soModel["brand_name"];
    $modelNo = $soModel["model_no"];

    $arrayPointer = &$soModels;

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
      $arrayPointer[$modelNo]["brand"] = "$brandCode - $brandName";
      $arrayPointer[$modelNo]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo]["models"];

    array_push($arrayPointer, $soModel);
  }
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
      <div class="headline"><?php echo SALES_REPORT_MODEL_DETAIL_TITLE; ?></div>
      <?php
        if (count($soModels) > 0) {

          foreach ($soModels as $modelNo => $model) {
            $brand = $model["brand"];
            $models = $model["models"];
            $totalQty = 0;
            $totalOutstanding = 0;
            $totalAmtBase = 0;

            echo "
              <div class=\"so-model\">
                <h4>$modelNo</h4>
                <h4 class=\"brand\">$brand</h4>
                <table class=\"so-results\">
                  <colgroup>
                    <col style=\"width: 80px\">
                    <col>
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 60px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 40px\">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Date</th>
                      <th>Order No.</th>
                      <th class=\"number\">Qty</th>
                      <th class=\"number\">Outstanding Qty</th>
                      <th class=\"number\">Currency</th>
                      <th class=\"number\">Outstanding Amt</th>
                      <th class=\"number\">$InBaseCurrency</th>
                      <th class=\"number\">Discount</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            for ($i = 0; $i < count($models); $i++) {
              $soModel = $models[$i];
              $date = $soModel["date"];
              $soId = $soModel["so_id"];
              $soNo = $soModel["so_no"];
              $qty = $soModel["qty"];
              $outstandingQty = $soModel["qty_outstanding"];
              $discount = $soModel["discount"];
              $currency = $soModel["currency"];
              $outstandingAmt = $soModel["amt_outstanding"];
              $outstandingAmtBase = $soModel["amt_outstanding_base"];

              $totalQty += $qty;
              $totalOutstanding += $outstandingQty;
              $totalAmtBase += $outstandingAmtBase;

              echo "
                <tr>
                  <td title=\"$date\">$date</td>
                  <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id=$soId\">$soNo</a></td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                  <td title=\"$currency\" class=\"number\">$currency</td>
                  <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                  <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                  <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                </tr>
              ";
            }

            echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQty) . "</th>
                      <th class=\"number\">" . number_format($totalOutstanding) . "</th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">" . number_format($totalAmtBase, 2) . "</th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"so-model-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
