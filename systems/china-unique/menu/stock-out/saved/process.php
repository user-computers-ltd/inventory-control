<?php
  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $stockOutNos = $_POST["stock_out_no"];

  if (assigned($action) && assigned($stockOutNos) && count($stockOutNos) > 0) {
    $queries = array();

    $whereClause = join(" OR ", array_map(function ($i) { return "stock_out_no=\"$i\""; }, $stockOutNos));

    if ($action == "delete") {
      array_push($queries, "DELETE FROM `stock_out_model` WHERE $whereClause");
      array_push($queries, "DELETE FROM `stock_out_header` WHERE $whereClause");
    } else if ($action == "post") {
      array_push($queries, "UPDATE `stock_out_header` SET status=\"POSTED\" WHERE $whereClause");

      foreach ($stockOutNos as $stockOutNo) {
        $queries = concat($queries, onPostStockOutVoucher($stockOutNo));
      }
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.stock_out_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.stock_out_date <= \"$to\"";
  }

  $stockOutHeaders = query("
    SELECT
      a.id                                                                    AS `id`,
      DATE_FORMAT(a.stock_out_date, '%d-%m-%Y')                               AS `date`,
      b.count                                                                 AS `count`,
      a.stock_out_no                                                          AS `stock_out_no`,
      IFNULL(c.english_name, 'Unknown')                                       AS `debtor_name`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`,
      a.transaction_code                                                      AS `transaction_code`
    FROM
      `stock_out_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                      AS `count`,
        stock_out_no                  AS `stock_out_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty * price)              AS `total_amt`
      FROM
        `stock_out_model`
      GROUP BY
        stock_out_no) AS b
    ON a.stock_out_no=b.stock_out_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.stock_out_date DESC
  ");
?>
