<?php
  $ids = $_GET["id"];
  $paymentNo = $_POST["payment_no"];
  $paymentDate = $_POST["payment_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $amount = $_POST["amount"];
  $remarks = $_POST["remarks"];

  $paymentHeaders = array();

  /* If an id is given, retrieve from an existing stock out voucher. */
  if (assigned($ids) && count($ids) > 0) {
    $whereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));

    $paymentHeaders = query("
      SELECT
        payment_no                              AS `payment_no`,
        DATE_FORMAT(payment_date, '%d-%m-%Y')   AS `payment_date`,
        debtor_code                             AS `debtor_code`,
        currency_code                           AS `currency_code`,
        exchange_rate                           AS `exchange_rate`,
        amount                                  AS `amount`,
        remarks                                 AS `remarks`
      FROM
        `ar_payment`
      WHERE
        $whereClause
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($paymentNo) &&
    assigned($paymentDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($amount)
  ) {
    $paymentDate = new DateTime($paymentDate);
    $paymentDate = $paymentDate->format("d-m-Y");

    $paymentHeaders = array(array(
      "payment_no"          => $paymentNo,
      "payment_date"        => $paymentDate,
      "debtor_code"         => $debtorCode,
      "currency_code"       => $currencyCode,
      "exchange_rate"       => $exchangeRate,
      "amount"              => $amount,
      "remarks"             => $remarks
    ));
  }

  if (count($paymentHeaders) > 0) {
    foreach ($paymentHeaders as &$paymentHeader) {
      $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"" . $paymentHeader["debtor_code"] . "\"")[0];

      $paymentHeader["debtor"] = $paymentHeader["debtor_code"] . " - " . (isset($debtor) ? $debtor["name"] : "Unknown");
      $paymentHeader["currency"] = $paymentHeader["currency_code"] . " @ " . $paymentHeader["exchange_rate"];
    }
  }
?>
