<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];

  $doWhereClause = "";
  $stockOutWhereClause = "";

  if (assigned($from)) {
    $doWhereClause = $doWhereClause . "
      AND a.do_date >= \"$from\"";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND a.stock_out_date >= \"$from\"";
  }

  if (assigned($to)) {
    $doWhereClause = $doWhereClause . "
      AND a.do_date <= \"$to\"";
    $stockOutWhereClause = $stockOutWhereClause . "
      AND a.stock_out_date <= \"$to\"";
  }

  $incomeHeaders = query("
    SELECT
      a.do_date                                                                     AS `date_`,
      DATE_FORMAT(a.do_date, '%d-%m-%Y')                                            AS `date`,
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
      IFNULL(b.amount, 0) * (100 - a.discount) / (100 + a.tax)                      AS `net`,
      IFNULL(b.cost, 0)                                                             AS `cost`,
      IFNULL(d.invoice_amounts, \"\")                                               AS `invoice_amounts`,
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
        x.do_no                     AS `do_no`,
        GROUP_CONCAT(y.id)          AS `invoice_ids`,
        GROUP_CONCAT(x.invoice_no)  AS `invoice_nos`,
        GROUP_CONCAT(x.amount)      AS `invoice_amounts`
      FROM
        `out_inv_model` AS x
      LEFT JOIN
        `out_inv_header` AS y
      ON x.invoice_no=y.invoice_no
      GROUP BY
        do_no) AS d
    ON a.do_no=d.do_no
    WHERE
      a.status=\"POSTED\"
      $doWhereClause
    UNION
    SELECT
      a.stock_out_date                                                              AS `date_`,
      DATE_FORMAT(a.stock_out_date, '%d-%m-%Y')                                     AS `date`,
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
      IFNULL(b.amount, 0) * (100 - a.discount) / (100 + a.tax)                      AS `net`,
      IFNULL(b.cost, 0)                                                             AS `cost`,
      IFNULL(d.invoice_amounts, \"\")                                               AS `invoice_amounts`,
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
        x.stock_out_no              AS `stock_out_no`,
        GROUP_CONCAT(y.id)          AS `invoice_ids`,
        GROUP_CONCAT(x.invoice_no)  AS `invoice_nos`,
        GROUP_CONCAT(x.amount)      AS `invoice_amounts`
      FROM
        `out_inv_model` AS x
      LEFT JOIN
        `out_inv_header` AS y
      ON x.invoice_no=y.invoice_no
      GROUP BY
        stock_out_no) AS d
    ON a.stock_out_no=d.stock_out_no
    WHERE
      a.status=\"POSTED\" AND (a.transaction_code=\"S1\" OR a.transaction_code=\"S2\")
      $stockOutWhereClause
    ORDER BY
      debtor_code ASC,
      date_ ASC
  ");
?>
