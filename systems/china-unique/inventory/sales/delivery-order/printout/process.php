<?php
  $ids = $_GET["id"];
  $editId = $_POST["id"];
  $doNo = $_POST["do_no"];
  $doDate = $_POST["do_date"];
  $debtorCode = $_POST["debtor_code"];
  $address = $_POST["address"];
  $contact = $_POST["contact"];
  $tel = $_POST["tel"];
  $warehouseCode = $_POST["warehouse_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $remarks = $_POST["remarks"];
  $iaNos = $_POST["ia_no"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];
  $prices = $_POST["price"];
  $hidePrice = $_GET["show_price"] === "off";

  $doHeaders = array();
  $doModelList = array();
  $doModels = array();

  /* Only populate the data if an id is given. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "a.id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $doHeaders = query("
      SELECT
        a.do_no                                           AS `do_no`,
        DATE_FORMAT(a.do_date, '%d-%m-%Y')                AS `date`,
        a.debtor_code                                     AS `debtor_code`,
        IFNULL(b.english_name, 'Unknown')                 AS `debtor_name`,
        a.address                                         AS `address`,
        a.contact                                         AS `contact`,
        a.tel                                             AS `tel`,
        c.name                                            AS `warehouse`,
        CONCAT(a.currency_code, ' @ ', a.exchange_rate)   AS `currency`,
        a.discount                                        AS `discount`,
        a.tax                                             AS `tax`,
        a.remarks                                         AS `remarks`
      FROM
        `sdo_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      LEFT JOIN
        `warehouse` AS c
      ON a.warehouse_code=c.code
      WHERE
        $headerWhereClause
    ");

    $doModelList = query("
      SELECT
        a.do_no           AS `do_no`,
        a.so_no           AS `so_no`,
        b.name            AS `brand`,
        a.model_no        AS `model_no`,
        a.price           AS `price`,
        SUM(a.qty)        AS `qty`,
        d.occurrence      AS `occurrence`
      FROM
        `sdo_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `sdo_header` AS c
      ON a.do_no=c.do_no
      LEFT JOIN
        `so_model` AS d
      ON a.so_no=d.so_no AND a.brand_code=d.brand_code AND a.model_no=d.model_no
      WHERE
        $modelWhereClause
      GROUP BY
        a.do_no, a.brand_code, a.model_no, a.so_no, a.price, d.occurrence
      ORDER BY
        a.do_no ASC,
        a.brand_code ASC,
        a.model_no ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($doNo) &&
    assigned($doDate) &&
    assigned($debtorCode) &&
    assigned($address) &&
    assigned($contact) &&
    assigned($tel) &&
    assigned($warehouseCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($tax) &&
    assigned($iaNos) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($soNos) &&
    assigned($qtys) &&
    assigned($prices)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $warehouses = query("SELECT code, name FROM `warehouse`");
    foreach ($warehouses as $warehouse) {
      $warehouses[$warehouse["code"]] = $warehouse["name"];
    }

    $debtorName = query("SELECT english_name AS `name` FROM `debtor` WHERE code=\"$debtorCode\"")[0]["name"];

    $doDate = new DateTime($doDate);
    $doDate = $doDate->format("d-m-Y");

    $doHeaders = array(array(
      "do_no"           => $doNo,
      "date"            => $doDate,
      "debtor_code"     => $debtorCode,
      "debtor_name"     => $debtorName,
      "address"         => $address,
      "contact"         => $contact,
      "tel"             => $tel,
      "warehouse"       => $warehouses[$warehouseCode],
      "currency_code"   => $currencyCode,
      "exchange_rate"   => $exchangeRate,
      "discount"        => $discount,
      "tax"             => $tax,
      "remarks"         => $remarks
    ));

    $indexMap = array();

    for ($i = 0; $i < count($iaNos); $i++) {
      $key = $soNos[$i] . $brandCodes[$i] . $modelNos[$i];

      if (!isset($indexMap[$key])) {
        $indexMap[$key] = $i;

        array_push($doModelList, array(
          "do_no"             => $doNo,
          "ia_no"             => $iaNos[$i],
          "so_no"             => $soNos[$i],
          "brand"             => $brands[$brandCodes[$i]],
          "model_no"          => $modelNos[$i],
          "price"             => $prices[$i],
          "qty"               => $qtys[$i],
          "occurrence"        => $qtys[$i]
        ));
      } else {
        $doModelList[$indexMap[$key]]["qty"] += $qtys[$i];
      }
    }
  }

  if (count($doModelList) > 0) {
    foreach ($doModelList as $doModel) {
      $doNo = $doModel["do_no"];

      $arrayPointer = &$doModels;

      if (!isset($arrayPointer[$doNo])) {
        $arrayPointer[$doNo] = array();
      }
      $arrayPointer = &$arrayPointer[$doNo];

      array_push($arrayPointer, $doModel);
    }
  }
?>
