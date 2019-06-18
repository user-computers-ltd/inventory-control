<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $brandCodes = $_GET["brand_code"];
  $modelNos = $_GET["model_no"];

  $whereClause = "";

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  if (assigned($modelNos) && count($modelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($m) { return "a.model_no=\"$m\""; }, $modelNos)) . ")";
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
      b.discount                                                                  AS `discount`,
      b.currency_code                                                             AS `currency`,
      a.qty * a.price * (100 - b.discount) / (100 + b.tax)                        AS `net_amt`,
      a.qty * a.price * (100 - b.discount) / (100 + b.tax) * b.exchange_rate      AS `net_amt_base`,
      a.qty * d.cost_average                                                      AS `total_cost`
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
      b.status=\"CONFIRMED\"
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

  $brands = query("
    SELECT DISTINCT
      a.brand_code  AS `code`,
      c.name        AS `name`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    WHERE
      b.status=\"CONFIRMED\"
    ORDER BY
      a.brand_code ASC
  ");

  $modelWhereClause = "";

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  $models = query("
    SELECT DISTINCT
      model_no AS `model_no`
    FROM
      `so_model`
    WHERE
      model_no IS NOT NULL
      $modelWhereClause
    ORDER BY
      model_no ASC
  ");
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
      <div class="headline"><?php echo SALES_PL_REPORT_MODEL_DETAIL_TITLE; ?></div>
      <form>
        <table id="so-input" class="web-only">
          <tr>
            <th>Brand:</th>
            <th>Model No.:</th>
          </tr>
          <tr>
            <td>
              <select name="brand_code[]" multiple>
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($brandCodes) && in_array($code, $brandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td>
              <select name="model_no[]" multiple>
                <?php
                  foreach ($models as $model) {
                    $modelNo = $model["model_no"];
                    $selected = assigned($modelNos) && in_array($modelNo, $modelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
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

          foreach ($soModels as $modelNo => $model) {
            $brand = $model["brand"];
            $models = $model["models"];
            $totalQty = 0;
            $totalCostSum = 0;
            $netAmtBaseSum = 0;
            $profitSum = 0;

            echo "
              <div class=\"so-model\">
                <h4>$brand</h4>
                <h4>$modelNo</h4>
                <table class=\"so-results\">
                  <colgroup>
                    <col style=\"width: 80px\">
                    <col>
                    <col style=\"width: 80px\">
                    <col style=\"width: 40px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 80px\">
                    <col style=\"width: 60px\">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Date</th>
                      <th>Order No.</th>
                      <th class=\"number\">Qty</th>
                      <th class=\"number\">Currency</th>
                      <th class=\"number\">Discount</th>
                      <th class=\"number\">Net Amt</th>
                      <th class=\"number\">$InBaseCurrency</th>
                      <th class=\"number\">Total Cost</th>
                      <th class=\"number\">Profit</th>
                      <th class=\"number\">(in %)</th>
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
              $discount = $soModel["discount"];
              $currency = $soModel["currency"];
              $totalCost = $soModel["total_cost"];
              $netAmt = $soModel["net_amt"];
              $netAmtBase = $soModel["net_amt_base"];
              $profit = $netAmtBase - $totalCost;
              $profitPercentage = $profit / $totalCost * 100;

              $totalQty += $qty;
              $totalCostSum += $totalCost;
              $netAmtBaseSum += $netAmtBase;
              $profitSum += $profit;

              echo "
                <tr>
                  <td title=\"$date\">$date</td>
                  <td title=\"$soNo\"><a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a></td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$currency\" class=\"number\">$currency</td>
                  <td title=\"$discount\" class=\"number\">" . number_format($discount, 2) . "%</td>
                  <td title=\"$netAmt\" class=\"number\">" . number_format($netAmt, 2) . "</td>
                  <td title=\"$netAmtBase\" class=\"number\">" . number_format($netAmtBase, 2) . "</td>
                  <td title=\"$totalCost\" class=\"number\">" . number_format($totalCost, 2) . "</td>
                  <td title=\"$profit\" class=\"number\">" . number_format($profit, 2) . "</td>
                  <td title=\"$profitPercentage\" class=\"number\">" . number_format($profitPercentage, 2) . "%</td>
                </tr>
              ";
            }

            $profitPercentageSum = $profitSum / $totalCostSum * 100;

            echo "
                    <tr>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQty) . "</th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th class=\"number\">" . number_format($netAmtBaseSum, 2) . "</th>
                      <th class=\"number\">" . number_format($totalCostSum, 2) . "</th>
                      <th class=\"number\">" . number_format($profitSum, 2) . "</th>
                      <th class=\"number\">" . number_format($profitPercentageSum, 2) . "%</th>
                    </tr>
                  </tbody>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"so-p-l-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
