<?php
  $ids = $_GET["id"];
  $poNo = $_POST["po_no"];
  $poDate = $_POST["po_date"];
  $creditorCode = $_POST["creditor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  $poHeaders = array();
  $poModelList = array();
  $poModels = array();

  /* If an id is given, retrieve from an existing sales order. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $poHeaders = query("
      SELECT
        po_no                               AS `po_no`,
        DATE_FORMAT(po_date, '%d-%m-%Y')    AS `date`,
        creditor_code                       AS `creditor_code`,
        currency_code                       AS `currency_code`,
        exchange_rate                       AS `exchange_rate`,
        discount                            AS `discount`,
        tax                                 AS `tax`,
        remarks                             AS `remarks`,
        status                              AS `status`
      FROM
        `po_header`
      WHERE
        $headerWhereClause
    ");

    $poModelList = query("
      SELECT
        a.po_no                             AS `po_no`,
        b.name                              AS `brand`,
        a.model_no                          AS `model_no`,
        a.price                             AS `price`,
        a.qty                               AS `qty`
      FROM
        `po_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `po_header` AS c
      ON a.po_no=c.po_no
      WHERE
        $modelWhereClause
      ORDER BY
        a.po_no ASC,
        a.po_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($poNo) &&
    assigned($poDate) &&
    assigned($creditorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($tax) &&
    assigned($status)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $poDate = new DateTime($poDate);
    $poDate = $poDate->format("d-m-Y");

    $poHeaders = array(array(
      "po_no"         => $poNo,
      "date"          => $poDate,
      "creditor_code" => $creditorCode,
      "currency_code" => $currencyCode,
      "exchange_rate" => $exchangeRate,
      "discount"      => $discount,
      "tax"           => $tax,
      "remarks"       => $remarks,
      "status"        => $status
    ));

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($poModelList, array(
        "po_no"             => $poNo,
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i]
      ));
    }
  }

  if (count($poHeaders) > 0) {
    foreach ($poHeaders as &$poHeader) {
      $creditor = query("SELECT english_name AS name FROM `creditor` WHERE code=\"" . $poHeader["creditor_code"] . "\"")[0];
      $poHeader["supplier"] = $poHeader["creditor_code"] . " - " . (isset($creditor) ? $creditor["name"] : "Unknown");
      $poHeader["currency"] = $poHeader["currency_code"] . " @ " . $poHeader["exchange_rate"];
    }
  }

  if (count($poModelList) > 0) {
    foreach ($poModelList as $poModel) {
      $poNo = $poModel["po_no"];

      $arrayPointer = &$poModels;

      if (!isset($arrayPointer[$poNo])) {
        $arrayPointer[$poNo] = array();
      }
      $arrayPointer = &$arrayPointer[$poNo];

      array_push($arrayPointer, $poModel);
    }
  }
?>
