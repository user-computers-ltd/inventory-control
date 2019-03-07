<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $doIds = $_POST["do_id"];

  if (assigned($action) && assigned($doIds) && count($doIds) > 0) {
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $doIds));

    if ($action == "print") {
      header("Location: " . SALES_DELIVERY_ORDER_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }
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
      (CASE
        WHEN b.normal_count=b.count   THEN \"normal\"
        WHEN b.special_count=b.count  THEN \"special\"
        WHEN b.end_user_count=b.count THEN \"end user\"
        ELSE \"custom\"
      END)                                                                    AS `price_category`,
      a.discount                                                              AS `discount`,
      a.currency_code                                                         AS `currency`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100                       AS `total_amt`,
      IFNULL(b.total_amt, 0) * (100 - a.discount) / 100 * a.exchange_rate     AS `total_amt_base`
    FROM
      `sdo_header` AS a
    LEFT JOIN
      (SELECT
        s.do_no                                     AS `do_no`,
        SUM(s.qty)                                  AS `total_qty`,
        SUM(IF(s.price=m.retail_normal, 1, 0))      AS `normal_count`,
        SUM(IF(s.price=m.retail_special, 1, 0))     AS `special_count`,
        SUM(IF(s.price=m.wholesale_special, 1, 0))  AS `end_user_count`,
        COUNT(s.id)                                 AS `count`,
        SUM(s.qty * s.price)                        AS `total_amt`
      FROM
        `sdo_model` AS s
      LEFT JOIN
        `model` AS m
      ON s.brand_code=m.brand_code AND s.model_no=m.model_no
      GROUP BY
        do_no) AS b
    ON a.do_no=b.do_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.do_date DESC
  ");
?>
