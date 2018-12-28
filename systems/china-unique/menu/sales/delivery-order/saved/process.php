<?php
  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $doNos = $_POST["do_no"];

  if (assigned($action) && assigned($doNos) && count($doNos) > 0) {
    $queries = array();

    $whereClause = join(" OR ", array_map(function ($i) { return "do_no=\"$i\""; }, $doNos));

    if ($action == "delete") {
      array_push($queries, "DELETE FROM `sdo_model` WHERE $whereClause");
      array_push($queries, "DELETE FROM `sdo_header` WHERE $whereClause");
    } else if ($action == "post") {
      array_push($queries, "UPDATE `sdo_header` SET status=\"POSTED\" WHERE $whereClause");

      foreach ($doNos as $doNo) {
        $queries = concat($queries, onPostSalesDeliveryOrder($doNo));
      }
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.do_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.do_date <= \"$to\"";
  }

  $doHeaders = query("
    SELECT
      a.id                                                                    AS `do_id`,
      DATE_FORMAT(a.do_date, '%d-%m-%Y')                                      AS `date`,
      a.do_no                                                                 AS `do_no`,
      CONCAT(a.debtor_code, ' - ', IFNULL(c.english_name, 'Unknown'))         AS `debtor`,
      IFNULL(b.total_qty, 0)                                                  AS `qty`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      (SELECT
        do_no, SUM(qty) as total_qty, SUM(qty * price) as total_amt
      FROM
        `sdo_model`
      GROUP BY
        do_no) AS b
    ON a.do_no=b.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.do_date DESC
  ");
?>
