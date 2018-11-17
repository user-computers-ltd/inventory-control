<?php
  $warehouseCodes = $_POST["warehouse_code"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

  if (assigned($warehouseCodes) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
    $warehouseClause = join(" OR ", array_map(function ($warehouseCode) { return "warehouse_code=\"$warehouseCode\""; }, $warehouseCodes));
    query("DELETE FROM `so_allotment` WHERE $warehouseClause");

    $values = array();

    for ($i = 0; $i < count($warehouseCodes); $i++) {
      $warehouseCode = $warehouseCodes[$i];
      $soNo = $soNos[$i];
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $qty = $qtys[$i];

      array_push($values, "(\"\", \"$warehouseCode\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")");
    }

    query("
      INSERT INTO
        `so_allotment`
          (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
        VALUES
    " . join(", ", $values));
  }

  $hasFilter = false;

  $filterModelNos = $_GET["filter_model_no"];
  $stockModels = array();

  if (assigned($filterModelNos) && count($filterModelNos) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($m) { return "a.model_no='$m'"; }, $filterModelNos)) . ")";
    $hasFilter = true;
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
      WHERE
        a.qty > 0
        $whereClause
      ORDER BY
        b.code ASC,
        c.code ASC,
        a.model_no ASC
    ");

    foreach ($results as $stockModel) {
      $warehouseCode = $stockModel["warehouse_code"];
      $warehouseName = $stockModel["warehouse_name"];

      if (!isset($stockModels[$warehouseCode])) {
        $stockModels[$warehouseCode] = array();
        $stockModels[$warehouseCode]["name"] = $warehouseName;
        $stockModels[$warehouseCode]["models"] = array();
      }

      array_push($stockModels[$warehouseCode]["models"], $stockModel);
    }
  }

  $results = query("
    SELECT
      a.brand_code                                                                        AS `brand_code`,
      a.model_no                                                                          AS `model_no`,
      b.debtor_code                                                                       AS `debtor_code`,
      IFNULL(c.english_name, 'Unknown')                                                   AS `debtor_name`,
      DATE_FORMAT(b.so_date, '%d-%m-%Y')                                                  AS `date`,
      a.so_no                                                                             AS `so_no`,
      a.price * (100 - b.discount) / 100 * b.exchange_rate                                AS `selling_price`,
      a.qty_outstanding                                                                   AS `qty_outstanding`
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
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC,
      b.so_date ASC
  ");

  $soModels = array();

  foreach ($results as $soModel) {
    $brandCode = $soModel["brand_code"];
    $modelNo = $soModel["model_no"];

    if (!isset($soModels[$brandCode])) {
      $soModels[$brandCode] = array();
    }

    if (!isset($soModels[$brandCode][$modelNo])) {
      $soModels[$brandCode][$modelNo] = array();
    }

    array_push($soModels[$brandCode][$modelNo], $soModel);
  }

  $results = query("
    SELECT
      a.warehouse_code    AS `warehouse_code`,
      a.so_no             AS `so_no`,
      a.brand_code        AS `brand_code`,
      a.model_no          AS `model_no`,
      a.qty               AS `qty`
    FROM
      `so_allotment` AS a
    ORDER BY
      a.warehouse_code ASC,
      a.brand_code ASC,
      a.model_no ASC
  ");

  $allotments = array();

  foreach ($results as $allotment) {
    $warehouseCode = $allotment["warehouse_code"];
    $brandCode = $allotment["brand_code"];
    $modelNo = $allotment["model_no"];

    if (!isset($allotments[$warehouseCode])) {
      $allotments[$warehouseCode] = array();
    }

    if (!isset($allotments[$warehouseCode][$brandCode])) {
      $allotments[$warehouseCode][$brandCode] = array();
    }

    if (!isset($allotments[$warehouseCode][$brandCode][$modelNo])) {
      $allotments[$warehouseCode][$brandCode][$modelNo] = array();
    }

    array_push($allotments[$warehouseCode][$brandCode][$modelNo], $allotment);
  }

  $modelNos = query("
    SELECT DISTINCT
      a.model_no
    FROM
      `model` AS a
    LEFT JOIN
      `stock` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    WHERE
      b.qty > 0
    ORDER BY
      a.model_no ASC
    ");
?>
