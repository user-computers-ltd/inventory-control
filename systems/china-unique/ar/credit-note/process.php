<?php
  $id = $_GET["id"];
  $creditNoteNo = $_POST["credit_note_no"];
  $creditNoteDate = $_POST["credit_note_date"];
  $debtorCode = $_POST["debtor_code"];
  $invoiceNo = $_POST["invoice_no"];
  $amount = $_POST["amount"];
  $remarks = $_POST["remarks"];
  $action = $_POST["action"];

  /* If a form is submitted, update or insert the credit. */
  if (
    assigned($creditNoteNo) &&
    assigned($creditNoteDate) &&
    assigned($debtorCode) &&
    assigned($invoiceNo) &&
    assigned($amount) &&
    assigned($action)
  ) {
    $invoice = query("SELECT * FROM `ar_inv_header` WHERE invoice_no=\"$invoiceNo\"")[0];
    $currencyCode = $invoice["currency_code"];
    $exchangeRate = $invoice["exchange_rate"];

    $queries = array();

    if ($action === "delete" && assigned($id)) {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_credit_note` AS b ON a.credit_note_no=b.credit_note_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `ar_credit_note` WHERE id=\"$id\"");
    } else if ($action === "create") {
      array_push($queries, "
        INSERT INTO
          `ar_credit_note`
            (credit_note_no, credit_note_date, debtor_code, invoice_no, currency_code, exchange_rate, amount, remarks, status)
          VALUES
            (\"$creditNoteNo\", \"$creditNoteDate\", \"$debtorCode\", \"$invoiceNo\", \"$currencyCode\", \"$exchangeRate\", \"$amount\", \"$remarks\", \"SAVED\")
      ");
    } else if ($action === "update" && assigned($id)) {
      $oldCreditNote = query("SELECT * FROM `ar_credit_note` WHERE id=\"$id\"")[0];

      if (
        $oldCreditNote["credit_note_no"] !== $creditNoteNo ||
        $oldCreditNote["debtor_code"] !== $debtorCode ||
        $oldCreditNote["currency_code"] !== $currencyCode
      ) {
        array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_credit_note` AS b ON a.credit_note_no=b.credit_note_no WHERE b.id=\"$id\"");
      }

      array_push($queries, "
        UPDATE
          `ar_credit_note`
        SET
          credit_note_no=\"$creditNoteNo\",
          credit_note_date=\"$creditNoteDate\",
          debtor_code=\"$debtorCode\",
          invoice_no=\"$invoiceNo\",
          currency_code=\"$currencyCode\",
          exchange_rate=\"$exchangeRate\",
          amount=\"$amount\",
          remarks=\"$remarks\",
          status=\"SAVED\"
        WHERE
          id=\"$id\"
      ");
    }

    execute($queries);

    header("Location: " . AR_CREDIT_NOTE_ISSUED_URL);
  }

  $results = query("SELECT code, english_name AS name, credit_term FROM `debtor`");
  $debtors = array();
  foreach ($results as $debtor) {
    $debtors[$debtor["code"]] = $debtor;
  }

  $results = query("
    SELECT
      a.debtor_code     AS `debtor_code`,
      a.invoice_no      AS `invoice_no`,
      b.invoice_amount  AS `invoice_amount`
    FROM
      `ar_inv_header` AS a
    LEFT JOIN
      (SELECT
        invoice_no  AS `invoice_no`,
        SUM(amount) AS `invoice_amount`
      FROM
        `ar_inv_item`
      GROUP BY
        invoice_no) AS b
    ON a.invoice_no=b.invoice_no
    WHERE
      a.status=\"SAVED\" AND b.invoice_amount < 0
  ");

  $invoices = array();

  foreach ($results as $invoice) {
    $dCode = $invoice["debtor_code"];
    $invNo = $invoice["invoice_no"];

    $invoicePointer = &$invoices;

    if (!isset($invoicePointer[$dCode])) {
      $invoicePointer[$dCode] = array();
    }
    $invoicePointer = &$invoicePointer[$dCode];

    if (!isset($invoicePointer[$invNo])) {
      $invoicePointer[$invNo] = array();
    }
    $invoicePointer[$invNo] = $invoice;
  }

  /* If an id is given, attempt to retrieve an existing credit note. */
  if (assigned($id)) {
    $headline = AR_CREDIT_NOTE_PRINTOUT_TITLE;

    $creditHeader = query("
      SELECT
        *,
        DATE_FORMAT(credit_note_date, \"%Y-%m-%d\")   AS `credit_note_date`
      FROM
        `ar_credit_note`
      WHERE id=\"$id\"
    ")[0];

    if (isset($creditHeader)) {
      $creditNoteNo = $creditHeader["credit_note_no"];
      $creditNoteDate = $creditHeader["credit_note_date"];
      $debtorCode = $creditHeader["debtor_code"];
      $invoiceNo = $creditHeader["invoice_no"];
      $amount = $creditHeader["amount"];
      $remarks = $creditHeader["remarks"];
      $status = $creditHeader["status"];
    }
  }

  /* Else, initialize values for a new credit. */
  else {
    $headline = AR_CREDIT_NOTE_CREATE_TITLE;
    $creditNoteNo = "CR" . date("YmdHis");
    $creditNoteDate = date("Y-m-d");
    $debtorCode = "";
    $invoiceNo = "";
    $amount = 0;
    $status = "DRAFT";
  }
?>
