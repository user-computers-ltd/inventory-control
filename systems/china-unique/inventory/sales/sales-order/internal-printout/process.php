<?php
  $ids = $_GET["id"];
  $soNo = $_POST["so_no"];
  $soDate = $_POST["so_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $priority = $_POST["priority"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  $soHeaders = array();
  $soModelList = array();
  $soModels = array();

  /* If an id is given, retrieve from an existing sales order. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $soHeaders = query("
      SELECT
        so_no                               AS `so_no`,
        DATE_FORMAT(so_date, '%d-%m-%Y')    AS `date`,
        debtor_code                         AS `debtor_code`,
        currency_code                       AS `currency_code`,
        exchange_rate                       AS `exchange_rate`,
        discount                            AS `discount`,
        tax                                 AS `tax`,
        priority                            AS `priority`,
        remarks                             AS `remarks`,
        status                              AS `status`
      FROM
        `so_header`
      WHERE
        $headerWhereClause
    ");

    $soModelList = query("
      SELECT
        a.so_no                             AS `so_no`,
        b.name                              AS `brand`,
        a.model_no                          AS `model_no`,
        a.price                             AS `price`,
        a.qty                               AS `qty`,
        a.qty_outstanding                   AS `qty_outstanding`,
        a.occurrence                        AS `occurrence`
      FROM
        `so_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `so_header` AS c
      ON a.so_no=c.so_no
      WHERE
        $modelWhereClause
      ORDER BY
        a.so_no ASC,
        a.so_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($soNo) &&
    assigned($soDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($tax) &&
    assigned($priority) &&
    assigned($status)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $soDate = new DateTime($soDate);
    $soDate = $soDate->format("d-m-Y");

    $soHeaders = array(array(
      "so_no"         => $soNo,
      "date"          => $soDate,
      "debtor_code"   => $debtorCode,
      "currency_code" => $currencyCode,
      "exchange_rate" => $exchangeRate,
      "discount"      => $discount,
      "tax"           => $tax,
      "priority"      => $priority,
      "remarks"       => $remarks,
      "status"        => $status
    ));

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($soModelList, array(
        "so_no"             => $soNo,
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i],
        "qty_outstanding"   => $qtys[$i],
        "occurrence"        => $qtys[$i]
      ));
    }
  }

  if (count($soHeaders) > 0) {
    foreach ($soHeaders as &$soHeader) {
      $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"" . $soHeader["debtor_code"] . "\"")[0];
      $soHeader["client"] = $soHeader["debtor_code"] . " - " . (isset($debtor) ? $debtor["name"] : "Unknown");
      $soHeader["currency"] = $soHeader["currency_code"] . " @ " . $soHeader["exchange_rate"];
    }
  }

  if (count($soModelList) > 0) {
    foreach ($soModelList as $soModel) {
      $soNo = $soModel["so_no"];

      $arrayPointer = &$soModels;

      if (!isset($arrayPointer[$soNo])) {
        $arrayPointer[$soNo] = array();
      }
      $arrayPointer = &$arrayPointer[$soNo];

      array_push($arrayPointer, $soModel);
    }
  }
?>
