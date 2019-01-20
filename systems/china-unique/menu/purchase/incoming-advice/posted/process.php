<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $iaIds = $_POST["ia_id"];

  if (assigned($action) && assigned($iaIds) && count($iaIds) > 0) {
    $queries = array();

    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $iaIds));

    if ($action == "print") {
      header("Location: " . INCOMING_ADVICE_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.ia_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.ia_date <= \"$to\"";
  }

  $iaHeaders = query("
    SELECT
      a.id                                  AS `id`,
      DATE_FORMAT(a.ia_date, '%d-%m-%Y')    AS `date`,
      b.count                               AS `count`,
      a.ia_no                               AS `ia_no`,
      a.do_no                               AS `do_no`,
      IFNULL(c.english_name, 'Unknown')     AS `creditor_name`,
      IFNULL(b.total_qty, 0)                AS `total_qty`
    FROM
      `ia_header` AS a
    LEFT JOIN
      (SELECT
        COUNT(*)            AS `count`,
        ia_no               AS `ia_no`,
        SUM(qty)            AS `total_qty`
      FROM
        `ia_model`
      GROUP BY
        ia_no) AS b
    ON a.ia_no=b.ia_no
    LEFT JOIN
      `creditor` AS c
    ON a.creditor_code=c.code
    WHERE
      a.status=\"POSTED\"
      $whereClause
    ORDER BY
      a.ia_date DESC
  ");
?>
