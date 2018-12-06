<?php
  $id = $_GET["id"];
  $soNo = $_POST["so_no"];
  $soDate = $_POST["so_date"];
  $debtorCode = $_POST["debtor_code"];
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

  $soHeader = null;
  $soModels = array();

  /* If an id is given, retrieve from an existing sales order. */
  if (assigned($id)) {
    $soHeader = query("
      SELECT
        a.so_no                                                           AS `so_no`,
        DATE_FORMAT(a.so_date, '%d-%m-%Y')                                AS `date`,
        a.debtor_code                                                     AS `debtor_code`,
        a.currency_code                                                   AS `currency_code`,
        a.exchange_rate                                                   AS `exchange_rate`,
        a.discount                                                        AS `discount`,
        a.tax                                                             AS `tax`,
        a.status                                                          AS `status`
      FROM
        `so_header` AS a
      WHERE
        a.id=\"$id\"
    ")[0];

    $soModels = query("
      SELECT
        b.name                                  AS `brand`,
        a.model_no                              AS `model_no`,
        a.price                                 AS `price`,
        a.qty                                   AS `qty`,
        a.qty * a.price                         AS `subtotal`
      FROM
        `so_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `so_header` AS c
      ON a.so_no=c.so_no
      WHERE
        c.id=\"$id\"
      ORDER BY
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
    assigned($status)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $soHeader = array(
      "so_no"         => $soNo,
      "date"          => $soDate,
      "debtor_code"   => $debtorCode,
      "currency_code" => $currencyCode,
      "exchange_rate" => $exchangeRate,
      "discount"      => $discount,
      "tax"           => $tax,
      "status"        => $status
    );

    $soModels = array();

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($soModels, array(
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i],
        "subtotal"          => $prices[$i] * $qtys[$i]
      ));
    }
  }

  if (isset($soHeader)) {
    $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"$debtorCode\"")[0];

    $soHeader["customer"] = $soHeader["debtor_code"] . " - " . (isset($debtor) ? $debtor["name"] : "Unknown");
    $soHeader["currency"] = $soHeader["currency_code"] . " @ " . $soHeader["exchange_rate"];
  }
?>
