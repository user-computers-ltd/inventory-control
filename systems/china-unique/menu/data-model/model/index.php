<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterBrandCodes = $_GET["filter_brand_code"];
  $filterModelNos = $_GET["filter_model_no"];

  $whereClause = "";

  if (assigned($filterBrandCodes) && count($filterBrandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $filterBrandCodes)) . ")";
  }

  if (assigned($filterModelNos) && count($filterModelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.model_no=\"$i\""; }, $filterModelNos)) . ")";
  }

  $results = query("
    SELECT
      a.id                          AS `id`,
      a.brand_code                  AS `brand_code`,
      b.name                        AS `brand_name`,
      a.model_no                    AS `model_no`,
      a.cost_pri_currency_code      AS `cost_pri_currency_code`,
      f.rate                        AS `cost_pri_exchange_rate`,
      a.cost_pri                    AS `cost_pri`,
      a.cost_average                AS `cost_average`,
      IFNULL(c.qty_on_hand, 0)      AS `qty_on_hand`,
      IFNULL(d.qty_on_order, 0)     AS `qty_on_order`,
      IFNULL(e.qty_on_reserve, 0)   AS `qty_on_reserve`
    FROM
      `model` AS a
    LEFT JOIN
      `brand` AS b
    ON a.brand_code=b.code
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        model_no, brand_code) AS c
    ON a.model_no=c.model_no AND a.brand_code=c.brand_code
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(GREATEST(qty_outstanding, 0)) AS `qty_on_order`
      FROM
        `po_model` AS m
      LEFT JOIN
        `po_header` AS h
      ON m.po_no=h.po_no
      WHERE
        h.status='POSTED'
      GROUP BY
        m.model_no, m.brand_code) AS d
    ON a.model_no=d.model_no AND a.brand_code=d.brand_code
    LEFT JOIN
      (SELECT
        brand_code, model_no, SUM(qty) AS `qty_on_reserve`
      FROM
        `so_allotment`
      GROUP BY
        model_no, brand_code) AS e
    ON a.model_no=e.model_no AND a.brand_code=e.brand_code
    LEFT JOIN
      `currency` AS f
    ON a.cost_pri_currency_code=f.code
    LEFT JOIN
      `currency` AS g
    ON a.cost_sec_currency_code=g.code
    WHERE
      a.brand_code IS NOT NULL
      $whereClause
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC
  ");

  $brands = query("
    SELECT DISTINCT
      a.brand_code  AS `code`,
      b.name        AS `name`
    FROM
      `model` AS a
    LEFT JOIN
      `brand` AS b
    ON a.brand_code=b.code
    ORDER BY
      a.brand_code ASC
  ");

  $modelWhereClause = "";

  if (assigned($filterBrandCodes) && count($filterBrandCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "brand_code=\"$i\""; }, $filterBrandCodes)) . ")";
  }

  $models = query("
    SELECT DISTINCT
      model_no
    FROM
      `model`
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
      <div class="headline"><?php echo DATA_MODEL_MODEL_TITLE; ?></div>
      <form>
        <table id="model-input">
          <tr>
            <th>Brand:</th>
            <th>Model No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_brand_code[]" multiple>
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($filterBrandCodes) && in_array($code, $filterBrandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td>
              <select name="filter_model_no[]" multiple>
                <?php
                  foreach ($models as $model) {
                    $modelNo = $model["model_no"];
                    $selected = assigned($filterModelNos) && in_array($modelNo, $filterModelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($results) > 0): ?>
        <table id="model-results">
          <colgroup>
            <col style="width: 60px;">
            <col style="width: 130px;">
            <col style="width: 60px;">
            <col>
            <col>
            <col>
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Brand</th>
              <th>Model No.</th>
              <th>Currency (Pri)</th>
              <th class="number">Exchange Rate (Pri)</th>
              <th class="number">Cost (Pri)</th>
              <th class="number">Average Cost</th>
              <th class="number">Qty On Order</th>
              <th class="number">Qty On Hand</th>
              <th class="number">Qty On Reserve</th>
            </tr>
          </thead>
          <tbody>
            <?php
              for ($i = 0; $i < count($results); $i++) {
                $model = $results[$i];
                $id = $model["id"];
                $brandName = $model["brand_name"];
                $modelNo = $model["model_no"];
                $currencyPri = $model["cost_pri_currency_code"];
                $exchangeRatePri = $model["cost_pri_exchange_rate"];
                $costPrimary = $model["cost_pri"];
                $costAverage = $model["cost_average"];
                $qtyOnHand = $model["qty_on_hand"];
                $qtyOnOrder = $model["qty_on_order"];
                $qtyOnReserve = $model["qty_on_reserve"];

                echo "
                  <tr>
                    <td title=\"$brandName\">$brandName</td>
                    <td title=\"$modelNo\"><a href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$id\">$modelNo</a></td>
                    <td title=\"$currencyPri\">$currencyPri</td>
                    <td class=\"number\" title=\"$exchangeRatePri\">$exchangeRatePri</td>
                    <td class=\"number\" title=\"$costPrimary\">" . number_format($costPrimary, 2) . "</td>
                    <td class=\"number\" title=\"$costAverage\">" . number_format($costAverage, 2) . "</td>
                    <td class=\"number\" title=\"$qtyOnHand\">$qtyOnHand</td>
                    <td class=\"number\" title=\"$qtyOnOrder\">$qtyOnOrder</td>
                    <td class=\"number\" title=\"$qtyOnReserve\">$qtyOnReserve</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="model-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
