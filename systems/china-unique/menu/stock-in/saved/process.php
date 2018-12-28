<?php
  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $stockInNos = $_POST["stock_in_no"];

  if (assigned($action) && assigned($stockInNos) && count($stockInNos) > 0) {
    $queries = array();

    $whereClause = join(" OR ", array_map(function ($i) { return "stock_in_no=\"$i\""; }, $stockInNos));

    if ($action == "delete") {
      array_push($queries, "DELETE FROM `stock_in_model` WHERE $whereClause");
      array_push($queries, "DELETE FROM `stock_in_header` WHERE $whereClause");
    } else if ($action == "post") {
      array_push($queries, "UPDATE `stock_in_header` SET status=\"POSTED\" WHERE $whereClause");

      foreach ($stockInNos as $stockInNo) {
        $queries = concat($queries, onPostStockInVoucher($stockInNo));
      }
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.stock_in_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.stock_in_date <= \"$to\"";
  }

  $stockInHeaders = query("
    SELECT
      a.id                                                                    AS `id`,
      DATE_FORMAT(a.stock_in_date, '%d-%m-%Y')                                AS `date`,
      b.count                                                                 AS `count`,
      a.stock_in_no                                                           AS `stock_in_no`,
      IFNULL(c.english_name, 'Unknown')                                       AS `creditor_name`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`,
      a.transaction_code                                                      AS `transaction_code`
    FROM
      `stock_in_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)                      AS `count`,
        stock_in_no                   AS `stock_in_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty * price)              AS `total_amt`
      FROM
        `stock_in_model`
      GROUP BY
        stock_in_no) AS b
    ON a.stock_in_no=b.stock_in_no
    LEFT JOIN
      `creditor` AS c
    ON a.creditor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.stock_in_date DESC
  ");
?>
