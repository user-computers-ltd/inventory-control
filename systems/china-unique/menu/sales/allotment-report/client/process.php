<?php
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $warehouseCode = $_POST["warehouse_code"];
  $iaNos = $_POST["ia_no"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];
  $removeIndex = $_POST["remove_index"];

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  /* If a form is submitted, create a new delivery order. */
  if (
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($tax) &&
    assigned($warehouseCode) &&
    assigned($iaNos) &&
    assigned($soNos) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($prices) &&
    assigned($qtys)
  ) {
    if (assigned($removeIndex)) {
      query("
        DELETE FROM
          `so_allotment`
        WHERE
          ia_no=\"$iaNos[$removeIndex]\" AND
          warehouse_code=\"$warehouseCode\" AND
          so_no=\"$soNos[$removeIndex]\" AND
          brand_code=\"$brandCodes[$removeIndex]\" AND
          model_no=\"$modelNos[$removeIndex]\"
      ");
    } else {
      $queries = array();

      $doNo = "DO" . date("YmdHis");
      $date = date("Y-m-d");

      array_push($queries, "
        INSERT INTO
          `sdo_header`
            (do_no, do_date, debtor_code, currency_code, exchange_rate, discount, tax, warehouse_code)
          VALUES
            (
              \"$doNo\",
              \"$date\",
              \"$debtorCode\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$discount\",
              \"$tax\",
              \"$warehouseCode\"
            )
      ");

      $values = array();

      for ($i = 0; $i < count($soNos); $i++) {
        $iaNo = $iaNos[$i];
        $soNo = $soNos[$i];
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = $prices[$i];
        $qty = $qtys[$i];

        array_push($values, "(\"$doNo\", \"$i\", \"$iaNo\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `sdo_model`
            (do_no, do_index, ia_no, so_no, brand_code, model_no, price, qty)
          VALUES
          " . join(", ", $values));
      }

      execute($queries);

      $doId = query("SELECT id FROM `sdo_header` WHERE do_no=\"$doNo\"")[0]["id"];

      header("Location: " . SALES_DELIVERY_ORDER_URL . "?id=$doId");
      exit(0);
    }
  }

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($d) { return "e.code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      e.code                                                        AS `debtor_code`,
      e.english_name                                                AS `debtor_name`,
      c.currency_code                                               AS `currency_code`,
      c.exchange_rate                                               AS `exchange_rate`,
      c.discount                                                    AS `discount`,
      c.tax                                                         AS `tax`,
      IFNULL(h.do_id, '')                                           AS `do_id`,
      IFNULL(h.do_no, '')                                           AS `do_no`,
      IF(a.warehouse_code='', g.warehouse_code, a.warehouse_code)   AS `warehouse_code`,
      a.so_no                                                       AS `so_no`,
      c.id                                                          AS `so_id`,
      a.brand_code                                                  AS `brand_code`,
      f.name                                                        AS `brand_name`,
      a.model_no                                                    AS `model_no`,
      b.price                                                       AS `price`,
      b.qty_outstanding                                             AS `outstanding_qty`,
      a.qty                                                         AS `qty`,
      a.ia_no                                                       AS `ia_no`,
      d.cost_average                                                AS `cost_average`
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
    LEFT JOIN
      (SELECT
        y.id              AS `do_id`,
        x.do_no           AS `do_no`,
        x.ia_no           AS `ia_no`,
        y.warehouse_code  AS `warehouse_code`,
        x.so_no           AS `so_no`,
        x.brand_code      AS `brand_code`,
        x.model_no        AS `model_no`
      FROM
        `sdo_model` AS x
      LEFT JOIN
        `sdo_header` AS y
      ON x.do_no=y.do_no) AS h
    ON
      a.ia_no=h.ia_no AND
      IF(a.warehouse_code='', g.warehouse_code, a.warehouse_code)=h.warehouse_code AND
      a.so_no=h.so_no AND
      a.brand_code=h.brand_code AND
      a.model_no=h.model_no
    WHERE
      a.qty IS NOT NULL
      $whereClause
    ORDER BY
      c.debtor_code ASC,
      CONCAT(c.currency_code, '-', c.exchange_rate) ASC,
      c.discount ASC,
      c.tax ASC,
      h.do_no,
      a.brand_code ASC,
      a.model_no ASC,
      a.so_no ASC
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
    $doId = $allotment["do_id"];
    $doNo = $allotment["do_no"];
    $allotmentModel = $allotment["so_no"] . "-" . $allotment["brand_code"] . "-" . $allotment["model_no"];

    $arrayPointer = &$allotments;

    if (!isset($arrayPointer[$debtorCode])) {
      $arrayPointer[$debtorCode] = array();
      $arrayPointer[$debtorCode]["name"] = $debtorName;
      $arrayPointer[$debtorCode]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$debtorCode]["models"];

    if (!isset($arrayPointer[$currencyCode])) {
      $arrayPointer[$currencyCode] = array();
      $arrayPointer[$currencyCode]["rate"] = $exchangeRate;
      $arrayPointer[$currencyCode]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$currencyCode]["models"];

    if (!isset($arrayPointer[$discount])) {
      $arrayPointer[$discount] = array();
    }
    $arrayPointer = &$arrayPointer[$discount];

    if (!isset($arrayPointer[$tax])) {
      $arrayPointer[$tax] = array();
    }
    $arrayPointer = &$arrayPointer[$tax];

    if (!isset($arrayPointer[$warehouseCode])) {
      $arrayPointer[$warehouseCode] = array();
    }
    $arrayPointer = &$arrayPointer[$warehouseCode];

    if (!isset($arrayPointer[$doNo])) {
      $arrayPointer[$doNo] = array();
      $arrayPointer[$doNo]["id"] = $doId;
      $arrayPointer[$doNo]["models"] = array();
    }
    $arrayPointer = &$arrayPointer[$doNo]["models"];

    if (!isset($arrayPointer[$allotmentModel])) {
      $arrayPointer[$allotmentModel] = array();
    }
    $arrayPointer = &$arrayPointer[$allotmentModel];

    array_push($arrayPointer, $allotment);
  }

  $results = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(c.english_name, 'Unknown')   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      `so_allotment` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      b.qty IS NOT NULL
    ORDER BY
      a.debtor_code ASC
  ");
  $debtors = array();
  foreach ($results as $debtor) {
    $debtors[$debtor["code"]] = $debtor["name"];
  }
?>