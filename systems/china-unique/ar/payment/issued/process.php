<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $paymentIds = $_POST["payment_id"];

  if (assigned($action) && assigned($paymentIds) && count($paymentIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $paymentIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $paymentIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $paymentIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_payment` AS b ON a.payment_no=b.payment_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_payment` WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_PAYMENT_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.payment_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.payment_date <= \"$to\"";
  }

  $paymentHeaders = query("
    SELECT
      a.id                                        AS `id`,
      DATE_FORMAT(a.payment_date, \"%d-%m-%Y\")   AS `date`,
      a.payment_no                                AS `payment_no`,
      IFNULL(c.english_name, \"Unknown\")         AS `debtor_name`,
      a.currency_code                             AS `currency_code`,
      a.amount                                    AS `amount`
    FROM
      `ar_payment` AS a
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.payment_date DESC
  ");
?>
