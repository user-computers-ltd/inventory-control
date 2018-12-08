<?php
  $id = $_GET["id"];
  $stockInNo = $_POST["stock_in_no"];
  $stockInDate = $_POST["stock_in_date"];
  $transactionCode = $_POST["transaction_code"];
  $warehouseCode = $_POST["warehouse_code"];
  $creditorCode = $_POST["creditor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $netAmount = $_POST["net_amount"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  $stockInHeader = null;
  $stockInModels = array();

  /* If an id is given, retrieve from an existing sales order. */
  if (assigned($id)) {
    $stockInHeader = query("
      SELECT
        a.stock_in_no                              AS `stock_in_no`,
        a.transaction_code                         AS `transaction_code`,
        a.warehouse_code                           AS `warehouse_code`,
        DATE_FORMAT(a.stock_in_date, '%d-%m-%Y')   AS `date`,
        a.creditor_code                            AS `creditor_code`,
        a.currency_code                            AS `currency_code`,
        a.exchange_rate                            AS `exchange_rate`,
        a.net_amount                               AS `net_amount`,
        a.discount                                 AS `discount`,
        a.tax                                      AS `tax`,
        a.status                                   AS `status`
      FROM
        `stock_in_header` AS a
      WHERE
        a.id=\"$id\"
    ")[0];

    $stockInModels = query("
      SELECT
        b.name                                  AS `brand`,
        a.model_no                              AS `model_no`,
        a.price                                 AS `price`,
        a.qty                                   AS `qty`,
        a.qty * a.price                         AS `subtotal`
      FROM
        `stock_in_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `stock_in_header` AS c
      ON a.stock_in_no=c.stock_in_no
      WHERE
        c.id=\"$id\"
      ORDER BY
        a.stock_in_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($stockInNo) &&
    assigned($stockInDate) &&
    assigned($transactionCode) &&
    assigned($warehouseCode) &&
    assigned($tax) &&
    assigned($status)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $stockInHeader = array(
      "stock_in_no"         => $stockInNo,
      "transaction_code"    => $transactionCode,
      "warehouse_code"      => $warehouseCode,
      "date"                => $stockInDate,
      "creditor_code"       => $creditorCode,
      "currency"            => $currencyCode,
      "exchange_rate"       => $exchangeRate,
      "net_amount"          => $netAmount,
      "discount"            => $discount,
      "tax"                 => $tax,
      "status"              => $status
    );

    $stockInModels = array();

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($stockInModels, array(
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i],
        "subtotal"          => $prices[$i] * $qtys[$i]
      ));
    }
  }

  if (isset($stockInHeader)) {
    $creditor = query("SELECT english_name AS name FROM `creditor` WHERE code=\"" . $stockInHeader["creditor_code"] . "\"")[0];
    $warehouse = query("SELECT name FROM `warehouse` WHERE code=\"" . $stockInHeader["warehouse_code"] . "\"")[0];

    $stockInHeader["transaction_type"] = $stockInHeader["transaction_code"] . " - " . $TRANSACTION_CODES[$stockInHeader["transaction_code"]];
    $stockInHeader["warehouse"] = $stockInHeader["warehouse_code"] . " - " . (isset($warehouse) ? $warehouse["name"] : "Unknown");
    $stockInHeader["creditor"] = $stockInHeader["creditor_code"] . " - " . (isset($creditor) ? $creditor["name"] : "Unknown");
    $stockInHeader["currency"] = $stockInHeader["currency_code"] . " @ " . $stockInHeader["exchange_rate"];
  }

  $miscellaneous = $stockInHeader["transaction_code"] != "R1" && $stockInHeader["transaction_code"] != "R3";
?>
