<?php
  define("SYSTEM_PATH", "../../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $ids = $_GET["id"];
  $filterWarehouseCodes = $_GET["filter_warehouse_code"];
  $filterModelNos = $_GET["filter_model_no"];

  $whereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids)) . ")";
  }

  if (assigned($filterWarehouseCodes) && count($filterWarehouseCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.warehouse_code=\"$i\""; }, $filterWarehouseCodes)) . ")";
  }

  if (assigned($filterModelNos) && count($filterModelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.model_no=\"$i\""; }, $filterModelNos)) . ")";
  }

  $results = query("
    SELECT
      a.brand_code                  AS `brand_code`,
      c.name                        AS `brand_name`,
      b.id                          AS `model_id`,
      a.model_no                    AS `model_no`,
      a.warehouse_code              AS `warehouse_code`,
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
    WHERE
      a.qty > 0
      $whereClause
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC,
      a.warehouse_code ASC
  ");

  $stocks = array();

  foreach ($results as $stock) {
    $brandCode = $stock["brand_code"];
    $brandName = $stock["brand_name"];
    $modelNo = $stock["model_no"];
    $modelId = $stock["model_id"];

    $arrayPointer = &$stocks;

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

  $warehouseWhereClause = "";
  $modelWhereClause = "";

  if (assigned($ids) && count($ids) > 0) {
    $warehouseWhereClause = $warehouseWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids)) . ")";
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids)) . ")";
  }

  if (assigned($filterWarehouseCodes) && count($filterWarehouseCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.warehouse_code=\"$i\""; }, $filterWarehouseCodes)) . ")";
  }

  $warehouses = query("
    SELECT DISTINCT
      b.code AS `warehouse_code`,
      b.name AS `warehouse_name`
    FROM
      `stock` AS a
    LEFT JOIN
      `warehouse` AS b
    ON a.warehouse_code=b.code
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    WHERE
      a.qty > 0
      $warehouseWhereClause
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
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `warehouse` AS d
    ON a.warehouse_code=d.code
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
      <div class="headline"><?php echo REPORT_STOCK_TAKE_BRAND_DETAIL_TITLE; ?></div>
      <form>
        <?php
          if (assigned($ids) && count($ids) > 0) {
            echo join(array_map(function ($id) {
              return "<input type=\"hidden\" name=\"id[]\" value=\"$id\" />";
            }, $ids));
          }
        ?>
        <table id="brand-input" class="web-only">
          <tr>
            <th>Warehouse:</th>
            <th>Model No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_warehouse_code[]" multiple>
                <?php
                  foreach ($warehouses as $warehouse) {
                    $warehouseCode = $warehouse["warehouse_code"];
                    $warehouseName = $warehouse["warehouse_name"];
                    $selected = assigned($filterWarehouseCodes) && in_array($warehouseCode, $filterWarehouseCodes) ? "selected" : "";
                    echo "<option value=\"$warehouseCode\" $selected>$warehouseCode - $warehouseName</option>";
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

          foreach ($stocks as $brandCode => $brand) {
            $brandName = $brand["name"];
            $brandStocks = $brand["stocks"];

            $totalQty = 0;
            $totalAmt = 0;

            echo "
              <div class=\"brand-client\">
                <h4>$brandCode - $brandName</h4>
                <table class=\"brand-results\">
                  <colgroup>
                    <col>
                    <col style=\"width: 40px;\">
                    <col style=\"width: 80px;\">
                    <col style=\"width: 80px;\">
                    <col style=\"width: 80px;\">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Model No.</th>
                      <th>W.C.</th>
                      <th class=\"number\">Qty</th>
                      <th class=\"number\">Average Cost</th>
                      <th class=\"number\">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody>
            ";

            foreach ($brandStocks as $modelNo => $model) {
              $modelId = $model["id"];
              $modelStocks = $model["stocks"];

                for ($i = 0; $i < count($modelStocks); $i++) {
                  $modelStock = $modelStocks[$i];
                  $qty = $modelStock["qty"];
                  $warehouseCode = $modelStock["warehouse_code"];
                  $costAverage = $modelStock["cost_average"];
                  $subtotal = $modelStock["subtotal"];

                  $totalQty += $qty;
                  $totalAmt += $subtotal;

                  $modelColumns = $i == 0 ? "
                    <td rowspan=\"" . count($modelStocks) . "\" title=\"$modelNo\">
                      <a class=\"link\" href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$modelId\">$modelNo</a>
                    </td>
                  " : "";
                  $amountColumns = $i == 0 ? "
                    <td rowspan=\"" . count($modelStocks) . "\" title=\"$costAverage\" class=\"number\">
                    " . number_format($costAverage, 2) . "
                    </td>
                    <td rowspan=\"" . count($modelStocks) . "\" title=\"$subtotal\" class=\"number\">
                    " . number_format($subtotal, 2) . "
                    </td>
                  " : "";

                  echo "
                    <tr>
                      $modelColumns
                      <td title=\"$warehouseCode\">$warehouseCode</td>
                      <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                      $amountColumns
                    </tr>
                  ";
                }
              }

            echo "
                  </tbody>
                  <tfoot>
                    <tr>
                      <th class=\"number\">Total:</th>
                      <th></th>
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
          echo "<div class=\"brand-client-no-results\">No results</div>";
        }
      ?>
    </div>
  </body>
</html>
