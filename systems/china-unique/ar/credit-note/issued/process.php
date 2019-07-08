<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $creditNoteIds = $_POST["credit_note_id"];

  if (assigned($action) && assigned($creditNoteIds) && count($creditNoteIds) > 0) {
    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $creditNoteIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $creditNoteIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $creditNoteIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_credit_note` AS b ON a.credit_note_no=b.credit_note_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_credit_note` WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_CREDIT_NOTE_PRINTOUT_URL . "?$printoutParams");
      exit(0);
    }

    execute($queries);
  }

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.credit_note_date >= \"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.credit_note_date <= \"$to\"";
  }

  $creditNoteHeaders = query("
    SELECT
      a.id                                              AS `id`,
      DATE_FORMAT(a.credit_note_date, \"%d-%m-%Y\")     AS `date`,
      a.credit_note_no                                  AS `credit_note_no`,
      a.debtor_code                                     AS `debtor_code`,
      IFNULL(c.english_name, \"Unknown\")               AS `debtor_name`,
      a.currency_code                                   AS `currency_code`,
      a.amount                                          AS `amount`,
      ROUND(a.amount - IFNULL(b.settled_amount, 0), 2)  AS `remaining`
    FROM
      `ar_credit_note` AS a
    LEFT JOIN
      (SELECT
        credit_note_no    AS `credit_note_no`,
        SUM(amount)       AS `settled_amount`
      FROM
        `ar_settlement`
      GROUP BY
        credit_note_no) AS b
    ON a.credit_note_no=b.credit_note_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"SAVED\"
      $whereClause
    ORDER BY
      a.credit_note_date DESC
  ");
?>
