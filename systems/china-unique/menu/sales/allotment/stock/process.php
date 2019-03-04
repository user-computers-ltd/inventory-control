<?php
  $warehouseCodes = $_POST["warehouse_code"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

  /* If a complete form is given, submit and update all IA allotments. */
  if (assigned($warehouseCodes) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
    $queries = array();

    $whereClause = "";

    $whereClause = join(" OR ", array_map(function ($warehouseCode, $soNo, $brandCode, $modelNo) {
      return "
        warehouse_code=\"$warehouseCode\" AND
        so_no=\"$soNo\" AND
        brand_code=\"$brandCode\" AND
        model_no=\"$modelNo\"
      ";
    }, $warehouseCodes, $soNos, $brandCodes, $modelNos));
    array_push($queries, "DELETE FROM `so_allotment` WHERE $whereClause");

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

    if (count($values) > 0) {
      array_push($queries, "
        INSERT INTO
          `so_allotment`
            (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
          VALUES
      " . join(", ", $values));
    }

    execute($queries);

    header("Location: " . SALES_ALLOTMENT_REPORT_CUSTOMER_URL);
  }

  $filterWarehouseCodes = $_GET["filter_warehouse_code"];
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $filterSONos = $_GET["filter_so_no"];

  $whereClause = "";

  if (assigned($filterWarehouseCodes) && count($filterWarehouseCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.warehouse_code=\"$i\""; }, $filterWarehouseCodes)) . ")";
  }

  $whereSoModelClause = "";
  $whereSoAllotmentClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereSoModelClause = $whereSoModelClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "y.debtor_code=\"$i\""; }, $filterDebtorCodes)) . ")";
    $whereSoAllotmentClause = $whereSoAllotmentClause . "
      AND (" . join(" AND ", array_map(function ($i) { return "y.debtor_code!=\"$i\""; }, $filterDebtorCodes)) . ")";
  } else {
    $whereSoAllotmentClause = $whereSoAllotmentClause . " AND y.debtor_code=\"\"";
  }

  if (assigned($filterSONos) && count($filterSONos) > 0) {
    $whereSoModelClause = $whereSoModelClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "y.so_no=\"$i\""; }, $filterSONos)) . ")";
    $whereSoAllotmentClause = $whereSoAllotmentClause . "
      AND (" . join(" AND ", array_map(function ($i) { return "y.so_no!=\"$i\""; }, $filterSONos)) . ")";
  } else {
    $whereSoAllotmentClause = $whereSoAllotmentClause . " AND y.so_no=\"\"";
  }

  $results = query("
    SELECT
      b.code                                AS `warehouse_code`,
      b.name                                AS `warehouse_name`,
      c.code                                AS `brand_code`,
      c.name                                AS `brand_name`,
      a.model_no                            AS `model_no`,
      a.qty                                 AS `qty`,
      a.qty - IFNULL(e.qty_allotted, 0)     AS `qty_available`
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
        x.brand_code            AS `brand_code`,
        x.model_no              AS `model_no`,
        SUM(x.qty_outstanding)  AS `qty_outstanding`
      FROM
        `so_model` AS x
      LEFT JOIN
        `so_header` AS y
      ON x.so_no=y.so_no
      WHERE
        y.status=\"CONFIRMED\"
        $whereSoModelClause
      GROUP BY
        x.brand_code, x.model_no) AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      (SELECT
        warehouse_code        AS `warehouse_code`,
        brand_code            AS `brand_code`,
        model_no              AS `model_no`,
        SUM(qty)              AS `qty_allotted`
      FROM
        `so_allotment` AS x
      LEFT JOIN
        `so_header` AS y
      ON x.so_no=y.so_no
      WHERE
        x.warehouse_code!=\"\"
        $whereSoAllotmentClause
      GROUP BY
        warehouse_code, brand_code, model_no) AS e
    ON a.warehouse_code=e.warehouse_code AND a.brand_code=e.brand_code AND a.model_no=e.model_no
    WHERE
      a.qty > 0 AND IFNULL(d.qty_outstanding, 0) > 0
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

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "b.debtor_code=\"$i\""; }, $filterDebtorCodes)) . ")";
  }

  if (assigned($filterSONos) && count($filterSONos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "b.so_no=\"$i\""; }, $filterSONos)) . ")";
  }

  $results = query("
    SELECT
      b.debtor_code                       AS `debtor_code`,
      IFNULL(c.english_name, 'Unknown')   AS `debtor_name`,
      a.so_no                             AS `so_no`,
      b.id                                AS `so_id`,
      DATE_FORMAT(b.so_date, '%d-%m-%Y')  AS `date`,
      b.discount                          AS `discount`,
      b.currency_code                     AS `currency_code`,
      b.exchange_rate                     AS `exchange_rate`,
      b.tax                               AS `tax`,
      b.priority                          AS `priority`,
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
      a.qty_outstanding > 0 AND b.status=\"CONFIRMED\"
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
      IFNULL(b.do_no, '')     AS `do_no`,
      a.warehouse_code        AS `warehouse_code`,
      a.so_no                 AS `so_no`,
      a.brand_code            AS `brand_code`,
      a.model_no              AS `model_no`,
      a.qty                   AS `qty`
    FROM
      `so_allotment` AS a
    LEFT JOIN
      (SELECT
        x.do_no           AS `do_no`,
        y.warehouse_code  AS `warehouse_code`,
        x.so_no           AS `so_no`,
        x.brand_code      AS `brand_code`,
        x.model_no        AS `model_no`,
        x.qty             AS `qty`
      FROM
        `sdo_model` AS x
      LEFT JOIN
        `sdo_header` AS y
      ON x.do_no=y.do_no
      WHERE
        y.status=\"SAVED\") AS b
    ON
      a.warehouse_code=b.warehouse_code AND
      a.so_no=b.so_no AND
      a.brand_code=b.brand_code AND
      a.model_no=b.model_no AND
      a.qty=b.qty
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
      code AS `code`,
      name AS `name`
    FROM
      `warehouse`
    ORDER BY
      name ASC
  ");

  $debtors = query("
    SELECT DISTINCT
      code            AS `code`,
      english_name    AS `name`
    FROM
      `debtor`
    ORDER BY
      code ASC
  ");

  $soNos = query("
    SELECT DISTINCT
      a.so_no            AS `so_no`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                 AS `so_no`,
        SUM(qty_outstanding)  AS `qty_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    WHERE
      b.qty_outstanding > 0
    ORDER BY
      a.so_no ASC
  ");
?>
