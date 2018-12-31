<?php
  $ids = $_GET["id"];
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

  $stockInHeaders = array();
  $stockInModelList = array();
  $stockInModels = array();

  /* If an id is given, retrieve from an existing stock in voucher. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $stockInHeaders = query("
      SELECT
        stock_in_no                              AS `stock_in_no`,
        transaction_code                         AS `transaction_code`,
        warehouse_code                           AS `warehouse_code`,
        DATE_FORMAT(stock_in_date, '%d-%m-%Y')   AS `date`,
        creditor_code                            AS `creditor_code`,
        currency_code                            AS `currency_code`,
        exchange_rate                            AS `exchange_rate`,
        net_amount                               AS `net_amount`,
        discount                                 AS `discount`,
        tax                                      AS `tax`,
        remarks                                  AS `remarks`,
        status                                   AS `status`
      FROM
        `stock_in_header`
      WHERE
        $headerWhereClause
    ");

    $stockInModelList = query("
      SELECT
        a.stock_in_no                           AS `stock_in_no`,
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
        $modelWhereClause
      ORDER BY
        a.stock_in_no ASC,
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

    $stockInDate = new DateTime($stockInDate);
    $stockInDate = $stockInDate->format("d-m-Y");

    $stockInHeaders = array(array(
      "stock_in_no"         => $stockInNo,
      "transaction_code"    => $transactionCode,
      "warehouse_code"      => $warehouseCode,
      "date"                => $stockInDate,
      "creditor_code"       => $creditorCode,
      "currency_code"       => $currencyCode,
      "exchange_rate"       => $exchangeRate,
      "net_amount"          => $netAmount,
      "discount"            => $discount,
      "tax"                 => $tax,
      "remarks"             => $remarks,
      "status"              => $status
    ));

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($stockInModelList, array(
        "stock_in_no"       => $stockInNo,
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i],
        "subtotal"          => $prices[$i] * $qtys[$i]
      ));
    }
  }

  if (count($stockInHeaders) > 0) {
    foreach ($stockInHeaders as &$stockInHeader) {
      $creditor = query("SELECT english_name AS name FROM `creditor` WHERE code=\"" . $stockInHeader["creditor_code"] . "\"")[0];
      $warehouse = query("SELECT name FROM `warehouse` WHERE code=\"" . $stockInHeader["warehouse_code"] . "\"")[0];

      $stockInHeader["transaction_type"] = $stockInHeader["transaction_code"] . " - " . $TRANSACTION_CODES[$stockInHeader["transaction_code"]];
      $stockInHeader["warehouse"] = $stockInHeader["warehouse_code"] . " - " . (isset($warehouse) ? $warehouse["name"] : "Unknown");
      $stockInHeader["creditor"] = $stockInHeader["creditor_code"] . " - " . (isset($creditor) ? $creditor["name"] : "Unknown");
      $stockInHeader["currency"] = $stockInHeader["currency_code"] . " @ " . $stockInHeader["exchange_rate"];
      $stockInHeader["miscellaneous"] = $stockInHeader["transaction_code"] != "R1" && $stockInHeader["transaction_code"] != "R3";
    }
  }

  if (count($stockInModelList) > 0) {
    foreach ($stockInModelList as $stockInModel) {
      $stockInNo = $stockInModel["stock_in_no"];

      $arrayPointer = &$stockInModels;

      if (!isset($arrayPointer[$stockInNo])) {
        $arrayPointer[$stockInNo] = array();
      }
      $arrayPointer = &$arrayPointer[$stockInNo];

      array_push($arrayPointer, $stockInModel);
    }
  }
?>
