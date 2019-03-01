<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $soIds = $_POST["so_id"];

  if (assigned($action) && assigned($soIds) && count($soIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $soIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $soIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $soIds));

    if ($action == "delete") {
      array_push($queries, "DELETE a FROM `so_model` AS a LEFT JOIN `so_header` AS b ON a.so_no=b.so_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `so_header` WHERE $headerWhereClause");
    } else if ($action == "confirm") {
      array_push($queries, "UPDATE `so_header` SET status=\"CONFIRMED\" WHERE $headerWhereClause");
    } else if ($action == "print") {
      header("Location: " . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.so_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.so_date <= \"$to\"";
  }

  $soHeaders = query("
    SELECT
      a.id                                                                                AS `id`,
      DATE_FORMAT(a.so_date, '%d-%m-%Y')                                                  AS `date`,
      a.so_no                                                                             AS `so_no`,
      IFNULL(c.english_name, 'Unknown')                                                   AS `debtor_name`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      IFNULL(b.total_qty_outstanding, 0)                                                  AS `outstanding_qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100                       AS `outstanding_amt`,
      IFNULL(b.total_outstanding_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `outstanding_amt_base`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                         AS `so_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`,
        SUM(qty_outstanding * price)  AS `total_outstanding_amt`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.so_date DESC
  ");
?>
