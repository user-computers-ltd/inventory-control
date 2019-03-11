<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $enquiryIds = $_POST["enquiry_id"];

  if (assigned($action) && assigned($enquiryIds) && count($enquiryIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $enquiryIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $enquiryIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $enquiryIds));

    if ($action == "delete") {
      array_push($queries, "DELETE a FROM `enquiry_model` AS a LEFT JOIN `enquiry_header` AS b ON a.enquiry_no=b.enquiry_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `enquiry_header` WHERE $headerWhereClause");
    } else if ($action == "print") {
      header("Location: " . SALES_ENQUIRY_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.enquiry_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.enquiry_date <= \"$to\"";
  }

  $enquiryHeaders = query("
    SELECT
      a.id                                                                                AS `id`,
      DATE_FORMAT(a.enquiry_date, '%d-%m-%Y')                                             AS `date`,
      a.enquiry_no                                                                        AS `enquiry_no`,
      IF(a.debtor_name=\"\", c.english_name, a.debtor_name)                               AS `debtor_name`,
      IFNULL(b.total_qty, 0)                                                              AS `qty`,
      IFNULL(b.total_qty_allotted, 0)                                                     AS `qty_allotted`
    FROM
      `enquiry_header` AS a
    LEFT JOIN
      (SELECT
        enquiry_no                    AS `enquiry_no`,
        SUM(qty)                      AS `total_qty`,
        SUM(qty_allotted)             AS `total_qty_allotted`
      FROM
        `enquiry_model`
      GROUP BY
        enquiry_no) AS b
    ON a.enquiry_no=b.enquiry_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.enquiry_no IS NOT NULL
      $whereClause
    ORDER BY
      a.enquiry_date DESC
  ");
?>
