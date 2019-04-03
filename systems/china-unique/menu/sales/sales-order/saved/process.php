<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $filterDebtorCodes = $_GET["filter_debtor_code"];
  $action = $_POST["action"];
  $soIds = $_POST["so_id"];

  if (assigned($action) && assigned($soIds) && count($soIds) > 0) {

    if ($action === "confirm" || $action === "delete") {
      $queries = array();

      if ($action == "delete") {
        array_push($queries, "
          DELETE a FROM
            `so_model` AS a
          LEFT JOIN
            `so_header` AS b
          ON a.so_no=b.so_no
          WHERE
            " . join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $soIds)) . "
        ");
        array_push($queries, "
          DELETE FROM
            `so_header`
          WHERE
            " . join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $soIds)) . "
        ");
      } else if ($action == "confirm") {
        array_push($queries, "
          UPDATE
            `so_header`
          SET
            status=\"CONFIRMED\"
          WHERE
            " . join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $soIds)) . "
        ");
      }

      execute($queries);

      if ($action === "confirm") {        
        header("Location: " . SALES_ORDER_CONFIRMED_URL);
        exit(0);
      }
    } else if ($action === "print") {
      header("Location:
      " . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?
      " . join("&", array_map(function ($i) { return "id[]=$i"; }, $soIds)));
      exit(0);
    }
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

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "c.code=\"$d\""; }, $filterDebtorCodes)) . ")";
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
      a.so_date DESC,
      a.so_no DESC
  ");

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                       AS `code`,
      IFNULL(c.english_name, 'Unknown')   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
    ORDER BY
      a.debtor_code ASC
  ");
?>
