<?php
  $ids = $_GET["id"];
  $enquiryNo = $_POST["enquiry_no"];
  $enquiryDate = $_POST["enquiry_date"];
  $debtorCode = $_POST["debtor_code"];
  $debtorName = $_POST["debtor_name"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $inCharge = $_POST["in_charge"];
  $showPrice = $_POST["show_price"] == "on" ? "TRUE" : "FALSE";
  $discount = $_POST["discount"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];
  $prices = $_POST["price"];
  $qtysAllotted = $_POST["qty_allotted"];
  $remarks = $_POST["remarks"];

  $enquiryHeaders = array();
  $enquiryModelList = array();
  $enquiryModels = array();

  /* If an id is given, retrieve from an existing sales enquiry. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "a.id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $enquiryHeaders = query("
      SELECT
        a.id                                                      AS `id`,
        a.enquiry_no                                              AS `enquiry_no`,
        DATE_FORMAT(a.enquiry_date, '%d-%m-%Y')                   AS `date`,
        a.debtor_code                                             AS `debtor_code`,
        IF(a.debtor_name=\"\", b.english_name, a.debtor_name)     AS `debtor_name`,
        a.currency_code                                           AS `currency_code`,
        a.exchange_rate                                           AS `exchange_rate`,
        a.in_charge                                               AS `in_charge`,
        a.show_price                                              AS `show_price`,
        a.discount                                                AS `discount`,
        a.remarks                                                 AS `remarks`
      FROM
        `enquiry_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      WHERE
        $headerWhereClause
    ");

    $enquiryModelList = query("
      SELECT
        a.enquiry_no                              AS `enquiry_no`,
        b.name                                    AS `brand`,
        a.model_no                                AS `model_no`,
        a.price                                   AS `price`,
        a.qty                                     AS `qty`,
        a.qty_allotted                            AS `qty_allotted`
      FROM
        `enquiry_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `enquiry_header` AS c
      ON a.enquiry_no=c.enquiry_no
      WHERE
        $modelWhereClause
      ORDER BY
        a.enquiry_no ASC,
        a.enquiry_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($enquiryNo) &&
    assigned($enquiryDate) &&
    assigned($debtorCode) &&
    assigned($inCharge) &&
    assigned($showPrice) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($qtys) &&
    assigned($qtysAllotted)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $enquiryDate = new DateTime($enquiryDate);
    $enquiryDate = $enquiryDate->format("d-m-Y");

    $enquiryHeaders = array(array(
      "enquiry_no"    => $enquiryNo,
      "date"          => $enquiryDate,
      "debtor_code"   => $debtorCode,
      "debtor_name"   => $debtorName,
      "currency_code" => $currencyCode,
      "exchange_rate" => $exchangeRate,
      "in_charge"     => $inCharge,
      "show_price"    => $showPrice,
      "discount"      => $discount,
      "remarks"       => $remarks
    ));

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($enquiryModelList, array(
        "enquiry_no"        => $enquiryNo,
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "price"             => $prices[$i],
        "qty"               => $qtys[$i],
        "qty_allotted"      => $qtysAllotted[$i]
      ));
    }
  }

  if (count($enquiryHeaders) > 0) {
    foreach ($enquiryHeaders as &$enquiryHeader) {
      $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"" . $enquiryHeader["debtor_code"] . "\"")[0];
      $enquiryHeader["client"] =
        (assigned($enquiryHeader["debtor_name"]) ? $enquiryHeader["debtor_name"] : isset($debtor) ? $debtor["name"] : "Unknown");
      $enquiryHeader["currency"] = $enquiryHeader["currency_code"] . " @ " . $enquiryHeader["exchange_rate"];
    }
  }

  if (count($enquiryModelList) > 0) {
    foreach ($enquiryModelList as $enquiryModel) {
      $enquiryNo = $enquiryModel["enquiry_no"];

      $arrayPointer = &$enquiryModels;

      if (!isset($arrayPointer[$enquiryNo])) {
        $arrayPointer[$enquiryNo] = array();
      }
      $arrayPointer = &$arrayPointer[$enquiryNo];

      array_push($arrayPointer, $enquiryModel);
    }
  }
?>
