<?php
  $id = $_GET["id"];
  $stockOutNo = $_POST["stock_out_no"];
  $stockOutDate = $_POST["stock_out_date"];
  $transactionCode = $_POST["transaction_code"];
  $warehouseCode = $_POST["warehouse_code"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $netAmount = $_POST["net_amount"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $invoiceNo = $_POST["invoice_no"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  $stockOutHeader = null;
  $stockOutModels = array();

  /* If an id is given, retrieve from an existing stock out voucher. */
  if (assigned($id)) {
    $stockOutHeader = query("
      SELECT
        stock_out_no                              AS `stock_out_no`,
        transaction_code                          AS `transaction_code`,
        warehouse_code                            AS `warehouse_code`,
        DATE_FORMAT(stock_out_date, '%d-%m-%Y')   AS `date`,
        debtor_code                               AS `debtor_code`,
        currency_code                             AS `currency_code`,
        exchange_rate                             AS `exchange_rate`,
        net_amount                                AS `net_amount`,
        discount                                  AS `discount`,
        tax                                       AS `tax`,
        invoice_no                                AS `invoice_no`,
        remarks                                   AS `remarks`,
        status                                    AS `status`
      FROM
        `stock_out_header`
      WHERE
        id=\"$id\"
    ")[0];

    $stockOutModels = query("
      SELECT
        b.name                                  AS `brand`,
        a.model_no                              AS `model_no`,
        a.price                                 AS `price`,
        a.qty                                   AS `qty`,
        a.qty * a.price                         AS `subtotal`
      FROM
        `stock_out_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `stock_out_header` AS c
      ON a.stock_out_no=c.stock_out_no
      WHERE
        c.id=\"$id\"
      ORDER BY
        a.stock_out_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($stockOutNo) &&
    assigned($stockOutDate) &&
    assigned($transactionCode) &&
    assigned($warehouseCode) &&
    assigned($tax) &&
    assigned($status)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $stockOutHeader = array(
      "stock_out_no"        => $stockOutNo,
      "transaction_code"    => $transactionCode,
      "warehouse_code"      => $warehouseCode,
      "date"                => $stockOutDate,
      "debtor_code"         => $debtorCode,
      "currency_code"       => $currencyCode,
      "exchange_rate"       => $exchangeRate,
      "net_amount"          => $netAmount,
      "discount"            => $discount,
      "tax"                 => $tax,
      "invoice_no"          => $invoiceNo,
      "remarks"             => $remarks,
      "status"              => $status
    );

    $stockOutModels = array();

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($stockOutModels, array(
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i],
        "subtotal"          => $prices[$i] * $qtys[$i]
      ));
    }
  }

  if (isset($stockOutHeader)) {
    $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"" . $stockOutHeader["debtor_code"] . "\"")[0];
    $warehouse = query("SELECT name FROM `warehouse` WHERE code=\"" . $stockOutHeader["warehouse_code"] . "\"")[0];

    $stockOutHeader["transaction_type"] = $stockOutHeader["transaction_code"] . " - " . $TRANSACTION_CODES[$stockOutHeader["transaction_code"]];
    $stockOutHeader["warehouse"] = $stockOutHeader["warehouse_code"] . " - " . (isset($warehouse) ? $warehouse["name"] : "Unknown");
    $stockOutHeader["debtor"] = $stockOutHeader["debtor_code"] . " - " . (isset($debtor) ? $debtor["name"] : "Unknown");
    $stockOutHeader["currency"] = $stockOutHeader["currency_code"] . " @ " . $stockOutHeader["exchange_rate"];
  }

  $miscellaneous = $stockOutHeader["transaction_code"] != "S1" && $stockOutHeader["transaction_code"] != "S3";
?>
