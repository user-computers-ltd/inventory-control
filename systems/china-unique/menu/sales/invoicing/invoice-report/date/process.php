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
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $filterProductTypes = $_GET["filter_product_type"];

  $doWhereClause = "";
  $stockOutWhereClause = "";
  $stockInReturnWhereClause = "";
  $modelWhereClause = "";

  if (assigned($period)) {
    $doWhereClause = $doWhereClause . "
      AND DATE_FORMAT(a.do_date, \"%Y-%m\")=\"$period\"";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND DATE_FORMAT(a.stock_out_date, \"%Y-%m\")=\"$period\"";
    $stockInReturnWhereClause = $stockInReturnWhereClause . "
      AND DATE_FORMAT(a.stock_in_date, \"%Y-%m\")=\"$period\"";
  }

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $doWhereClause = $doWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
    $stockInReturnWhereClause = $stockInReturnWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.creditor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  if (assigned($filterProductTypes) && count($filterProductTypes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "y.product_type=\"$d\""; }, $filterProductTypes)) . ")";
  }

  $incomeHeaders = array();

  function getColumns($soNo, $dateC, $doIdC, $doNoC, $stockOutIdC, $stockOutNoC, $stockInIdC, $stockInNoC, $clientCode) {
    return "
      $soNo                                                                         AS `so_no`,
      $dateC                                                                        AS `date_`,
      DATE_FORMAT($dateC, \"%d-%m-%Y\")                                             AS `date`,
      DATE_FORMAT($dateC, \"%Y-%m\")                                                AS `period`,
      $doIdC                                                                        AS `do_id`,
      $doNoC                                                                        AS `do_no`,
      $stockOutIdC                                                                  AS `stock_out_id`,
      $stockOutNoC                                                                  AS `stock_out_no`,
      $stockInIdC                                                                   AS `stock_in_id`,
      $stockInNoC                                                                   AS `stock_in_no`,
      $clientCode                                                                   AS `debtor_code`,
      a.currency_code                                                               AS `currency`,
      IFNULL(c.english_name, \"Unknown\")                                           AS `debtor_name`,
      IFNULL(b.qtyM, 0)                                                             AS `qtyM`,
      IFNULL(b.amountM, 0) * (100 - a.discount) / 100                               AS `amountM`,
      IFNULL(b.amountM, 0) * (100 - a.discount) / (100 + a.tax)                     AS `netM`,
      IFNULL(b.costM, 0)                                                            AS `costM`,
      IFNULL(b.qtyS, 0)                                                             AS `qtyS`,
      IFNULL(b.amountS, 0) * (100 - a.discount) / 100                               AS `amountS`,
      IFNULL(b.amountS, 0) * (100 - a.discount) / (100 + a.tax)                     AS `netS`,
      IFNULL(b.costS, 0)                                                            AS `costS`,
      IFNULL(b.qtyO, 0)                                                             AS `qtyO`,
      IFNULL(b.amountO, 0) * (100 - a.discount) / 100                               AS `amountO`,
      IFNULL(b.amountO, 0) * (100 - a.discount) / (100 + a.tax)                     AS `netO`,
      IFNULL(b.costO, 0)                                                            AS `costO`,
      IFNULL(d.invoice_amounts, \"\")                                               AS `invoice_amounts`,
      IFNULL(d.invoice_dates, \"\")                                                 AS `invoice_dates`,
      IFNULL(d.invoice_nos, \"\")                                                   AS `invoice_nos`,
      IFNULL(d.invoice_ids, \"\")                                                   AS `invoice_ids`
    ";
  }

  function joinModelTable($as, $link, $whereClause, $negateValues = false) {
    $prefix = $negateValues ? "-" : "";
    return "
      LEFT JOIN
        (SELECT
          x.header_no                                                                         AS `$link`,
          $prefix SUM(CASE WHEN y.product_type=\"M\" THEN x.qty ELSE 0 END)                   AS `qtyM`,
          $prefix SUM(CASE WHEN y.product_type=\"M\" THEN x.qty * x.price ELSE 0 END)         AS `amountM`,
          $prefix SUM(CASE WHEN y.product_type=\"M\" THEN x.qty * x.cost_average ELSE 0 END)  AS `costM`,
          $prefix SUM(CASE WHEN y.product_type=\"S\" THEN x.qty ELSE 0 END)                   AS `qtyS`,
          $prefix SUM(CASE WHEN y.product_type=\"S\" THEN x.qty * x.price ELSE 0 END)         AS `amountS`,
          $prefix SUM(CASE WHEN y.product_type=\"S\" THEN x.qty * x.cost_average ELSE 0 END)  AS `costS`,
          $prefix SUM(CASE WHEN y.product_type=\"O\" THEN x.qty ELSE 0 END)                   AS `qtyO`,
          $prefix SUM(CASE WHEN y.product_type=\"O\" THEN x.qty * x.price ELSE 0 END)         AS `amountO`,
          $prefix SUM(CASE WHEN y.product_type=\"O\" THEN x.qty * x.cost_average ELSE 0 END)  AS `costO`
        FROM
          `transaction` AS x
        LEFT JOIN
          `model` AS y
        ON x.brand_code=y.brand_code AND x.model_no=y.model_no
        WHERE
          y.product_type IS NOT NULL
          $whereClause
        GROUP BY
          x.header_no) AS $as
      ON a.$link=$as.$link
    ";
  }

  function joinInvoiceTable($as, $columnName) {
    return "
      LEFT JOIN
        (SELECT
          x.$columnName                                           AS `$columnName`,
          GROUP_CONCAT(DATE_FORMAT(y.invoice_date, \"%d-%m-%Y\")) AS `invoice_dates`,
          GROUP_CONCAT(y.id)                                      AS `invoice_ids`,
          GROUP_CONCAT(x.invoice_no)                              AS `invoice_nos`,
          GROUP_CONCAT(x.amount)                                  AS `invoice_amounts`
        FROM
          `out_inv_model` AS x
        LEFT JOIN
          `out_inv_header` AS y
        ON x.invoice_no=y.invoice_no
        GROUP BY
          x.$columnName) AS $as
      ON a.$columnName=$as.$columnName
    ";
  }

  $results = query("
    SELECT
      " . getColumns("IFNULL(b2.so_nos, \"\")", "a.do_date", "a.id", "a.do_no", "\"\"", "\"\"", "\"\"", "\"\"", "a.debtor_code") . "
    FROM
      `sdo_header` AS a
    " . joinModelTable("b", "do_no", $modelWhereClause) . "
    LEFT JOIN
      (SELECT
        do_no                        AS `do_no`,
        GROUP_CONCAT(DISTINCT so_no) AS `so_nos`
      FROM
        `sdo_model`
      GROUP BY
        do_no) AS b2
    ON a.do_no=b2.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "do_no") . "
    WHERE
      a.status=\"POSTED\" AND
      IFNULL(b.qtyM, 0) + IFNULL(b.qtyS, 0) + IFNULL(b.qtyO, 0) != 0 AND
      IFNULL(b.amountM, 0) + IFNULL(b.amountS, 0) + IFNULL(b.amountO, 0) != 0
      $doWhereClause
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_out_date", "\"\"", "\"\"", "a.id", "a.stock_out_no", "\"\"", "\"\"",  "a.debtor_code") . "
    FROM
      `stock_out_header` AS a
    " . joinModelTable("b", "stock_out_no", $modelWhereClause) . "
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "stock_out_no") . "
    WHERE
      a.status=\"POSTED\" AND
      (a.transaction_code=\"S1\" OR a.transaction_code=\"S2\") AND
      IFNULL(b.qtyM, 0) + IFNULL(b.qtyS, 0) + IFNULL(b.qtyO, 0) != 0 AND
      IFNULL(b.amountM, 0) + IFNULL(b.amountS, 0) + IFNULL(b.amountO, 0) != 0
      $stockOutWhereClause
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_in_date", "\"\"", "\"\"", "\"\"", "\"\"", "a.id", "a.stock_in_no", "a.creditor_code") . "
    FROM
      `stock_in_header` AS a
    " . joinModelTable("b", "stock_in_no", $modelWhereClause, true) . "
    LEFT JOIN
      `debtor` AS c
    ON a.creditor_code=c.code
    LEFT JOIN
      (SELECT \"\" AS `invoice_amounts`, \"\" AS `invoice_dates`, \"\" AS `invoice_nos`, \"\" AS `invoice_ids`) AS d
    ON a.id=a.id
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"R3\" AND
      IFNULL(b.qtyM, 0) + IFNULL(b.qtyS, 0) + IFNULL(b.qtyO, 0) != 0 AND
      IFNULL(b.amountM, 0) + IFNULL(b.amountS, 0) + IFNULL(b.amountO, 0) != 0
      $stockInReturnWhereClause
    ORDER BY
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
?>
