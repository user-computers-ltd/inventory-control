<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $ids = $_GET["id"];
  $filterBrandCodes = $_GET["filter_brand_code"];
  $filterModelNos = $_GET["filter_model_no"];

  $whereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($id) { return "d.id=\"$id\""; }, $ids)) . ")";
  }

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
      a.warehouse_code              AS `warehouse_code`,
      d.name                        AS `warehouse_name`,
      a.brand_code                  AS `brand_code`,
      c.name                        AS `brand_name`,
      b.id                          AS `model_id`,
      a.model_no                    AS `model_no`,
      a.qty                         AS `qty`,
      b.cost_average                AS `cost_average`,
      a.qty * b.cost_average        AS `subtotal`
    FROM
      `stock` AS a
    LEFT JOIN
      `model` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `warehouse` AS d
    ON a.warehouse_code=d.code
    WHERE
      a.qty > 0
      $whereClause
    ORDER BY
      a.warehouse_code ASC,
      a.brand_code ASC,
      a.model_no ASC
  ");

  $stocks = array();

  foreach ($results as $stock) {
    $warehouseCode = $stock["warehouse_code"];
    $warehouseName = $stock["warehouse_name"];
    $brandCode = $stock["brand_code"];
    $brandName = $stock["brand_name"];
    $modelNo = $stock["model_no"];
    $modelId = $stock["model_id"];

    $arrayPointer = &$stocks;

    if (!isset($arrayPointer[$warehouseCode])) {
      $arrayPointer[$warehouseCode] = array();
      $arrayPointer[$warehouseCode]["name"] = $warehouseName;
      $arrayPointer[$warehouseCode]["stocks"] = array();
    }
    $arrayPointer = &$arrayPointer[$warehouseCode]["stocks"];

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
      $arrayPointer[$brandCode]["name"] = $brandName;
      $arrayPointer[$brandCode]["stocks"] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode]["stocks"];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
      $arrayPointer[$modelNo]["id"] = $modelId;
      $arrayPointer[$modelNo]["stocks"] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo]["stocks"];

    array_push($arrayPointer, $stock);
  }

  $brandWhereClause = "";
  $modelWhereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $brandWhereClause = $brandWhereClause . "
      AND (" . join(" OR ", array_map(function ($id) { return "c.id=\"$id\""; }, $ids)) . ")";
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($id) { return "c.id=\"$id\""; }, $ids)) . ")";
  }

  if (assigned($filterBrandCodes) && count($filterBrandCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $filterBrandCodes)) . ")";
  }

  $brands = query("
    SELECT DISTINCT
      b.code AS `code`,
      b.name AS `name`
    FROM
      `stock` AS a
    LEFT JOIN
      `brand` AS b
    ON a.brand_code=b.code
    LEFT JOIN
      `warehouse` AS c
    ON a.warehouse_code=c.code
    WHERE
      a.qty > 0
      $brandWhereClause
    ORDER BY
      b.code ASC
  ");

  $models = query("
    SELECT DISTINCT
      b.model_no
    FROM
      `stock` AS a
    LEFT JOIN
      `model` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `warehouse` AS c
    ON a.warehouse_code=c.code
    WHERE
      a.qty > 0
      $modelWhereClause
    ORDER BY
      b.model_no ASC
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
      <div class="headline"><?php echo REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_TITLE; ?></div>
      <form>
        <?php
          if (assigned($ids) && count($ids) > 0) {
            echo join(array_map(function ($id) {
              return "<input type=\"hidden\" name=\"id[]\" value=\"$id\" />";
            }, $ids));
          }
        ?>
        <table id="warehouse-input" class="web-only">
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
      <?php
        if (count($stocks) > 0) {

          foreach ($stocks as $warehouseCode => $warehouse) {
            $warehouseName = $warehouse["name"];
            $warehouseStocks = $warehouse["stocks"];

            $totalQty = 0;
            $totalAmt = 0;

            echo "
              <div class=\"warehouse-client\">
                <h4>$warehouseCode - $warehouseName</h4>
                <table class=\"warehouse-results\">
                  <colgroup>
                    <col>
                    <col>
                    <col style=\"width: 80px;\">
                    <col style=\"width: 80px;\">
                    <col style=\"width: 80px;\">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Brand</th>
                      <th>Model No.</th>
                      <th class=\"number\">Qty</th>
                      <th class=\"number\">Average Cost</th>
                      <th class=\"number\">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            foreach ($warehouseStocks as $brandCode => $brand) {
              $brandName = $brand["name"];
              $brandStocks = $brand["stocks"];

              foreach ($brandStocks as $modelNo => $model) {
                $modelId = $model["id"];
                $modelStocks = $model["stocks"];

                for ($i = 0; $i < count($modelStocks); $i++) {
                  $modelStock = $modelStocks[$i];
                  $qty = $modelStock["qty"];
                  $costAverage = $modelStock["cost_average"];
                  $subtotal = $modelStock["subtotal"];

                  $totalQty += $qty;
                  $totalAmt += $subtotal;

                  echo "
                    <tr>
                      <td title=\"$brandName\">$brandCode - $brandName</td>
                      <td title=\"$modelNo\"><a class=\"link\" href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$modelId\">$modelNo</a></td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      <td title=\"$costAverage\" class=\"number\">" . number_format($costAverage, 2) . "</td>
                      <td title=\"$subtotal\" class=\"number\">" . number_format($subtotal, 2) . "</td>
                    </tr>
                  ";
                }
              }
            }

            echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th></th>
                      <th class=\"number\">Total:</th>
                      <th class=\"number\">" . number_format($totalQty) . "</th>
                      <th></th>
                      <th class=\"number\">" . number_format($totalAmt, 2) . "</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            ";
          }
        } else {
          echo "<div class=\"warehouse-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
