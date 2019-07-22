<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $invoiceIds = $_POST["invoice_id"];

  if (assigned($action) && assigned($invoiceIds) && count($invoiceIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $invoiceIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $invoiceIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $invoiceIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_inv_item` AS a LEFT JOIN `ar_inv_header` AS b ON a.invoice_no=b.invoice_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_inv_header` WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . SALES_INVOICE_PRINTOUT_URL . "?$printoutParams");
      exit();
    }

    execute($queries);

    $queries = array();

    foreach ($invoiceIds as $invoiceId) {
      $invoice = query("SELECT invoice_no FROM `ar_inv_header` WHERE id=\"$invoiceId\"")[0];
      $invoiceNo = assigned($invoice) ? $invoice["invoice_no"] : "";
      array_push($queries, recordInvoiceAction($action . "_invoice", $invoiceNo));
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
      a.id                                      AS `id`,
      DATE_FORMAT(a.invoice_date, \"%d-%m-%Y\") AS `date`,
      b.count                                   AS `count`,
      a.invoice_no                              AS `invoice_no`,
      a.debtor_code                             AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")       AS `debtor_name`,
      a.currency_code                           AS `currency_code`,
      IFNULL(b.amount, 0)                       AS `amount`,
      IFNULL(b.amount, 0) * a.exchange_rate     AS `amount_base`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                      AS `count`,
        invoice_no                    AS `invoice_no`,
        SUM(amount)                   AS `amount`
      FROM
        `ar_inv_item`
      WHERE
        do_no!=\"\" OR stock_out_no!=\"\" OR stock_in_no!=\"\"
      GROUP BY
        invoice_no) AS b
    ON a.invoice_no=b.invoice_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\" AND b.count > 0
      $whereClause
    ORDER BY
      a.invoice_date DESC,
      a.invoice_no ASC
  ");
?>
