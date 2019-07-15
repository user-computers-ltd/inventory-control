<?php
  $ids = $_GET["id"];

  $statementHeaders = array();
  $statementInvoiceList = array();
  $statementInvoices = array();

  /* If an id is given, retrieve from an existing stock out voucher. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));
    $modelWhereClause = "
      AND (" . join(" OR ", array_map(function ($i) { return "e.id=\"$i\""; }, $ids)) . ")";

    $statementHeaders = query("
      SELECT
        code                                    AS `debtor_code`,
        english_name                            AS `debtor_name`,
        billing_address                         AS `address`
      FROM
        `debtor`
      WHERE
        $headerWhereClause
    ");

    $dateDiff = "DATEDIFF(NOW(), a.invoice_date)";

    $statementInvoiceList = query("
      SELECT
        a.debtor_code                                                                               AS `debtor_code`,
        DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")                                                   AS `date`,
        CASE
          WHEN $dateDiff > 90 THEN \"Over 90 days（90天以上）\"
          WHEN $dateDiff > 60 AND $dateDiff <= 90 THEN \"60-90 days（60-90天）\"
          WHEN $dateDiff > 30 AND $dateDiff <= 60 THEN \"30-60 days（30-60天）\"
          ELSE \"Current period（30天或以內）\"
        END                                                                                         AS `date_category`,
        DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")                                                   AS `date`,
        a.invoice_no                                                                                AS `invoice_no`,
        ROUND(IFNULL(b.amount, 0), 2)                                                               AS `amount`,
        ROUND(IFNULL(c.credit_amount, 0) - IFNULL(d.credited_amount, 0), 2)                         AS `dr_cr_amount`,
        ROUND(IFNULL(c.payment_amount, 0), 2)                                                       AS `paid_amount`,
        ROUND(IFNULL(b.amount, 0) - IFNULL(c.settled_amount, 0) + IFNULL(d.credited_amount, 0), 2)  AS `balance`
      FROM
        `ar_inv_header` AS a
      LEFT JOIN
        (SELECT
          COUNT(*)                                  AS `count`,
          invoice_no                                AS `invoice_no`,
          SUM(amount)                               AS `amount`
        FROM
          `ar_inv_item`
        GROUP BY
          invoice_no) AS b
      ON a.invoice_no=b.invoice_no
      LEFT JOIN
        (SELECT
          invoice_no                                  AS `invoice_no`,
          SUM(IF(credit_note_no!=\"\", amount, 0))    AS `credit_amount`,
          SUM(IF(payment_no!=\"\", amount, 0))        AS `payment_amount`,
          SUM(amount)                                 AS `settled_amount`
        FROM
          `ar_settlement`
        GROUP BY
          invoice_no) AS c
      ON a.invoice_no=c.invoice_no
      LEFT JOIN
        (SELECT
          invoice_no    AS `invoice_no`,
          SUM(amount)   AS `credited_amount`
        FROM
          `ar_credit_note`
        GROUP BY
          invoice_no) AS d
      ON a.invoice_no=d.invoice_no
      LEFT JOIN
        `debtor` AS e
      ON a.debtor_code=e.code
      WHERE
        a.status=\"SAVED\" AND
        ROUND(IFNULL(b.amount, 0) - IFNULL(c.settled_amount, 0) + IFNULL(d.credited_amount, 0), 2) != 0
        $modelWhereClause
      ORDER BY
        a.invoice_date ASC
      ");
  }

  if (count($statementInvoiceList) > 0) {
    foreach ($statementInvoiceList as $statementInvoice) {
      $dCode = $statementInvoice["debtor_code"];
      $dCategory = $statementInvoice["date_category"];

      $arrayPointer = &$statementInvoices;

      if (!isset($arrayPointer[$dCode])) {
        $arrayPointer[$dCode] = array();
      }
      $arrayPointer = &$arrayPointer[$dCode];

      if (!isset($arrayPointer[$dCategory])) {
        $arrayPointer[$dCategory] = array();
      }
      $arrayPointer = &$arrayPointer[$dCategory];

      array_push($arrayPointer, $statementInvoice);
    }
  }

  consoleLog($statementInvoiceList);
?>
