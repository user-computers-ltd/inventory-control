<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $stockOutIds = $_POST["stock_out_id"];

  if (assigned($action) && assigned($stockOutIds) && count($stockOutIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $stockOutIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $stockOutIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $stockOutIds));
    $postStockOutNos = array_map(function ($i) { return $i["stock_out_no"]; }, query("SELECT stock_out_no FROM `stock_out_header` WHERE $headerWhereClause"));

    if ($action == "delete") {
      array_push($queries, "DELETE a FROM `stock_out_model` AS a LEFT JOIN `stock_out_header` AS b ON a.stock_out_no=b.stock_out_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `stock_out_header` WHERE $headerWhereClause");
    } else if ($action == "post") {
      array_push($queries, "UPDATE `stock_out_header` SET status=\"POSTED\" WHERE $headerWhereClause");

      foreach ($postStockOutNos as $stockOutNo) {
        $queries = concat($queries, onPostStockOutVoucher($stockOutNo));
      }
    } else if ($action == "print") {
      header("Location: " . STOCK_OUT_PRINTOUT_URL . "?$printoutParams");
      exit(0);
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
      a.id                                                                                AS `id`,
      DATE_FORMAT(a.stock_out_date, '%d-%m-%Y')                                           AS `date`,
      b.count                                                                             AS `count`,
      a.stock_out_no                                                                      AS `stock_out_no`,
      IFNULL(IF(a.transaction_code=\"S3\", d.creditor_name_eng, c.english_name), \"Unknown\")  AS `debtor_name`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                                   AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate                 AS `total_amt_base`,
      a.transaction_code                                                                  AS `transaction_code`
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
    LEFT JOIN
      `cu_ap`.`creditor` AS d
    ON a.debtor_code=d.creditor_code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.stock_out_date DESC
  ");
?>
