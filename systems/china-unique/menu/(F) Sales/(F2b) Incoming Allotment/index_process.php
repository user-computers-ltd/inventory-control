<?php
  $iaNos = $_POST["ia_no"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

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

      array_push($values, "(\"$iaNo\", \"\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")");
    }

    query("
      INSERT INTO
        `so_allotment`
          (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
        VALUES
    " . join(", ", $values));
  }

  $hasFilter = false;

  $filterIaNos = $_GET["filter_ia_no"];
  $iaModels = array();

  if (assigned($filterIaNos) && count($filterIaNos) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($i) { return "a.ia_no='$i'"; }, $filterIaNos)) . ")";
    $hasFilter = true;
    $results = query("
      SELECT
        a.ia_no                                                             AS `ia_no`,
        DATE_FORMAT(b.ia_date, '%d-%m-%Y')                                  AS `date`,
        a.ia_index                                                          AS `index`,
        d.code                                                              AS `brand_code`,
        d.name                                                              AS `brand_name`,
        a.model_no                                                          AS `model_no`,
        a.qty                                                               AS `qty_available`
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
        a.ia_no IS NOT NULL
        $whereClause
      ORDER BY
        a.ia_no ASC,
        b.ia_date DESC,
        a.ia_index ASC,
        a.model_no ASC
    ");

    foreach ($results as $iaModel) {
      $iaNo = $iaModel["ia_no"];
      $date = $iaModel["date"];

      if (!isset($iaModels[$iaNo])) {
        $iaModels[$iaNo] = array();
        $iaModels[$iaNo]["date"] = $date;
        $iaModels[$iaNo]["models"] = array();
      }

      array_push($iaModels[$iaNo]["models"], $iaModel);
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
      a.model_no ASC
  ");

  $allotments = array();

  foreach ($results as $allotment) {
    $iaNo = $allotment["ia_no"];
    $brandCode = $allotment["brand_code"];
    $modelNo = $allotment["model_no"];

    if (!isset($allotments[$iaNo])) {
      $allotments[$iaNo] = array();
    }

    if (!isset($allotments[$iaNo][$brandCode])) {
      $allotments[$iaNo][$brandCode] = array();
    }

    if (!isset($allotments[$iaNo][$brandCode][$modelNo])) {
      $allotments[$iaNo][$brandCode][$modelNo] = array();
    }

    array_push($allotments[$iaNo][$brandCode][$modelNo], $allotment);
  }

  $ias = query("
    SELECT
      a.ia_no                               AS `ia_no`,
      DATE_FORMAT(a.ia_date, '%d-%m-%Y')    AS `date`
    FROM
      `ia_header` AS a
    ORDER BY
      a.ia_no ASC
  ");
?>
