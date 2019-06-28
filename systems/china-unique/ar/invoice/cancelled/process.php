<?php
  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $invoiceIds = $_POST["invoice_id"];

  if (assigned($action) && assigned($invoiceIds) && count($invoiceIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $invoiceIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $invoiceIds));

    if ($action === "reissue") {
      array_push($queries, "UPDATE `ar_inv_header` SET status=\"SAVED\" WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_INVOICE_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
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
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"CANCELLED\"
      $whereClause
    ORDER BY
      a.invoice_date DESC
  ");
?>
