<?php

  $incomeHeaders = array();

  function getColumns($soNo, $dateC, $doIdC, $doNoC, $stockOutIdC, $stockOutNoC, $stockInIdC, $stockInNoC, $clientCode) {
    return "
      $soNo                                                                     AS `so_no`,
      $dateC                                                                    AS `date_`,
      DATE_FORMAT($dateC, \"%d-%m-%Y\")                                         AS `date`,
      $doIdC                                                                    AS `do_id`,
      $doNoC                                                                    AS `do_no`,
      $stockOutIdC                                                              AS `stock_out_id`,
      $stockOutNoC                                                              AS `stock_out_no`,
      $stockInIdC                                                               AS `stock_in_id`,
      $stockInNoC                                                               AS `stock_in_no`,
      $clientCode                                                               AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")                                       AS `debtor_name`,
      IFNULL(b.qty, 0)                                                          AS `qty`,
      a.currency_code                                                           AS `currency`,
      IFNULL(b.amount, 0) * (100 - a.discount) / 100 - IFNULL(e.invoice_sum, 0) AS `pending`,
      (100 + a.tax) / 100                                                       AS `tax`,
      CASE
        WHEN d.invoice_settlement>0 THEN \"FULL\"
        WHEN d.invoice_settlement=0 THEN \"PARTIAL\"
        ELSE \"PENDING\"
      END                                                                       AS `settlement`
    ";
  }

  function joinModelTable($as, $link, $whereClause, $negateValues = false) {
    $prefix = $negateValues ? "-" : "";
    return "
      LEFT JOIN
        (SELECT
          x.header_no                           AS `$link`,
          $prefix SUM(x.qty)                    AS `qty`,
          $prefix SUM(x.qty * x.price)          AS `amount`,
          $prefix SUM(x.qty * x.cost_average)   AS `cost`
        FROM
          `transaction` AS x
        LEFT JOIN
          `model` AS y
        ON x.brand_code=y.brand_code AND x.model_no=y.model_no
        WHERE
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
          GROUP_CONCAT(x.amount)                                  AS `invoice_amounts`,
          SUM(IF(x.settlement=\"FULL\",1, 0))                     AS `invoice_settlement`
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

  function joinInvoiceSumTable($as, $columnName) {
    return "
      LEFT JOIN
        (SELECT
          x.$columnName                       AS `$columnName`,
          SUM(x.amount)                       AS `invoice_sum`,
          SUM(IF(x.settlement=\"FULL\",1, 0)) AS `invoice_settlement`
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
    " . joinModelTable("b", "do_no", "x.transaction_code=\"S2\"") . "
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
    " . joinInvoiceSumTable("e", "do_no") . "
    WHERE
      a.status=\"POSTED\" AND
      IFNULL(b.qty, 0) != 0 AND
      (e.invoice_settlement IS NULL OR e.invoice_settlement = 0)
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_out_date", "\"\"", "\"\"", "a.id", "a.stock_out_no", "\"\"", "\"\"", "a.debtor_code") . "
    FROM
      `stock_out_header` AS a
    " . joinModelTable("b", "stock_out_no", "x.transaction_code=\"S1\"") . "
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    " . joinInvoiceTable("d", "stock_out_no") . "
    " . joinInvoiceSumTable("e", "stock_out_no") . "
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"S1\" AND
      (e.invoice_settlement IS NULL OR e.invoice_settlement = 0)
    UNION
    SELECT
      " . getColumns("a.transaction_code", "a.stock_in_date", "\"\"", "\"\"", "\"\"", "\"\"", "a.id", "a.stock_in_no", "a.creditor_code") . "
    FROM
      `stock_in_header` AS a
    " . joinModelTable("b", "stock_in_no", "x.transaction_code=\"R3\"", true) . "
    LEFT JOIN
      `debtor` AS c
    ON a.creditor_code=c.code
    " . joinInvoiceTable("d", "stock_in_no") . "
    " . joinInvoiceSumTable("e", "stock_in_no") . "
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"R3\" AND
      (e.invoice_settlement IS NULL OR e.invoice_settlement = 0)
    ORDER BY
      debtor_code ASC,
      date_ ASC
  ");

  foreach ($results as $incomeHeader) {
    $currency = $incomeHeader["currency"];
    $debtorName = $incomeHeader["debtor_name"];

    $arrayPointer = &$incomeHeaders;

    if (!isset($arrayPointer[$currency])) {
      $arrayPointer[$currency] = array();
    }
    $arrayPointer = &$arrayPointer[$currency];

    if (!isset($arrayPointer[$debtorName])) {
      $arrayPointer[$debtorName] = array();
    }
    $arrayPointer = &$arrayPointer[$debtorName];

    array_push($arrayPointer, $incomeHeader);
  }
?>
