<?php
  $id = $_GET["id"];
  $paymentNos = $_POST["payment_no"];
  $creditNoteNos = $_POST["credit_note_no"];
  $amounts = $_POST["amount"];

  if (assigned($id)) {

    $invoice = query("
      SELECT
        a.invoice_no                                AS `invoice_no`,
        DATE_FORMAT(a.invoice_date, \"%Y-%m-%d\")   AS `invoice_date`,
        a.debtor_code                               AS `debtor_code`,
        IFNULL(c.english_name, \"Unknown\")         AS `debtor_name`,
        a.currency_code                             AS `currency_code`,
        a.exchange_rate                             AS `exchange_rate`,
        SUM(b.amount)                               AS `invoice_amount`,
        a.remarks                                   AS `remarks`,
        a.status                                    AS `status`
      FROM
        `ar_inv_header` AS a
      LEFT JOIN
        `ar_inv_item` AS b
      ON a.invoice_no=b.invoice_no
      LEFT JOIN
        `debtor` AS c
      ON a.debtor_code=c.code
      WHERE
        a.id=\"$id\"
    ")[0];

    if (assigned($invoice)) {
      $invoiceNo = $invoice["invoice_no"];
      $invoiceDate = $invoice["invoice_date"];
      $debtorCode = $invoice["debtor_code"];
      $debtorName = $invoice["debtor_name"];
      $currencyCode = $invoice["currency_code"];
      $exchangeRate = $invoice["exchange_rate"];
      $invoiceAmount = $invoice["invoice_amount"];
      $remarks = $invoice["remarks"];
      $status = $invoice["status"];
      $settlemntVouchers = query("SELECT * FROM `ar_settlement` WHERE invoice_no=\"$invoiceNo\" ORDER BY settlement_index ASC");

      /* If a form is submitted, update or insert the outbound invoice. */
      if (
        assigned($paymentNos) &&
        assigned($creditNoteNos) &&
        assigned($amounts)
      ) {
        $queries = array();

        /* Remove the previous settlements. */
        array_push($queries, "DELETE FROM `ar_settlement` WHERE invoice_no=\"$invoiceNo\"");

        /* Insert new settlements. */
        $values = array();

        for ($i = 0; $i < count($paymentNos); $i++) {
          $paymentNo = $paymentNos[$i];
          $creditNoteNo = $creditNoteNos[$i];
          $amount = $amounts[$i];

          if (!assigned($creditNoteNo) && !assigned($paymentNo) && assigned($amount)) {
            $paymentNo = "PY" . date("YmdHis");
            $paymentDate = date("Y-m-d");
            
            array_push($queries, "
              INSERT INTO
                `ar_payment`
                  (payment_no, payment_date, debtor_code, currency_code, exchange_rate, amount, remarks, status)
                VALUES
                  (\"$paymentNo\", \"$paymentDate\", \"$debtorCode\", \"$currencyCode\", \"$exchangeRate\", \"$amount\", \"$remarks\", \"SAVED\")
            ");
          }

          array_push($values, "(\"$i\", \"$invoiceNo\", \"$paymentNo\", \"$creditNoteNo\", \"$amount\")");
        }

        if (count($values) > 0) {
          array_push($queries, "
            INSERT INTO
              `ar_settlement`
                (settlement_index, invoice_no, payment_no, credit_note_no, amount)
              VALUES
          " . join(", ", $values));
        }

        execute($queries);

        header("Location: " . AR_INVOICE_ISSUED_URL);
      }

      $paymentVouchers = query("
        SELECT
          a.payment_no                            AS `payment_no`,
          a.amount - IFNULL(b.settled_amount, 0)  AS `amount`
        FROM
          `ar_payment` AS a
        LEFT JOIN
          (SELECT
            payment_no    AS `payment_no`,
            SUM(amount)   AS `settled_amount`
          FROM
            `ar_settlement`
          WHERE
            invoice_no!=\"$invoiceNo\"
          GROUP BY
            payment_no) AS b
        ON a.payment_no=b.payment_no
        WHERE
          a.debtor_code=\"$debtorCode\" AND
          a.currency_code=\"$currencyCode\" AND
          ROUND(a.amount - IFNULL(b.settled_amount, 0), 2) > 0
      ");

      $creditNoteVouchers = query("
        SELECT
          a.credit_note_no                        AS `credit_note_no`,
          a.amount - IFNULL(b.settled_amount, 0)  AS `amount`
        FROM
          `ar_credit_note` AS a
        LEFT JOIN
          (SELECT
            credit_note_no    AS `credit_note_no`,
            SUM(amount)       AS `settled_amount`
          FROM
            `ar_settlement`
          WHERE
            invoice_no!=\"$invoiceNo\"
          GROUP BY
            credit_note_no) AS b
        ON a.credit_note_no=b.credit_note_no
        WHERE
          a.debtor_code=\"$debtorCode\" AND
          a.currency_code=\"$currencyCode\" AND
          a.invoice_no!=\"$invoiceNo\" AND
          ROUND(a.amount - IFNULL(b.settled_amount, 0), 2) > 0
      ");

      $ownCreditNoteVouchers = query("SELECT * FROM `ar_credit_note` WHERE invoice_no=\"$invoiceNo\"");
    }
  }
?>
