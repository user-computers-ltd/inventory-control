<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $stockInIds = $_POST["stock_in_id"];

  if (assigned($action) && assigned($stockInIds) && count($stockInIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $stockInIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $stockInIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $stockInIds));
    $postStockInNos = array_map(function ($i) { return $i["stock_in_no"]; }, query("SELECT stock_in_no FROM `stock_in_header` WHERE $headerWhereClause"));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `stock_in_model` AS a LEFT JOIN `stock_in_header` AS b ON a.stock_in_no=b.stock_in_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `stock_in_header` WHERE $headerWhereClause");
    } else if ($action === "post") {
      array_push($queries, "UPDATE `stock_in_header` SET status=\"POSTED\" WHERE $headerWhereClause");

      foreach ($postStockInNos as $stockInNo) {
        $queries = concat($queries, onPostStockInVoucher($stockInNo));
      }
    } else if ($action === "print") {
      header("Location: " . STOCK_IN_PRINTOUT_URL . "?$printoutParams");
      exit(0);
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
      a.id                                                                                AS `id`,
      DATE_FORMAT(a.stock_in_date, '%d-%m-%Y')                                            AS `date`,
      b.count                                                                             AS `count`,
      a.stock_in_no                                                                       AS `stock_in_no`,
      IFNULL(IF(a.transaction_code=\"R3\", d.english_name, c.creditor_name_eng), \"Unknown\")  AS `creditor_name`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      a.discount                                                                          AS `discount`,
      a.currency_code                                                                     AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                                   AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate                 AS `total_amt_base`,
      a.transaction_code                                                                  AS `transaction_code`
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
      `cu_ap`.`creditor` AS c
    ON a.creditor_code=c.creditor_code
    LEFT JOIN
      `debtor` AS d
    ON a.creditor_code=d.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.stock_in_date DESC
  ");
?>
