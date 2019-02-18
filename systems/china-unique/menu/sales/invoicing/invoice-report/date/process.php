<?php
  $periods = array_map(function ($p) { return $p["period"]; }, query("
    SELECT DISTINCT
      DATE_FORMAT(do_date, \"%Y-%m\")           AS `period`
    FROM
      `sdo_header`
    WHERE
      status=\"POSTED\"
    UNION
    SELECT DISTINCT
      DATE_FORMAT(stock_out_date, \"%Y-%m\")    AS `period`
    FROM
      `stock_out_header`
    WHERE
      status=\"POSTED\"
    ORDER BY
      period DESC
  "));

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $period = assigned($_GET["period"]) ? $_GET["period"] : (count($periods) > 0 ? $periods[0] : "");
  $debtorCodes = $_GET["debtor_code"];

  $doWhereClause = "";
  $stockOutWhereClause = "";
  $invoiceWhereClause = "";
  $currentInvoiceWhereClause = "";

  if (assigned($period)) {
    $doWhereClause = $doWhereClause . "
      AND (IFNULL(b.amount, 0) * (100 - a.discount) / 100) - IFNULL(e.invoice_sum, 0) > 0
      AND DATE_FORMAT(a.do_date, \"%Y-%m\") <= \"$period\"";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND (IFNULL(b.amount, 0) * (100 - a.discount) / 100) - IFNULL(e.invoice_sum, 0) > 0
      AND DATE_FORMAT(a.stock_out_date, \"%Y-%m\") <= \"$period\"";
    $invoiceWhereClause = $invoiceWhereClause . "
      y.invoice_date < \"$period-01\"";
    $currentInvoiceWhereClause = $currentInvoiceWhereClause . "
      DATE_FORMAT(y.invoice_date, \"%Y-%m\")=\"$period\"";
  }

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $doWhereClause = $doWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
  }

  $incomeHeaders = array();

  $results = query("
    SELECT
      a.do_date                                                                     AS `date_`,
      DATE_FORMAT(a.do_date, '%d-%m-%Y')                                            AS `date`,
      DATE_FORMAT(a.do_date, '%Y-%m')                                               AS `period`,
      b.count                                                                       AS `count`,
      a.id                                                                          AS `do_id`,
      a.do_no                                                                       AS `do_no`,
      \"\"                                                                          AS `stock_out_id`,
      \"\"                                                                          AS `stock_out_no`,
      a.debtor_code                                                                 AS `debtor_code`,
      IFNULL(c.english_name, 'Unknown')                                             AS `debtor_name`,
      IFNULL(b.qty, 0)                                                              AS `qty`,
      a.currency_code                                                               AS `currency`,
      IFNULL(b.amount, 0) * (100 - a.discount) / 100                                AS `amount`,
      (IFNULL(b.amount, 0) * (100 - a.discount) / 100) - IFNULL(e.invoice_sum, 0)   AS `pending`,
      IFNULL(b.amount, 0) * (100 - a.discount) / (100 + a.tax)                      AS `net`,
      IFNULL(b.cost, 0)                                                             AS `cost`,
      IFNULL(d.invoice_amounts, \"\")                                               AS `invoice_amounts`,
      IFNULL(d.invoice_dates, \"\")                                                 AS `invoice_dates`,
      IFNULL(d.invoice_nos, \"\")                                                   AS `invoice_nos`,
      IFNULL(d.invoice_ids, \"\")                                                   AS `invoice_ids`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      (SELECT
        x.do_no                       AS `do_no`,
        COUNT(*)                      AS `count`,
        SUM(x.qty)                    AS `qty`,
        SUM(x.qty * x.price)          AS `amount`,
        SUM(x.qty * y.cost_average)   AS `cost`
      FROM
        `sdo_model` AS x
      LEFT JOIN
        `model` AS y
      ON x.brand_code=y.brand_code AND x.model_no=y.model_no
      GROUP BY
        do_no) AS b
    ON a.do_no=b.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    LEFT JOIN
      (SELECT
        x.do_no                       AS `do_no`,
        GROUP_CONCAT(y.invoice_date)  AS `invoice_dates`,
        GROUP_CONCAT(y.id)            AS `invoice_ids`,
        GROUP_CONCAT(x.invoice_no)    AS `invoice_nos`,
        GROUP_CONCAT(x.amount)        AS `invoice_amounts`
      FROM
        `out_inv_model` AS x
      LEFT JOIN
        `out_inv_header` AS y
      ON x.invoice_no=y.invoice_no
      WHERE
        $currentInvoiceWhereClause
      GROUP BY
        x.do_no) AS d
    ON a.do_no=d.do_no
    LEFT JOIN
      (SELECT
        x.do_no                       AS `do_no`,
        SUM(x.amount)                 AS `invoice_sum`
      FROM
        `out_inv_model` AS x
      LEFT JOIN
        `out_inv_header` AS y
      ON x.invoice_no=y.invoice_no
      WHERE
        $invoiceWhereClause
      GROUP BY
        x.do_no) AS e
    ON a.do_no=e.do_no
    WHERE
      a.status=\"POSTED\"
      $doWhereClause
    UNION
    SELECT
      a.stock_out_date                                                              AS `date_`,
      DATE_FORMAT(a.stock_out_date, '%d-%m-%Y')                                     AS `date`,
      DATE_FORMAT(a.stock_out_date, '%Y-%m')                                        AS `period`,
      b.count                                                                       AS `count`,
      \"\"                                                                          AS `do_id`,
      \"\"                                                                          AS `do_no`,
      a.id                                                                          AS `stock_out_id`,
      a.stock_out_no                                                                AS `stock_out_no`,
      a.debtor_code                                                                 AS `debtor_code`,
      IFNULL(c.english_name, 'Unknown')                                             AS `debtor_name`,
      IFNULL(b.qty, 0)                                                              AS `qty`,
      a.currency_code                                                               AS `currency`,
      IFNULL(b.amount, 0) * (100 - a.discount) / 100                                AS `amount`,
      (IFNULL(b.amount, 0) * (100 - a.discount) / 100) - IFNULL(e.invoice_sum, 0)   AS `pending`,
      IFNULL(b.amount, 0) * (100 - a.discount) / (100 + a.tax)                      AS `net`,
      IFNULL(b.cost, 0)                                                             AS `cost`,
      IFNULL(d.invoice_amounts, \"\")                                               AS `invoice_amounts`,
      IFNULL(d.invoice_dates, \"\")                                                 AS `invoice_dates`,
      IFNULL(d.invoice_nos, \"\")                                                   AS `invoice_nos`,
      IFNULL(d.invoice_ids, \"\")                                                   AS `invoice_ids`
    FROM
      `stock_out_header` AS a
    LEFT JOIN
      (SELECT
        x.stock_out_no                AS `stock_out_no`,
        COUNT(*)                      AS `count`,
        SUM(x.qty)                    AS `qty`,
        SUM(x.qty * x.price)          AS `amount`,
        SUM(x.qty * y.cost_average)   AS `cost`
      FROM
        `stock_out_model` AS x
      LEFT JOIN
        `model` AS y
      ON x.brand_code=y.brand_code AND x.model_no=y.model_no
      GROUP BY
        stock_out_no) AS b
    ON a.stock_out_no=b.stock_out_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    LEFT JOIN
      (SELECT
        x.stock_out_no                AS `stock_out_no`,
        GROUP_CONCAT(y.invoice_date)  AS `invoice_dates`,
        GROUP_CONCAT(y.id)            AS `invoice_ids`,
        GROUP_CONCAT(x.invoice_no)    AS `invoice_nos`,
        GROUP_CONCAT(x.amount)        AS `invoice_amounts`
      FROM
        `out_inv_model` AS x
      LEFT JOIN
        `out_inv_header` AS y
      ON x.invoice_no=y.invoice_no
      WHERE
        $currentInvoiceWhereClause
      GROUP BY
        x.stock_out_no) AS d
    ON a.stock_out_no=d.stock_out_no
    LEFT JOIN
      (SELECT
        x.stock_out_no                AS `stock_out_no`,
        SUM(x.amount)                 AS `invoice_sum`
      FROM
        `out_inv_model` AS x
      LEFT JOIN
        `out_inv_header` AS y
      ON x.invoice_no=y.invoice_no
      WHERE
        $invoiceWhereClause
      GROUP BY
        x.stock_out_no) AS e
    ON a.stock_out_no=e.stock_out_no
    WHERE
      a.status=\"POSTED\" AND (a.transaction_code=\"S1\" OR a.transaction_code=\"S2\")
      $stockOutWhereClause
    ORDER BY
      debtor_code ASC,
      date_ ASC
  ");

  foreach ($results as $incomeHeader) {
    $currency = $incomeHeader["currency"];

    $arrayPointer = &$incomeHeaders;

    if (!isset($arrayPointer[$currency])) {
      $arrayPointer[$currency] = array();
    }
    $arrayPointer = &$arrayPointer[$currency];

    array_push($arrayPointer, $incomeHeader);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    WHERE
      a.status=\"POSTED\"
    UNION
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `stock_out_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    WHERE
      a.status=\"POSTED\"
    ORDER BY
      code ASC
  ");

  $pIndex = array_search($period, $periods);
  $previousPeriod = $pIndex === FALSE || ($pIndex + 1 > count($periods)) ? "" : $periods[$pIndex + 1];
?>
