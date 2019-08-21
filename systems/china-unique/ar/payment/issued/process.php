<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $paymentIds = $_POST["payment_id"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "live_only";

  if (assigned($action) && assigned($paymentIds) && count($paymentIds) > 0) {
    $queries = array();

    foreach ($paymentIds as $paymentId) {
      if ($action !== "print") {
        $payment = query("SELECT payment_no FROM `ar_payment` WHERE id=\"$paymentId\"")[0];
        $paymentNo = assigned($payment) ? $payment["payment_no"] : "";
        array_push($queries, recordPaymentAction($action . "_payment", $paymentNo));
      }
    }

    execute($queries);

    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $paymentIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $paymentIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $paymentIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_payment` AS b ON a.payment_no=b.payment_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_payment` WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_PAYMENT_PRINTOUT_URL . "?$printoutParams");
      exit();
    }

    execute($queries);
  }

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "a.debtor_code=\"$d\""; }, $filterDebtorCodes)) . ")";
  }

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.payment_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.payment_date <= \"$to\"";
  }

  if ($showMode === "deposit_only") {
    $whereClause = $whereClause . "
      AND ROUND(a.amount - IFNULL(b.settled_amount, 0), 2) > 0
    ";
  }

  $paymentHeaders = query("
    SELECT
      a.id                                              AS `id`,
      DATE_FORMAT(a.payment_date, \"%d-%m-%Y\")         AS `date`,
      a.payment_no                                      AS `payment_no`,
      a.debtor_code                                     AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")               AS `debtor_name`,
      a.currency_code                                   AS `currency_code`,
      ROUND(a.amount, 2)                                AS `amount`,
      ROUND(a.amount - IFNULL(b.settled_amount, 0), 2)  AS `remaining`
    FROM
      `ar_payment` AS a
    LEFT JOIN
      (SELECT
        payment_no    AS `payment_no`,
        SUM(amount)   AS `settled_amount`
      FROM
        `ar_settlement`
      GROUP BY
        payment_no) AS b
    ON a.payment_no=b.payment_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.payment_date DESC,
      a.payment_no ASC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_payment` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    ORDER BY
      code ASC
  ");
?>
