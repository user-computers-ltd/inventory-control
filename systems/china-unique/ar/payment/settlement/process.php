<?php
  $id = $_GET["id"];
  $invoiceNos = $_POST["invoice_no"];
  $amounts = $_POST["amount"];
  $settleRemarkss = $_POST["settle_remarks"];
  $settlementRemarks = $_POST["settlement_remarks"];
  $action = $_POST["action"];

  if (assigned($id)) {

    $payment = query("
      SELECT
        a.payment_no                                AS `payment_no`,
        DATE_FORMAT(a.payment_date, \"%Y-%m-%d\")   AS `payment_date`,
        a.debtor_code                               AS `debtor_code`,
        IFNULL(c.english_name, \"Unknown\")         AS `debtor_name`,
        a.currency_code                             AS `currency_code`,
        a.exchange_rate                             AS `exchange_rate`,
        SUM(a.amount)                               AS `payment_amount`,
        a.remarks                                   AS `remarks`,
        a.status                                    AS `status`
      FROM
        `ar_payment` AS a
      LEFT JOIN
        `debtor` AS c
      ON a.debtor_code=c.code
      WHERE
        a.id=\"$id\"
    ")[0];

    if (assigned($payment)) {
      $paymentNo = $payment["payment_no"];
      $paymentDate = $payment["payment_date"];
      $debtorCode = $payment["debtor_code"];
      $debtorName = $payment["debtor_name"];
      $currencyCode = $payment["currency_code"];
      $exchangeRate = $payment["exchange_rate"];
      $paymentAmount = $payment["payment_amount"];
      $status = $payment["status"];
      $settlemntVouchers = query("SELECT * FROM `ar_settlement` WHERE payment_no=\"$paymentNo\" ORDER BY settlement_index ASC");

      if ($action === "save" || $action === "settle") {
        $queries = array();

        /* Remove the previous settlements. */
        array_push($queries, "DELETE FROM `ar_settlement` WHERE payment_no=\"$paymentNo\"");

        /* Insert new settlements. */
        $values = array();

        for ($i = 0; $i < count($invoiceNos); $i++) {
          $invoiceNo = $invoiceNos[$i];
          $amount = $amounts[$i];
          $settleRemarks = $settleRemarkss[$i];

          array_push($values, "(\"$i\", \"$invoiceNo\", \"$paymentNo\", \"\", \"$amount\", \"$settleRemarks\")");
        }

        if (count($values) > 0) {
          array_push($queries, "
            INSERT INTO
              `ar_settlement`
                (settlement_index, invoice_no, payment_no, credit_note_no, amount, settle_remarks)
              VALUES
          " . join(", ", $values));

        }

        for ($i = 0; $i < count($invoiceNos); $i++) {
          query(recordInvoiceAction($action . "_settlement", $invoiceNos[$i], $settlementRemarks));
        }

        execute($queries);

        header("Location: " . AR_PAYMENT_ISSUED_URL);
      }

      $invoiceVouchers = query("
        SELECT
          a.invoice_no                            AS `invoice_no`,
          a.amount - IFNULL(b.settled_amount, 0)  AS `amount`
        FROM
          (SELECT y.debtor_code, y.currency_code, y.invoice_no, y.status, SUM(x.amount) AS `amount` FROM `ar_inv_item` AS x LEFT JOIN `ar_inv_header` AS y ON x.invoice_no=y.invoice_no GROUP BY y.debtor_code, y.currency_code, y.invoice_no, y.status HAVING y.status=\"SAVED\") AS a
        LEFT JOIN
          (SELECT
            invoice_no    AS `invoice_no`,
            SUM(amount)   AS `settled_amount`
          FROM
            `ar_settlement`
          WHERE
            payment_no!=\"$paymentNo\"
          GROUP BY
            invoice_no) AS b
        ON a.invoice_no=b.invoice_no
        WHERE
          a.debtor_code=\"$debtorCode\" AND
          a.currency_code=\"$currencyCode\" AND
          ROUND(a.amount - IFNULL(b.settled_amount, 0), 2) > 0
      ");
    }
  }
?>
