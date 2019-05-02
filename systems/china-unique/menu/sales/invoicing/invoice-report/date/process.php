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
      IFNULL(bM.qty, 0)                                                             AS `qtyM`,
      IFNULL(bM.amount, 0) * (100 - a.discount) / 100                               AS `amountM`,
      IFNULL(bM.amount, 0) * (100 - a.discount) / (100 + a.tax)                     AS `netM`,
      IFNULL(bM.cost, 0)                                                            AS `costM`,
      IFNULL(bS.qty, 0)                                                             AS `qtyS`,
      IFNULL(bS.amount, 0) * (100 - a.discount) / 100                               AS `amountS`,
      IFNULL(bS.amount, 0) * (100 - a.discount) / (100 + a.tax)                     AS `netS`,
      IFNULL(bS.cost, 0)                                                            AS `costS`,
      IFNULL(bO.qty, 0)                                                             AS `qtyO`,
      IFNULL(bO.amount, 0) * (100 - a.discount) / 100                               AS `amountO`,
      IFNULL(bO.amount, 0) * (100 - a.discount) / (100 + a.tax)                     AS `netO`,
      IFNULL(bO.cost, 0)                                                            AS `costO`,
      IFNULL(d.invoice_amounts, \"\")                                               AS `invoice_amounts`,
      IFNULL(d.invoice_dates, \"\")                                                 AS `invoice_dates`,
      IFNULL(d.invoice_nos, \"\")                                                   AS `invoice_nos`,
      IFNULL(d.invoice_ids, \"\")                                                   AS `invoice_ids`
    ";
  }

  function joinModelTable($table, $as, $link, $otherColumns, $type, $whereClause, $negateValues = false) {
    $prefix = $negateValues ? "-" : "";
    return "
      LEFT JOIN
        (SELECT
          x.$link                               AS `$link`,
          $otherColumns
          COUNT(*)                              AS `count`,
          $prefix SUM(x.qty)                    AS `qty`,
          $prefix SUM(x.qty * x.price)          AS `amount`,
          $prefix SUM(x.qty * y.cost_average)   AS `cost`
        FROM
          `$table` AS x
        LEFT JOIN
          `model` AS y
        ON x.brand_code=y.brand_code AND x.model_no=y.model_no
        WHERE
          y.product_type" . (assigned($type) ? "=\"$type\"" : " IS NOT NULL") . "
          $whereClause
        GROUP BY
          $link) AS $as
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
      " . getColumns("IFNULL(bSO.so_no, \"\")", "a.do_date", "a.id", "a.do_no", "\"\"", "\"\"", "\"\"", "\"\"", "a.debtor_code") . "
    FROM
      `sdo_header` AS a
    " . joinModelTable("sdo_model", "bM", "do_no", "", "M", $modelWhereClause) . "
    " . joinModelTable("sdo_model", "bS", "do_no", "", "S", $modelWhereClause) . "
    " . joinModelTable("sdo_model", "bO", "do_no", "", "O", $modelWhereClause) . "
    " . joinModelTable("sdo_model", "bSO", "do_no", "GROUP_CONCAT(DISTINCT x.so_no) AS `so_no`,", "", $modelWhereClause) . "
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "do_no") . "
    WHERE
      a.status=\"POSTED\" AND
      IFNULL(bM.qty, 0) + IFNULL(bS.qty, 0) + IFNULL(bO.qty, 0) != 0 AND
      IFNULL(bM.amount, 0) + IFNULL(bS.amount, 0) + IFNULL(bO.amount, 0) != 0
      $doWhereClause
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_out_date", "\"\"", "\"\"", "a.id", "a.stock_out_no", "\"\"", "\"\"",  "a.debtor_code") . "
    FROM
      `stock_out_header` AS a
    " . joinModelTable("stock_out_model", "bM", "stock_out_no", "", "M", $modelWhereClause) . "
    " . joinModelTable("stock_out_model", "bS", "stock_out_no", "", "S", $modelWhereClause) . "
    " . joinModelTable("stock_out_model", "bO", "stock_out_no", "", "O", $modelWhereClause) . "
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "stock_out_no") . "
    WHERE
      a.status=\"POSTED\" AND
      (a.transaction_code=\"S1\" OR a.transaction_code=\"S2\") AND
      IFNULL(bM.qty, 0) + IFNULL(bS.qty, 0) + IFNULL(bO.qty, 0) != 0 AND
      IFNULL(bM.amount, 0) + IFNULL(bS.amount, 0) + IFNULL(bO.amount, 0) != 0
      $stockOutWhereClause
    UNION
    SELECT
      " . getColumns("\"R3\"", "a.stock_in_date", "\"\"", "\"\"", "\"\"", "\"\"", "a.id", "a.stock_in_no", "a.creditor_code") . "
    FROM
      `stock_in_header` AS a
    " . joinModelTable("stock_in_model", "bM", "stock_in_no", "", "M", $modelWhereClause, true) . "
    " . joinModelTable("stock_in_model", "bS", "stock_in_no", "", "S", $modelWhereClause, true) . "
    " . joinModelTable("stock_in_model", "bO", "stock_in_no", "", "O", $modelWhereClause, true) . "
    LEFT JOIN
      `creditor` AS c
    ON a.creditor_code=c.code
    LEFT JOIN
      (SELECT \"\" AS `invoice_amounts`, \"\" AS `invoice_dates`, \"\" AS `invoice_nos`, \"\" AS `invoice_ids`) AS d
    ON a.id=a.id
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"R3\" AND
      IFNULL(bM.qty, 0) + IFNULL(bS.qty, 0) + IFNULL(bO.qty, 0) != 0 AND
      IFNULL(bM.amount, 0) + IFNULL(bS.amount, 0) + IFNULL(bO.amount, 0) != 0
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
