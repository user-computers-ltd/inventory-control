<?php
  $action = $_POST["action"];
  $debtorIds = $_POST["debtor_id"];

  if (assigned($action) && assigned($debtorIds) && count($debtorIds) > 0) {
    if ($action === "print") {
      $debtorIdParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $debtorIds));
      header("Location: " . AR_REPORT_CLIENT_STATEMENT_PRINTOUT_URL . "?$debtorIdParams");
      exit(0);
    }
  }

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      e.id                                                                                              AS `id`,
      a.debtor_code                                                                                     AS `debtor_code`,
      IFNULL(e.english_name, \"Unknown\")                                                               AS `debtor_name`,
      ROUND(SUM(IFNULL(b.amount, 0)), 2)                                                                AS `amount`,
      ROUND(SUM(IFNULL(c.credit_amount, 0) - IFNULL(d.credited_amount, 0)), 2)                          AS `dr_cr_amount`,
      ROUND(SUM(IFNULL(c.payment_amount, 0)), 2)                                                        AS `paid_amount`,
      ROUND(SUM(IFNULL(b.amount, 0) - IFNULL(c.settled_amount, 0) + IFNULL(d.credited_amount, 0)), 2)   AS `balance`
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
      a.status=\"SAVED\"
      $whereClause
    GROUP BY
      a.debtor_code
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    ORDER BY
      code ASC
  ");
?>
