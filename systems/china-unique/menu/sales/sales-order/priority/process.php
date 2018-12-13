<?php
  $soIds = $_POST["so_id"];
  $priorities = $_POST["priority"];

  /* If a complete form is given, submit and update the priorities on the sales orders. */
  if (assigned($soIds) && assigned($priorities) && count($soIds) > 0 && count($priorities) > 0) {
    $whereClause = "id IN (" . join(", ", $soIds) . ")";
    $values = join(" ", array_map(function ($id, $priority) {
      return "WHEN id=\"$id\" THEN \"$priority\"";
    }, $soIds, $priorities));

    query("UPDATE `so_header` SET priority=(CASE $values END) WHERE $whereClause");
  }

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($code) { return "c.code=\"$code\""; }, $filterDebtorCodes)) . ")";
  }

  $soHeaders = query("
    SELECT
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                                  AS `date`,
      a.debtor_code                                                                       AS `debtor_code`,
      c.english_name                                                                      AS `debtor_name`,
      a.id                                                                                AS `so_id`,
      a.so_no                                                                             AS `so_no`,
      a.priority                                                                          AS `priority`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      IFNULL(b.total_qty_outstanding, 0)                                                  AS `qty_outstanding`,
      a.discount                                                                          AS `discount`,
      a.priority                                                                          AS `priority`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_amt_outstanding, 0) * (100 - a.discount) / 100                       AS `amt_outstanding`,
      IFNULL(b.total_amt_outstanding, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `amt_outstanding_base`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                         AS `so_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`,
        SUM(qty_outstanding * price)  AS `total_amt_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.priority DESC,
      a.debtor_code ASC,
      a.so_date DESC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(b.english_name, 'Unknown')   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    WHERE
      a.status=\"POSTED\"
    ORDER BY
      a.debtor_code ASC
  ");
?>
