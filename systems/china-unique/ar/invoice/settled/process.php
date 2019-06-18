<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $invoiceIds = $_POST["invoice_id"];

  if (assigned($action) && assigned($invoiceIds) && count($invoiceIds) > 0) {
    $queries = array();

    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $invoiceIds));

    if ($action === "print") {
      header("Location: " . OUT_INVOICE_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.invoice_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.invoice_date <= \"$to\"";
  }

  $invoiceHeaders = query("
    SELECT
      a.id                                        AS `id`,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\")   AS `date`,
      b.count                                     AS `count`,
      a.invoice_no                                AS `invoice_no`,
      IFNULL(c.english_name, \"Unknown\")         AS `debtor_name`,
      a.currency_code                             AS `currency_code`,
      DATE_FORMAT(a.maturity_date, \"%d-%m-%Y\")  AS `maturity_date`,
      IFNULL(b.amount, 0)                         AS `amount`,
      IFNULL(b.amount, 0) * a.exchange_rate       AS `amount_base`
    FROM
      `out_inv_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                                  AS `count`,
        invoice_no                                AS `invoice_no`,
        SUM(amount)                               AS `amount`
      FROM
        `out_inv_model`
      GROUP BY
        invoice_no) AS b
    ON a.invoice_no=b.invoice_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SETTLED\"
      $whereClause
    ORDER BY
      a.invoice_date DESC
  ");
?>
