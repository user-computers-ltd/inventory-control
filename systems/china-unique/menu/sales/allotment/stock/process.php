<?php
  $warehouseCodes = $_POST["warehouse_code"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

  /* If a complete form is given, submit and update all IA allotments. */
  if (assigned($warehouseCodes) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
    $whereClause = join(" OR ", array_map(function ($code) { return "warehouse_code=\"$code\""; }, $warehouseCodes));
    query("DELETE FROM `so_allotment` WHERE $whereClause");

    $values = array();

    for ($i = 0; $i < count($warehouseCodes); $i++) {
      $warehouseCode = $warehouseCodes[$i];
      $soNo = $soNos[$i];
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $qty = $qtys[$i];

      if ($qty > 0) {
        array_push($values, "(\"\", \"$warehouseCode\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")");
      }
    }

    query("
      INSERT INTO
        `so_allotment`
          (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
        VALUES
    " . join(", ", $values));
  }

  $filterWarehouseCodes = $_GET["filter_warehouse_code"];
  $filterSoNos = $_GET["filter_so_no"];

  $whereClause = "";

  if (assigned($filterWarehouseCodes) && count($filterWarehouseCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.warehouse_code='$i'"; }, $filterWarehouseCodes)) . ")";
  }

  $whereSoModelClause = "";

  if (assigned($filterSoNos) && count($filterSoNos) > 0) {
    $whereSoModelClause = $whereSoModelClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "so_no='$i'"; }, $filterSoNos)) . ")";
  }

  $results = query("
    SELECT
      b.code                                AS `warehouse_code`,
      b.name                                AS `warehouse_name`,
      c.code                                AS `brand_code`,
      c.name                                AS `brand_name`,
      a.model_no                            AS `model_no`,
      a.qty                                 AS `qty`
    FROM
      `stock` AS a
    LEFT JOIN
      `warehouse` AS b
    ON a.warehouse_code=b.code
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      (SELECT
        brand_code            AS `brand_code`,
        model_no              AS `model_no`,
        SUM(qty_outstanding)  AS `qty_outstanding`
      FROM
        `so_model`
      WHERE
        qty_outstanding > 0
        $whereSoModelClause
      GROUP BY
        brand_code, model_no) AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    WHERE
      a.qty > 0 AND d.qty_outstanding > 0
      $whereClause
    ORDER BY
      b.code ASC,
      c.code ASC,
      a.model_no ASC
  ");

  $stockResults = array();

  foreach ($results as $model) {
    $warehouseCode = $model["warehouse_code"];
    $warehouseName = $model["warehouse_name"];

    $arrayPointer = &$stockResults;

    if (!isset($arrayPointer[$warehouseCode])) {
      $arrayPointer[$warehouseCode] = array();
      $arrayPointer[$warehouseCode]["name"] = $warehouseName;
      $arrayPointer[$warehouseCode]["models"] = array();
    }

    $arrayPointer = &$arrayPointer[$warehouseCode]["models"];

    array_push($arrayPointer, $model);
  }

  $stockModels = array();

  foreach ($results as $model) {
    $warehouseCode = $model["warehouse_code"];
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];

    $arrayPointer = &$stockModels;

    if (!isset($arrayPointer[$warehouseCode])) {
      $arrayPointer[$warehouseCode] = array();
    }
    $arrayPointer = &$arrayPointer[$warehouseCode];

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo];

    $arrayPointer = $model;
  }

  $whereClause = "";

  if (assigned($filterSoNos) && count($filterSoNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.so_no='$i'"; }, $filterSoNos)) . ")";
  }

  $results = query("
    SELECT
      b.debtor_code                       AS `debtor_code`,
      IFNULL(c.english_name, 'Unknown')   AS `debtor_name`,
      a.so_no                             AS `so_no`,
      DATE_FORMAT(b.so_date, '%d-%m-%Y')  AS `date`,
      b.discount                          AS `discount`,
      b.currency_code                     AS `currency_code`,
      b.exchange_rate                     AS `exchange_rate`,
      b.tax                               AS `tax`,
      a.brand_code                        AS `brand_code`,
      a.model_no                          AS `model_no`,
      a.qty                               AS `qty_order`,
      a.qty_outstanding                   AS `qty_outstanding`,
      a.price                             AS `price`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON b.debtor_code=c.code
    WHERE
      a.qty_outstanding > 0
      $whereClause
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC,
      b.so_date ASC
  ");

  $soModels = array();

  foreach ($results as $model) {
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];
    $soNo = $model["so_no"];

    $arrayPointer = &$soModels;

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo];

    if (!isset($arrayPointer[$soNo])) {
      $arrayPointer[$soNo] = array();
    }
    $arrayPointer = &$arrayPointer[$soNo];

    $arrayPointer = $model;
  }

  $results = query("
    SELECT
      a.warehouse_code       AS `warehouse_code`,
      a.so_no       AS `so_no`,
      a.brand_code  AS `brand_code`,
      a.model_no    AS `model_no`,
      a.qty         AS `qty`
    FROM
      `so_allotment` AS a
    ORDER BY
      a.warehouse_code ASC,
      a.brand_code ASC,
      a.model_no ASC,
      a.so_no ASC
  ");

  $allotments = array();

  foreach ($results as $allotment) {
    $warehouseCode = $allotment["warehouse_code"];
    $brandCode = $allotment["brand_code"];
    $modelNo = $allotment["model_no"];
    $soNo = $allotment["so_no"];

    $arrayPointer = &$allotments;

    if (!isset($arrayPointer[$warehouseCode])) {
      $arrayPointer[$warehouseCode] = array();
    }
    $arrayPointer = &$arrayPointer[$warehouseCode];

    if (!isset($arrayPointer[$brandCode])) {
      $arrayPointer[$brandCode] = array();
    }
    $arrayPointer = &$arrayPointer[$brandCode];

    if (!isset($arrayPointer[$modelNo])) {
      $arrayPointer[$modelNo] = array();
    }
    $arrayPointer = &$arrayPointer[$modelNo];

    if (!isset($arrayPointer[$soNo])) {
      $arrayPointer[$soNo] = array();
    }
    $arrayPointer = &$arrayPointer[$soNo];

    $arrayPointer = $allotment;
  }

  $warehouses = query("
    SELECT
      code    AS `warehouse_code`,
      name    AS `warehouse_name`
    FROM
      `warehouse`
    ORDER BY
      name ASC
  ");

  $sos = query("
    SELECT DISTINCT
      so_no   AS `so_no`
    FROM
      `so_model`
    WHERE
      qty_outstanding > 0
    ORDER BY
      so_no ASC
  ");
?>