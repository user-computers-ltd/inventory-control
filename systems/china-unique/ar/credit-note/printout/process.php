<?php
  $ids = $_GET["id"];
  $creditNoteNo = $_POST["credit_note_no"];
  $creditNoteDate = $_POST["credit_note_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $amount = $_POST["amount"];
  $remarks = $_POST["remarks"];

  $creditNoteHeaders = array();

  /* If an id is given, retrieve from an existing stock out voucher. */
  if (assigned($ids) && count($ids) > 0) {
    $whereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));

    $creditNoteHeaders = query("
      SELECT
        credit_note_no                              AS `credit_note_no`,
        DATE_FORMAT(credit_note_date, '%d-%m-%Y')   AS `credit_note_date`,
        debtor_code                                 AS `debtor_code`,
        currency_code                               AS `currency_code`,
        exchange_rate                               AS `exchange_rate`,
        amount                                      AS `amount`,
        remarks                                     AS `remarks`
      FROM
        `ar_credit_note`
      WHERE
        $whereClause
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($creditNoteNo) &&
    assigned($creditNoteDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($amount)
  ) {
    $creditNoteDate = new DateTime($creditNoteDate);
    $creditNoteDate = $creditNoteDate->format("d-m-Y");

    $creditNoteHeaders = array(array(
      "credit_note_no"          => $creditNoteNo,
      "credit_note_date"        => $creditNoteDate,
      "debtor_code"         => $debtorCode,
      "currency_code"       => $currencyCode,
      "exchange_rate"       => $exchangeRate,
      "amount"              => $amount,
      "remarks"             => $remarks
    ));
  }

  if (count($creditNoteHeaders) > 0) {
    foreach ($creditNoteHeaders as &$creditNoteHeader) {
      $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"" . $creditNoteHeader["debtor_code"] . "\"")[0];

      $creditNoteHeader["debtor"] = $creditNoteHeader["debtor_code"] . " - " . (isset($debtor) ? $debtor["name"] : "Unknown");
      $creditNoteHeader["currency"] = $creditNoteHeader["currency_code"] . " @ " . $creditNoteHeader["exchange_rate"];
    }
  }
?>
