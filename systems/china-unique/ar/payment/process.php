<?php
  $id = $_GET["id"];
  $paymentNo = $_POST["payment_no"];
  $paymentDate = $_POST["payment_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $amount = $_POST["amount"];
  $remarks = $_POST["remarks"];
  $action = $_POST["action"];

  /* If a form is submitted, update or insert the payment. */
  if (
    assigned($paymentNo) &&
    assigned($paymentDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($amount) &&
    assigned($action)
  ) {
    $queries = array();

    if ($action === "delete" && assigned($id)) {
      array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_payment` AS b ON a.payment_no=b.payment_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `ar_payment` WHERE id=\"$id\"");
    } else if ($action === "create") {
      array_push($queries, "
        INSERT INTO
          `ar_payment`
            (payment_no, payment_date, debtor_code, currency_code, exchange_rate, amount, remarks, status)
          VALUES
            (\"$paymentNo\", \"$paymentDate\", \"$debtorCode\", \"$currencyCode\", \"$exchangeRate\", \"$amount\", \"$remarks\", \"SAVED\")
      ");
    } else if ($action === "update" && assigned($id)) {
      $oldPayment = query("SELECT * FROM `ar_payment` WHERE id=\"$id\"")[0];

      if (
        $oldPayment["payment_no"] !== $paymentNo ||
        $oldPayment["debtor_code"] !== $debtorCode ||
        $oldPayment["currency_code"] !== $currencyCode
      ) {
        array_push($queries, "DELETE a FROM `ar_settlement` AS a LEFT JOIN `ar_payment` AS b ON a.payment_no=b.payment_no WHERE b.id=\"$id\"");
      }

      array_push($queries, "
        UPDATE
          `ar_payment`
        SET
          payment_no=\"$paymentNo\",
          payment_date=\"$paymentDate\",
          debtor_code=\"$debtorCode\",
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

    query(recordPaymentAction($action . "_payment", $paymentNo));

    header("Location: " . AR_PAYMENT_ISSUED_URL);
  }

  $results = query("SELECT code, english_name AS name, credit_term FROM `debtor` ORDER BY code ASC");
  $debtors = array();
  foreach ($results as $debtor) {
    $debtors[$debtor["code"]] = $debtor;
  }
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  /* If an id is given, attempt to retrieve an existing payment. */
  if (assigned($id)) {
    $headline = AR_PAYMENT_PRINTOUT_TITLE;

    $paymentHeader = query("
      SELECT
        *,
        DATE_FORMAT(payment_date, \"%Y-%m-%d\")   AS `payment_date`
      FROM
        `ar_payment`
      WHERE id=\"$id\"
    ")[0];

    if (isset($paymentHeader)) {
      $paymentNo = $paymentHeader["payment_no"];
      $paymentDate = $paymentHeader["payment_date"];
      $debtorCode = $paymentHeader["debtor_code"];
      $currencyCode = $paymentHeader["currency_code"];
      $exchangeRate = $paymentHeader["exchange_rate"];
      $amount = $paymentHeader["amount"];
      $remarks = $paymentHeader["remarks"];
      $status = $paymentHeader["status"];
    }
  }

  /* Else, initialize values for a new payment. */
  else {
    $headline = AR_PAYMENT_CREATE_TITLE;
    $paymentNo = "PY" . date("YmdHis");
    $paymentDate = date("Y-m-d");
    $debtorCode = "";
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[$currencyCode];
    $amount = 0;
    $status = "DRAFT";
  }
?>
