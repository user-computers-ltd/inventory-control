<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

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

  $soModels = query("
    SELECT
      a.brand_code                                                                  AS `brand_code`,
      c.name                                                                        AS `brand_name`,
      a.model_no                                                                    AS `model_no`,
      SUM(a.qty)                                                                    AS `qty`,
      SUM(a.qty * a.price * (100 - b.discount) / (100 + b.tax) * b.exchange_rate)   AS `net_amt_base`,
      SUM(a.qty * d.cost_average)                                                   AS `total_cost`
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
    GROUP BY
      d.id, a.brand_code, c.name, a.model_no
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC
  ");

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
      b.status=\"POSTED\"
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
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_PL_REPORT_MODEL_SUMMARY_TITLE; ?></div>
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
      <?php if (count($soModels) > 0) : ?>
        <table class="so-results">
          <colgroup>
            <col style="width: 100px">
            <col>
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 60px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Brand</th>
              <th>Model No.</th>
              <th class="number">Total Qty</th>
              <th class="number">Total Net Amt</th>
              <th class="number">Total Cost</th>
              <th class="number">Profit</th>
              <th class="number">(in %)</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $totalQty = 0;
            $totalCostSum = 0;
            $netAmtBaseSum = 0;
            $profitSum = 0;

            for ($i = 0; $i < count($soModels); $i++) {
              $soModel = $soModels[$i];
              $brandCode = $soModel["brand_code"];
              $brandName = $soModel["brand_name"];
              $modelNo = $soModel["model_no"];
              $qty = $soModel["qty"];
              $netAmtBase = $soModel["net_amt_base"];
              $totalCost = $soModel["total_cost"];
              $profit = $netAmtBase - $totalCost;
              $profitPercentage = $profit / $totalCost * 100;

              $totalQty += $qty;
              $totalCostSum += $totalCost;
              $netAmtBaseSum += $netAmtBase;
              $profitSum += $profit;

              echo "
                <tr>
                  <td title=\"$brandCode\">$brandName</td>
                  <td title=\"$modelNo\"><a class=\"link\" href=\"" . SALES_PL_REPORT_MODEL_DETAIL_URL . "?brand_code[]=$brandCode&model_no[]=$modelNo\">$modelNo</a></td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$netAmtBase\" class=\"number\">" . number_format($netAmtBase, 2) . "</td>
                  <td title=\"$totalCost\" class=\"number\">" . number_format($totalCost, 2) . "</td>
                  <td title=\"$profit\" class=\"number\">" . number_format($profit, 2) . "</td>
                  <td title=\"$profitPercentage\" class=\"number\">" . number_format($profitPercentage, 2) . "%</td>
                </tr>
              ";
            }

            $profitPercentageSum = $profitSum / $totalCostSum * 100;
          ?>
          </tbody>
          <tfoot>
            <tr>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($netAmtBaseSum, 2); ?></th>
              <th class="number"><?php echo number_format($totalCostSum, 2); ?></th>
              <th class="number"><?php echo number_format($profitSum, 2); ?></th>
              <th class="number"><?php echo number_format($profitPercentageSum, 2); ?>%</th>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="so-p-l-no-results">No results</div>
    <?php endif ?>
  </body>
</html>
