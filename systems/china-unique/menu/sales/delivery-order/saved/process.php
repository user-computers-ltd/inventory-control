<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $doIds = $_POST["do_id"];

  if (assigned($action) && assigned($doIds) && count($doIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $doIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $doIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $doIds));
    $postDoNos = array_map(function ($i) { return $i["do_no"]; }, query("SELECT do_no FROM `sdo_header` WHERE $headerWhereClause"));

    if ($action == "delete") {
      array_push($queries, "DELETE a FROM `sdo_model` AS a LEFT JOIN `sdo_header` AS b ON a.do_no=b.do_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `sdo_header` WHERE $headerWhereClause");
    } else if ($action == "post") {
      array_push($queries, "UPDATE `sdo_header` SET status=\"POSTED\" WHERE $headerWhereClause");

      foreach ($postDoNos as $doNo) {
        $queries = concat($queries, onPostSalesDeliveryOrder($doNo));
      }
    } else if ($action == "print") {
      header("Location: " . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?$printoutParams");
      exit(0);
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
