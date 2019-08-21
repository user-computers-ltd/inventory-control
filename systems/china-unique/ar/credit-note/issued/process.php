<?php
  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $action = $_POST["action"];
  $creditNoteIds = $_POST["credit_note_id"];

  if (assigned($action) && assigned($creditNoteIds) && count($creditNoteIds) > 0) {
    $queries = array();

    foreach ($creditNoteIds as $creditNoteId) {
      if ($action !== "print") {
        $creditNote = query("SELECT credit_note_no FROM `ar_credit_note` WHERE id=\"$creditNoteId\"")[0];
        $creditNoteNo = assigned($creditNote) ? $creditNote["credit_note_no"] : "";
        array_push($queries, recordCreditNoteAction($action . "_credit_note", $creditNoteNo));
      }
    }

    execute($queries);

    $queries = array();

    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $creditNoteIds));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $creditNoteIds));
    $printoutParams = join("&", array_map(function ($i) { return "id[]=$i"; }, $creditNoteIds));

    if ($action === "delete") {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_credit_note` AS b ON a.credit_note_no=b.credit_note_no WHERE $modelWhereClause");
      array_push($queries, "DELETE FROM `ar_credit_note` WHERE $headerWhereClause");
    } else if ($action === "print") {
      header("Location: " . AR_CREDIT_NOTE_PRINTOUT_URL . "?$printoutParams");
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

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(b.english_name, \"Unknown\")   AS `name`
    FROM
      `ar_credit_note` AS a
    LEFT JOIN
      `debtor` AS b
    ON a.debtor_code=b.code
    ORDER BY
      code ASC
  ");
?>
