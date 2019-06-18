<?php
  $id = $_GET["id"];
  $invoiceNo = $_POST["invoice_no"];
  $invoiceDate = $_POST["invoice_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $maturityDate = $_POST["maturity_date"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $doNos = $_POST["do_no"];
  $stockOutNos = $_POST["stock_out_no"];
  $stockInNos = $_POST["stock_in_no"];
  $amounts = $_POST["amount"];
  $settlements = $_POST["settlement"];
  $settleRemarkss = $_POST["settle_remarks"];

  /* If a form is submitted, update or insert the outbound invoice. */
  if (
    assigned($invoiceNo) &&
    assigned($invoiceDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($status) &&
    assigned($doNos) &&
    assigned($stockOutNos) &&
    assigned($stockInNos) &&
    assigned($amounts) &&
    assigned($settlements) &&
    assigned($settleRemarkss)
  ) {
    $queries = array();

    /* If an id is given, remove the previous outbound invoice first. */
    if (assigned($id)) {
      array_push($queries, "DELETE a FROM `out_inv_model` AS a LEFT JOIN `out_inv_header` AS b ON a.invoice_no=b.invoice_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `out_inv_header` WHERE id=\"$id\"");
    }

    /* If the status is not delete, insert a new outbound invoice. */
    if ($status !== "DELETED") {

      $values = array();

      for ($i = 0; $i < count($stockOutNos); $i++) {
        $doNo = $doNos[$i];
        $stockOutNo = $stockOutNos[$i];
        $stockInNo = $stockInNos[$i];
        $amount = $amounts[$i];
        $settlement = $settlements[$i];
        $settleRemarks = $settleRemarkss[$i];

        array_push($values, "(\"$invoiceNo\", \"$i\", \"$doNo\", \"$stockOutNo\", \"$stockInNo\", \"$amount\", \"$settlement\", \"$settleRemarks\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `out_inv_model`
              (invoice_no, invoice_index, do_no, stock_out_no, stock_in_no, amount, settlement, settle_remarks)
            VALUES
        " . join(", ", $values));
      }

      array_push($queries, "
        INSERT INTO
          `out_inv_header`
            (invoice_no, invoice_date, debtor_code, currency_code, exchange_rate, maturity_date, remarks, status)
          VALUES
            (\"$invoiceNo\", \"$invoiceDate\", \"$debtorCode\", \"$currencyCode\", \"$exchangeRate\", \"$maturityDate\", \"$remarks\", \"$status\")
      ");
    }

    execute($queries);

    header("Location: " . OUT_INVOICE_SAVED_URL);
  }

  $results = query("SELECT code, english_name AS name, credit_term FROM `debtor`");
  $debtors = array();
  foreach ($results as $debtor) {
    $debtors[$debtor["code"]] = $debtor;
  }
  $brands = query("SELECT code, name FROM `brand`");
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  /* If an id is given, attempt to retrieve an existing outbound invoice. */
  if (assigned($id)) {
    $headline = OUT_INVOICE_PRINTOUT_TITLE;

    $invoiceHeader = query("
      SELECT
        *,
        DATE_FORMAT(invoice_date, \"%Y-%m-%d\")   AS `invoice_date`,
        DATE_FORMAT(maturity_date, \"%Y-%m-%d\")  AS `maturity_date`
      FROM
        `out_inv_header`
      WHERE id=\"$id\"
    ")[0];

    if (isset($invoiceHeader)) {
      $invoiceNo = $invoiceHeader["invoice_no"];
      $invoiceDate = $invoiceHeader["invoice_date"];
      $debtorCode = $invoiceHeader["debtor_code"];
      $currencyCode = $invoiceHeader["currency_code"];
      $exchangeRate = $invoiceHeader["exchange_rate"];
      $maturityDate = $invoiceHeader["maturity_date"];
      $remarks = $invoiceHeader["remarks"];
      $status = $invoiceHeader["status"];
      $invoiceVouchers = query("
        SELECT
          do_no,
          stock_out_no,
          stock_in_no,
          amount,
          settlement,
          IFNULL(settle_remarks, \"\") AS `settle_remarks`
        FROM
          `out_inv_model`
        WHERE
          invoice_no=\"$invoiceNo\"
        ORDER BY
          invoice_index ASC
      ");
    }
  }

  /* Else, initialize values for a new outbound invoice. */
  else {
    $headline = OUT_INVOICE_CREATE_TITLE;
    $invoiceNo = "INV" . date("YmdHis");
    $invoiceDate = date("Y-m-d");
    $debtorCode = "";
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[$currencyCode];
    $maturityDate = date("Y-m-d");
    $status = "DRAFT";
    $invoiceVouchers = array();
  }
?>
