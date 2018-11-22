<?php
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $warehouseCode = $_POST["warehouse_code"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  if (assigned($debtorCode) && assigned($currencyCode) && assigned($exchangeRate) && assigned($discount) && assigned($tax) && assigned($warehouseCode) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($prices) && assigned($qtys)) {
    $plNo = "INV" . date("YmdHis");
    $date = date("Y-m-d");

    query("
      INSERT INTO
        `pl_header`
          (pl_no, pl_date, debtor_code, currency_code, exchange_rate, discount, tax, warehouse_code)
        VALUES
          (\"$plNo\", \"$date\", \"$debtorCode\", \"$currencyCode\", \"$exchangeRate\", \"$discount\", \"$tax\", \"$warehouseCode\")
    ");

    $values = array();

    for ($i = 0; $i < count($soNos); $i++) {
      $soNo = $soNos[$i];
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $price = $prices[$i];
      $qty = $qtys[$i];

      array_push($values, "(\"$plNo\", \"$i\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")");
    }

    query("
      INSERT INTO
        `pl_model`
          (pl_no, pl_index, so_no, brand_code, model_no, price, qty)
          VALUES
      " . join(", ", $values));

    header("Location: " . PACKING_LIST_URL . "?pl_no=$plNo");
  }

  $InBaseCurrCol = "(in " . COMPANY_CURRENCY . ")";

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "e.code='$d'"; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      e.code                                                                                  AS `debtor_code`,
      e.english_name                                                                          AS `debtor_name`,
      c.currency_code                                                                         AS `currency_code`,
      c.exchange_rate                                                                         AS `exchange_rate`,
      c.discount                                                                              AS `discount`,
      c.tax                                                                                   AS `tax`,
      IF(a.warehouse_code='', g.warehouse_code, a.warehouse_code)                             AS `warehouse_code`,
      a.so_no                                                                                 AS `so_no`,
      a.brand_code                                                                            AS `brand_code`,
      f.name                                                                                  AS `brand_name`,
      a.model_no                                                                              AS `model_no`,
      b.price                                                                                 AS `price`,
      b.qty_outstanding                                                                       AS `outstanding_qty`,
      a.qty                                                                                   AS `qty`,
      a.ia_no                                                                                 AS `ia_no`,
      d.cost_average                                                                          AS `unit_price`
    FROM
      `so_allotment` AS a
    LEFT JOIN
      `so_model` AS b
    ON a.so_no=b.so_no AND a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `so_header` AS c
    ON a.so_no=c.so_no
    LEFT JOIN
      `model` AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      `debtor` AS e
    ON c.debtor_code=e.code
    LEFT JOIN
      `brand` AS f
    ON a.brand_code=f.code
    LEFT JOIN
      `ia_header` AS g
    ON a.ia_no=g.ia_no
    WHERE
      a.qty IS NOT NULL
      $whereClause
    ORDER BY
      c.debtor_code ASC,
      CONCAT(c.currency_code, '-', c.exchange_rate) ASC,
      c.discount ASC,
      c.tax ASC,
      a.so_no ASC,
      a.brand_code ASC,
      a.model_no ASC
  ");

  $allotments = array();

  foreach ($results as $allotment) {
    $debtorCode = $allotment["debtor_code"];
    $debtorName = $allotment["debtor_name"];
    $currencyCode = $allotment["currency_code"];
    $exchangeRate = $allotment["exchange_rate"];
    $discount = $allotment["discount"];
    $tax = $allotment["tax"];
    $warehouseCode = $allotment["warehouse_code"];
    $allotmentModel = $allotment["so_no"] . "-" . $allotment["brand_code"] . "-" . $allotment["model_no"];

    if (!isset($allotments[$debtorCode])) {
      $allotments[$debtorCode] = array();
      $allotments[$debtorCode]["name"] = $debtorName;
      $allotments[$debtorCode]["models"] = array();
    }

    if (!isset($allotments[$debtorCode]["models"][$currencyCode])) {
      $allotments[$debtorCode]["models"][$currencyCode] = array();
      $allotments[$debtorCode]["models"][$currencyCode]["rate"] = $exchangeRate;
      $allotments[$debtorCode]["models"][$currencyCode]["models"] = array();
    }

    if (!isset($allotments[$debtorCode]["models"][$currencyCode]["models"][$discount])) {
      $allotments[$debtorCode]["models"][$currencyCode]["models"][$discount] = array();
    }

    if (!isset($allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax])) {
      $allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax] = array();
    }

    if (!isset($allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax][$warehouseCode])) {
      $allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax][$warehouseCode] = array();
    }

    if (!isset($allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax][$warehouseCode][$allotmentModel])) {
      $allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax][$warehouseCode][$allotmentModel] = array();
    }

    array_push($allotments[$debtorCode]["models"][$currencyCode]["models"][$discount][$tax][$warehouseCode][$allotmentModel], $allotment);
  }

  $results = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(b.english_name, 'Unknown')   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    ORDER BY
      a.debtor_code ASC
  ");

  $debtors = array();

  foreach ($results as $debtor) {
    $debtors[$debtor["code"]] = $debtor["name"];
  }
?>
