<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $poIds = $_POST["po_id"];

  if (assigned($action) && assigned($poIds) && count($poIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $poIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $poIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $poIds));

    if ($action == "delete") {
      array_push($queries, "DELETE a FROM `po_model` AS a LEFT JOIN `po_header` AS b ON a.po_no=b.po_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `po_header` WHERE $headerWhereClause");
    } else if ($action == "print") {
      header("Location: " . PURCHASE_ORDER_INTERNAL_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.po_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.po_date <= \"$to\"";
  }

  $poHeaders = query("
    SELECT
      a.id                                                                                AS `id`,
      DATE_FORMAT(a.po_date, '%d-%m-%Y')                                                  AS `date`,
      a.po_no                                                                             AS `po_no`,
      IFNULL(c.english_name, 'Unknown')                                                   AS `creditor_name`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      IFNULL(b.total_qty_outstanding, 0)                                                  AS `outstanding_qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100                       AS `outstanding_amt`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `outstanding_amt_base`
    FROM
      `po_header` AS a
    LEFT JOIN
      (SELECT
        po_no                         AS `po_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`,
        SUM(qty_outstanding * price)  AS `total_outstanding_amt`
      FROM
        `po_model`
      GROUP BY
        po_no) AS b
    ON a.po_no=b.po_no
    LEFT JOIN
      `creditor` AS c
    ON a.creditor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.po_date DESC
  ");
?>
