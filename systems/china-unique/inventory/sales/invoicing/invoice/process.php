<?php
  $id = $_GET["id"];
  $invoiceNo = $_POST["invoice_no"];
  $invoiceDate = $_POST["invoice_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
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
          SELECT
            \"$invoiceNo\"                                        AS `invoice_no`,
            \"$invoiceDate\"                                      AS `invoice_date`,
            \"$debtorCode\"                                       AS `debtor_code`,
            \"$currencyCode\"                                     AS `currency_code`,
            \"$exchangeRate\"                                     AS `exchange_rate`,
            DATE_ADD(\"$invoiceDate\", INTERVAL credit_term DAY)  AS `maturity_date`,
            \"$remarks\"                                          AS `remarks`,
            \"$status\"                                           AS `status`
          FROM
            `debtor`
          WHERE code=\"$debtorCode\"
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

  $doResults = query("
    SELECT
      a.debtor_code                                                     AS `debtor_code`,
      a.currency_code                                                   AS `currency_code`,
      a.do_no                                                           AS `do_no`,
      (b.amount * (100 - a.discount) / 100) - IFNULL(c.paid_amount, 0)  AS `amount`
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
        m.do_no                               AS `do_no`,
        SUM(m.amount)                         AS `paid_amount`,
        SUM(IF(m.settlement=\"FULL\", 1, 0))  AS `settled`
      FROM
        `out_inv_model` AS m
      LEFT JOIN
        `out_inv_header` AS h
      ON
        m.invoice_no=h.invoice_no WHERE h.id!=\"$id\"
      GROUP BY
        m.do_no) AS c
    ON a.do_no=c.do_no
    WHERE
      a.status=\"POSTED\" AND
      (c.settled IS NULL OR c.settled=0) AND
      ROUND((b.amount * (100 - a.discount) / 100) - IFNULL(c.paid_amount, 0), 2) > 0
  ");

  $stockOutResults = query("
    SELECT
      a.debtor_code                                                     AS `debtor_code`,
      a.currency_code                                                   AS `currency_code`,
      a.stock_out_no                                                    AS `stock_out_no`,
      (b.amount * (100 - a.discount) / 100) - IFNULL(c.paid_amount, 0)  AS `amount`
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
        m.stock_out_no                      AS `stock_out_no`,
        SUM(m.amount)                       AS `paid_amount`,
        SUM(IF(m.settlement=\"FULL\",1, 0)) AS `settled`
      FROM
        `out_inv_model` AS m
      LEFT JOIN
        `out_inv_header` AS h
      ON
        m.invoice_no=h.invoice_no WHERE h.id!=\"$id\"
      GROUP BY
        m.stock_out_no) AS c
    ON a.stock_out_no=c.stock_out_no
    WHERE
      a.status=\"POSTED\" AND
      (c.settled IS NULL OR c.settled=0) AND
      ROUND((b.amount * (100 - a.discount) / 100) - IFNULL(c.paid_amount, 0), 2) > 0
  ");

  $stockInResults = query("
    SELECT
      a.creditor_code                                                     AS `debtor_code`,
      a.currency_code                                                     AS `currency_code`,
      a.stock_in_no                                                       AS `stock_in_no`,
      -(b.amount * (100 - a.discount) / 100) - IFNULL(c.paid_amount, 0)   AS `amount`
    FROM
      `stock_in_header` AS a
    LEFT JOIN
      (SELECT
        stock_in_no      AS `stock_in_no`,
        SUM(qty * price)  AS `amount`
      FROM
        `stock_in_model`
      GROUP BY
        stock_in_no) AS b
    ON a.stock_in_no=b.stock_in_no
    LEFT JOIN
      (SELECT
        m.stock_in_no                       AS `stock_in_no`,
        SUM(m.amount)                       AS `paid_amount`,
        SUM(IF(m.settlement=\"FULL\",1, 0)) AS `settled`
      FROM
        `out_inv_model` AS m
      LEFT JOIN
        `out_inv_header` AS h
      ON
        m.invoice_no=h.invoice_no WHERE h.id!=\"$id\"
      GROUP BY
        m.stock_in_no) AS c
    ON a.stock_in_no=c.stock_in_no
    WHERE
      a.status=\"POSTED\" AND
      a.transaction_code=\"R3\" AND
      (c.settled IS NULL OR c.settled=0) AND
      ROUND((b.amount * (100 - a.discount) / 100) - IFNULL(c.paid_amount, 0), 2) > 0
  ");

  $deliveryOrders = array();
  $stockOutVouchers = array();
  $stockInVouchers = array();

  foreach ($doResults as $doResult) {
    $dCode = $doResult["debtor_code"];
    $cCode = $doResult["currency_code"];

    $doPointer = &$deliveryOrders;

    if (!isset($doPointer[$dCode])) {
      $doPointer[$dCode] = array();
    }
    $doPointer = &$doPointer[$dCode];

    if (!isset($doPointer[$cCode])) {
      $doPointer[$cCode] = array();
    }
    $doPointer = &$doPointer[$cCode];

    array_push($doPointer, $doResult);
  }

  foreach ($stockOutResults as $stockOutResult) {
    $dCode = $stockOutResult["debtor_code"];
    $cCode = $stockOutResult["currency_code"];

    $stockOutPointer = &$stockOutVouchers;

    if (!isset($stockOutPointer[$dCode])) {
      $stockOutPointer[$dCode] = array();
    }
    $stockOutPointer = &$stockOutPointer[$dCode];

    if (!isset($stockOutPointer[$cCode])) {
      $stockOutPointer[$cCode] = array();
    }
    $stockOutPointer = &$stockOutPointer[$cCode];

    array_push($stockOutPointer, $stockOutResult);
  }

  foreach ($stockInResults as $stockInResult) {
    $dCode = $stockInResult["debtor_code"];
    $cCode = $stockInResult["currency_code"];

    $stockInPointer = &$stockInVouchers;

    if (!isset($stockInPointer[$dCode])) {
      $stockInPointer[$dCode] = array();
    }
    $stockInPointer = &$stockInPointer[$dCode];

    if (!isset($stockInPointer[$cCode])) {
      $stockInPointer[$cCode] = array();
    }
    $stockInPointer = &$stockInPointer[$cCode];

    array_push($stockInPointer, $stockInResult);
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
