<?php
  $ids = $_GET["id"];
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

  $invoiceHeaders = array();
  $invoiceModelList = array();
  $invoiceModels = array();

  /* If an id is given, retrieve from an existing stock out voucher. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "b.id=\"$i\""; }, $ids));

    $invoiceHeaders = query("
      SELECT
        invoice_no                              AS `invoice_no`,
        DATE_FORMAT(invoice_date, '%d-%m-%Y')   AS `date`,
        debtor_code                             AS `debtor_code`,
        currency_code                           AS `currency_code`,
        exchange_rate                           AS `exchange_rate`,
        remarks                                 AS `remarks`,
        status                                  AS `status`
      FROM
        `ar_inv_header`
      WHERE
        $headerWhereClause
    ");

    $invoiceModelList = query("
      SELECT
        a.invoice_no                            AS `invoice_no`,
        a.do_no                                 AS `do_no`,
        a.stock_out_no                          AS `stock_out_no`,
        a.stock_in_no                           AS `stock_in_no`,
        a.settle_remarks                        AS `settle_remarks`,
        a.amount                                AS `amount`
      FROM
        `ar_inv_item` AS a
      LEFT JOIN
        `ar_inv_header` AS b
      ON a.invoice_no=b.invoice_no
      WHERE
        $modelWhereClause
      ORDER BY
        a.invoice_no ASC,
        a.invoice_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($invoiceNo) &&
    assigned($invoiceDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($status)
  ) {
    $invoiceDate = new DateTime($invoiceDate);
    $invoiceDate = $invoiceDate->format("d-m-Y");

    $invoiceHeaders = array(array(
      "invoice_no"          => $invoiceNo,
      "date"                => $invoiceDate,
      "debtor_code"         => $debtorCode,
      "currency_code"       => $currencyCode,
      "exchange_rate"       => $exchangeRate,
      "remarks"             => $remarks,
      "status"              => $status
    ));

    for ($i = 0; $i < count($stockOutNos); $i++) {
      array_push($invoiceModelList, array(
        "invoice_no"        => $invoiceNo,
        "do_no"             => $doNos[$i],
        "stock_out_no"      => $stockOutNos[$i],
        "stock_in_no"       => $stockInNos[$i],
        "amount"            => $amounts[$i]
      ));
    }
  }

  if (count($invoiceHeaders) > 0) {
    foreach ($invoiceHeaders as &$invoiceHeader) {
      $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"" . $invoiceHeader["debtor_code"] . "\"")[0];

      $invoiceHeader["debtor"] = $invoiceHeader["debtor_code"] . " - " . (isset($debtor) ? $debtor["name"] : "Unknown");
      $invoiceHeader["currency"] = $invoiceHeader["currency_code"] . " @ " . $invoiceHeader["exchange_rate"];
    }
  }

  if (count($invoiceModelList) > 0) {
    foreach ($invoiceModelList as $invoiceModel) {
      $invoiceNo = $invoiceModel["invoice_no"];

      $arrayPointer = &$invoiceModels;

      if (!isset($arrayPointer[$invoiceNo])) {
        $arrayPointer[$invoiceNo] = array();
      }
      $arrayPointer = &$arrayPointer[$invoiceNo];

      array_push($arrayPointer, $invoiceModel);
    }
  }
?>
