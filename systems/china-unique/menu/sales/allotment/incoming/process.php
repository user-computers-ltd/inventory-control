<?php
  $iaNos = $_POST["ia_no"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

  /* If a complete form is given, submit and update all IA allotments. */
  if (assigned($iaNos) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
    $iaNoClause = join(" OR ", array_map(function ($iaNo) { return "ia_no=\"$iaNo\""; }, $iaNos));
    query("DELETE FROM `so_allotment` WHERE $iaNoClause");

    $values = array();

    for ($i = 0; $i < count($iaNos); $i++) {
      $iaNo = $iaNos[$i];
      $soNo = $soNos[$i];
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $qty = $qtys[$i];

      if ($qty > 0) {
        array_push($values, "(\"$iaNo\", \"\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")");
      }
    }

    query("
      INSERT INTO
        `so_allotment`
          (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
        VALUES
    " . join(", ", $values));
  }

  $filterIaNos = $_GET["filter_ia_no"];
  $filterSoNos = $_GET["filter_so_no"];

  $whereClause = "";

  if (assigned($filterIaNos) && count($filterIaNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.ia_no='$i'"; }, $filterIaNos)) . ")";
  }

  $results = query("
    SELECT
      b.creditor_code                     AS `creditor_code`,
      c.english_name                      AS `creditor_name`,
      a.ia_no                             AS `ia_no`,
      DATE_FORMAT(b.ia_date, '%d-%m-%Y')  AS `date`,
      a.ia_index                          AS `index`,
      d.code                              AS `brand_code`,
      d.name                              AS `brand_name`,
      a.model_no                          AS `model_no`,
      a.qty                               AS `qty`
    FROM
      `ia_model` AS a
    LEFT JOIN
      `ia_header` AS b
    ON a.ia_no=b.ia_no
    LEFT JOIN
      `creditor` AS c
    ON b.creditor_code=c.code
    LEFT JOIN
      `brand` AS d
    ON a.brand_code=d.code
    WHERE
      b.status=\"DO\"
      $whereClause
    ORDER BY
      b.creditor_code ASC,
      a.ia_no ASC,
      a.ia_index ASC,
      a.model_no ASC
  ");

  $iaResults = array();

  foreach ($results as $model) {
    $creditorCode = $model["creditor_code"];
    $creditorName = $model["creditor_name"];
    $iaNo = $model["ia_no"];
    $date = $model["date"];
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];

    $arrayPointer = &$iaResults;

    if (!isset($arrayPointer[$creditorCode])) {
      $arrayPointer[$creditorCode] = array();
      $arrayPointer[$creditorCode]["name"] = $creditorName;
      $arrayPointer[$creditorCode]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$creditorCode]["models"];

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
      $arrayPointer[$iaNo]["date"] = $date;
      $arrayPointer[$iaNo]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$iaNo]["models"];

    array_push($arrayPointer, $model);
  }

  $iaModels = array();

  foreach ($results as $model) {
    $iaNo = $model["ia_no"];
    $brandCode = $model["brand_code"];
    $modelNo = $model["model_no"];

    $arrayPointer = &$iaModels;

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
    }
    $arrayPointer = &$arrayPointer[$iaNo];

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
      a.ia_no       AS `ia_no`,
      a.so_no       AS `so_no`,
      a.brand_code  AS `brand_code`,
      a.model_no    AS `model_no`,
      a.qty         AS `qty`
    FROM
      `so_allotment` AS a
    ORDER BY
      a.ia_no ASC,
      a.brand_code ASC,
      a.model_no ASC,
      a.so_no ASC
  ");

  $allotments = array();

  foreach ($results as $allotment) {
    $iaNo = $allotment["ia_no"];
    $brandCode = $allotment["brand_code"];
    $modelNo = $allotment["model_no"];
    $soNo = $allotment["so_no"];

    $arrayPointer = &$allotments;

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
    }
    $arrayPointer = &$arrayPointer[$iaNo];

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

  $ias = query("
    SELECT
      ia_no                               AS `ia_no`,
      DATE_FORMAT(ia_date, '%d-%m-%Y')    AS `date`
    FROM
      `ia_header`
    WHERE
      status=\"DO\"
    ORDER BY
      ia_no ASC
  ");

  $sos = query("
    SELECT DISTINCT
      so_no                               AS `so_no`
    FROM
      `so_model`
    WHERE
      qty_outstanding > 0
    ORDER BY
      so_no ASC
  ");
?>
