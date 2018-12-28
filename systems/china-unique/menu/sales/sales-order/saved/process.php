<?php
  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $soNos = $_POST["so_no"];

  if (assigned($action) && assigned($soNos) && count($soNos) > 0) {
    $queries = array();

    $whereClause = join(" OR ", array_map(function ($i) { return "so_no=\"$i\""; }, $soNos));

    if ($action == "delete") {
      array_push($queries, "DELETE FROM `so_model` WHERE $whereClause");
      array_push($queries, "DELETE FROM `so_header` WHERE $whereClause");
    } else if ($action == "post") {
      array_push($queries, "UPDATE `so_header` SET status=\"POSTED\" WHERE $whereClause");
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
