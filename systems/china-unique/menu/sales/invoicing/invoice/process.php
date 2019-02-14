<?php
  $id = $_GET["id"];
  $invoiceNo = $_POST["invoice_no"];
  $invoiceDate = $_POST["invoice_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $stockOutNos = $_POST["stock_out_no"];
  $doNos = $_POST["do_no"];
  $amounts = $_POST["amount"];

  /* If a form is submitted, update or insert the outbound invoice. */
  if (
    assigned($invoiceNo) &&
    assigned($invoiceDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($status) &&
    assigned($stockOutNos) &&
    assigned($doNos) &&
    assigned($amounts)
  ) {
    $queries = array();

    /* If an id is given, remove the previous outbound invoice first. */
    if (assigned($id)) {
      array_push($queries, "DELETE a FROM `out_inv_model` AS a LEFT JOIN `out_inv_header` AS b ON a.invoice_no=b.invoice_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `out_inv_header` WHERE id=\"$id\"");
    }

    /* If the status is not delete, insert a new outbound invoice. */
    if ($status != "DELETED") {

      $values = array();

      for ($i = 0; $i < count($stockOutNos); $i++) {
        $stockOutNo = $stockOutNos[$i];
        $doNo = $doNos[$i];
        $amount = $amounts[$i];

        array_push($values, "(\"$invoiceNo\", \"$i\", \"$stockOutNo\", \"$doNo\", \"$amount\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `out_inv_model`
              (invoice_no, invoice_index, stock_out_no, do_no, amount)
            VALUES
        " . join(", ", $values));
      }

      array_push($queries, "
        INSERT INTO
          `out_inv_header`
            (invoice_no, invoice_date, debtor_code, currency_code, exchange_rate, remarks, status)
          VALUES
            (\"$invoiceNo\", \"$invoiceDate\", \"$debtorCode\", \"$currencyCode\", \"$exchangeRate\", \"$remarks\", \"$status\")
      ");
    }

    execute($queries);

    header("Location: " . SALES_INVOICE_SAVED_URL);
  }

  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
  $brands = query("SELECT code, name FROM `brand`");
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  $stockOutVouchers = array();
  $deliveryOrders = array();

  foreach ($debtors as $debtor) {
    $dCode = $debtor["code"];

    $stockOutPointer = &$stockOutVouchers;
    $doPointer = &$deliveryOrders;

    if (!isset($stockOutPointer[$dCode])) {
      $stockOutPointer[$dCode] = array();
      $doPointer[$dCode] = array();
    }
    $stockOutPointer = &$stockOutPointer[$dCode];
    $doPointer = &$doPointer[$dCode];

    foreach ($currencies as $cCode => $rate) {

      $stockOutResults = query("
        SELECT
          a.stock_out_no                                          AS `stock_out_no`,
          (b.amount * (100 - a.discount) / 100) - c.paid_amount   AS `amount`
        FROM
          `stock_out_header` AS a
        LEFT JOIN
          (SELECT
            stock_out_no      AS `stock_out_no`,
            SUM(qty * price)  AS `amount`
          FROM
            `stock_out_model`
          GROUP BY
            stock_out_no) AS b
        ON a.stock_out_no=b.stock_out_no
        LEFT JOIN
          (SELECT
            stock_out_no      AS `stock_out_no`,
            SUM(amount)       AS `paid_amount`
          FROM
            `out_inv_model` AS m
          LEFT JOIN
            `out_inv_header` AS h
          ON
            m.invoice_no=h.invoice_no WHERE h.id!=\"$id\"
          GROUP BY
            stock_out_no) AS c
        ON a.stock_out_no=c.stock_out_no
        WHERE
          a.status=\"POSTED\" AND
          a.debtor_code=\"$dCode\" AND
          a.currency_code=\"$cCode\"
      ");

      $doResults = query("
        SELECT
          a.do_no                                                 AS `do_no`,
          (b.amount * (100 - a.discount) / 100) - c.paid_amount   AS `amount`
        FROM
          `sdo_header` AS a
        LEFT JOIN
          (SELECT
            do_no             AS `do_no`,
            SUM(qty * price)  AS `amount`
          FROM
            `sdo_model`
          GROUP BY
            do_no) AS b
        ON a.do_no=b.do_no
        LEFT JOIN
          (SELECT
            do_no             AS `do_no`,
            SUM(amount)       AS `paid_amount`
          FROM
            `out_inv_model` AS m
          LEFT JOIN
            `out_inv_header` AS h
          ON
            m.invoice_no=h.invoice_no WHERE h.id!=\"$id\"
          GROUP BY
            do_no) AS c
        ON a.do_no=c.do_no
        WHERE
          a.status=\"POSTED\" AND
          a.debtor_code=\"$dCode\" AND
          a.currency_code=\"$cCode\"
      ");

      if (count($stockOutResults) > 0) {
        $stockOutPointer[$cCode] = $stockOutResults;
      }

      if (count($doResults) > 0) {
        $doPointer[$cCode] = $doResults;
      }
    }
  }

  /* If an id is given, attempt to retrieve an existing outbound invoice. */
  if (assigned($id)) {
    $headline = SALES_INVOICE_PRINTOUT_TITLE;

    $invoiceHeader = query("
      SELECT
        *,
        DATE_FORMAT(invoice_date, '%Y-%m-%d') AS `invoice_date`
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
      $remarks = $invoiceHeader["remarks"];
      $status = $invoiceHeader["status"];
      $invoiceVouchers = query("
        SELECT
          stock_out_no,
          do_no,
          amount
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
    $headline = SALES_INVOICE_CREATE_TITLE;
    $invoiceNo = "INV" . date("YmdHis");
    $invoiceDate = date("Y-m-d");
    $debtorCode = "";
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[$currencyCode];
    $status = "DRAFT";
    $invoiceVouchers = array();
  }
?>
