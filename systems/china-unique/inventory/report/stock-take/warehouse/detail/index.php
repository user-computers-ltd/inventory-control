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
      z.warehouse_code                            AS `warehouse_code`,
      d.name                                      AS `warehouse_name`,
      z.brand_code                                AS `brand_code`,
      c.name                                      AS `brand_name`,
      b.id                                        AS `model_id`,
      z.model_no                                  AS `model_no`,
      IFNULL(a.qty, 0)                            AS `qty`,
      f.qty_on_loan                               AS `qty_on_loan`,
      f.qty_on_borrow                             AS `qty_on_borrow`,
      e.qty_on_reserve                            AS `qty_on_reserve`,
      b.cost_average                              AS `cost_average`,
      ROUND(a.qty * b.cost_average, 2)            AS `subtotal`
    FROM
      (SELECT x.brand_code, x.model_no, y.code AS `warehouse_code` FROM `model` AS x CROSS JOIN `warehouse` AS y) AS z
    LEFT JOIN
      `stock` AS a
    ON z.warehouse_code=a.warehouse_code AND z.brand_code=a.brand_code AND z.model_no=a.model_no
    LEFT JOIN
      `model` AS b
    ON z.brand_code=b.brand_code AND z.model_no=b.model_no
    LEFT JOIN
      `brand` AS c
    ON z.brand_code=c.code
    LEFT JOIN
      `warehouse` AS d
    ON z.warehouse_code=d.code
    LEFT JOIN
      (SELECT
        h.warehouse_code  AS `warehouse_code`,
        m.brand_code      AS `brand_code`,
        m.model_no        AS `model_no`,
        SUM(m.qty)        AS `qty_on_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        h.status=\"SAVED\" AND
        m.ia_no=\"\"
      GROUP BY
        h.warehouse_code, m.brand_code, m.model_no) AS e
    ON z.warehouse_code=e.warehouse_code AND z.brand_code=e.brand_code AND z.model_no=e.model_no
    LEFT JOIN
      (SELECT
        brand_code,
        model_no,
        warehouse_code,
        SUM(IF(transaction_code=\"S7\", qty, 0)) - SUM(IF(transaction_code=\"R8\", qty, 0)) AS `qty_on_loan`,
        SUM(IF(transaction_code=\"R7\", qty, 0)) - SUM(IF(transaction_code=\"S8\", qty, 0)) AS `qty_on_borrow`
      FROM
        `transaction`
      GROUP BY
        brand_code, model_no, warehouse_code) AS f
    ON z.warehouse_code=f.warehouse_code AND z.brand_code=f.brand_code AND z.model_no=f.model_no
    WHERE
      (a.qty > 0 OR
      f.qty_on_loan != 0 OR
      f.qty_on_borrow != 0)
      $whereClause
    ORDER BY
      z.warehouse_code ASC,
      z.brand_code ASC,
      z.model_no ASC
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
      a.qty IS NOT NULL
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
      a.qty IS NOT NULL
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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
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
      <?php if (count($stocks) > 0) : ?>
        <?php foreach ($stocks as $warehouseCode => &$warehouse) : ?>
          <div class="warehouse-client">
            <h4><?php echo $warehouseCode . " - " . $warehouse["name"]; ?></h4>
            <table class="warehouse-results">
              <colgroup>
                <col>
                <col>
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 80px;">
                <col style="width: 110px;">
                <col style="width: 80px;">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Brand</th>
                  <th>Model No.</th>
                  <th class="number">Loaned</th>
                  <th class="number">Borrowed</th>
                  <th class="number">Qty</th>
                  <th class="number">Reserved</th>
                  <th class="number">Available</th>
                  <th class="number">Average Cost</th>
                  <th class="number">Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $warehouseStocks = $warehouse["stocks"];

                  $totalQtyOnLoan = 0;
                  $totalQtyOnBorrow = 0;
                  $totalQty = 0;
                  $totalQtyOnReserve = 0;
                  $totalQtyAvailable = 0;
                  $totalAmt = 0;

                  foreach ($warehouseStocks as $brandCode => $brand) {
                    $brandName = $brand["name"];
                    $brandStocks = $brand["stocks"];

                    foreach ($brandStocks as $modelNo => $model) {
                      $modelId = $model["id"];
                      $modelStocks = $model["stocks"];
                      $stockCount = count($modelStocks);

                      for ($i = 0; $i < $stockCount; $i++) {
                        $modelStock = $modelStocks[$i];
                        $qtyOnLoan = $modelStock["qty_on_loan"];
                        $qtyOnBorrow = $modelStock["qty_on_borrow"];
                        $qty = $modelStock["qty"];
                        $qtyOnReserve = $modelStock["qty_on_reserve"];
                        $qtyAvailable = $qty - $qtyOnReserve;
                        $costAverage = $modelStock["cost_average"];
                        $subtotal = $modelStock["subtotal"];

                        $totalQtyOnLoan += $qtyOnLoan;
                        $totalQtyOnBorrow += $qtyOnBorrow;
                        $totalQty += $qty;
                        $totalQtyOnReserve += $qtyOnReserve;
                        $totalQtyAvailable += $qtyAvailable;
                        $totalAmt += $subtotal;

                        echo "
                          <tr>
                            <td title=\"$brandName\">$brandCode - $brandName</td>
                            <td title=\"$modelNo\"><a class=\"link\" href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$modelId\">$modelNo</a></td>
                            <td title=\"$qtyOnLoan\" class=\"number\">" . number_format($qtyOnLoan) . "</td>
                            <td title=\"$qtyOnBorrow\" class=\"number\">" . number_format($qtyOnBorrow) . "</td>
                            <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                            <td title=\"$qtyOnReserve\" class=\"number\">" . number_format($qtyOnReserve) . "</td>
                            <td title=\"$qtyAvailable\" class=\"number\">" . number_format($qtyAvailable) . "</td>
                            <td title=\"$costAverage\" class=\"number\">" . number_format($costAverage, 6) . "</td>
                            <td title=\"$subtotal\" class=\"number\">" . number_format($subtotal, 2) . "</td>
                          </tr>
                        ";
                      }
                    }
                  }
                ?>
                <tr>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQtyOnLoan); ?></th>
                  <th class="number"><?php echo number_format($totalQtyOnBorrow); ?></th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($totalQtyOnReserve); ?></th>
                  <th class="number"><?php echo number_format($totalQtyAvailable); ?></th>
                  <th></th>
                  <th class="number"><?php echo number_format($totalAmt, 2); ?></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="warehouse-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
