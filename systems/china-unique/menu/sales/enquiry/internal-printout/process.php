<?php
  $ids = $_GET["id"];
  $enquiryNo = $_POST["enquiry_no"];
  $enquiryDate = $_POST["enquiry_date"];
  $debtorCode = $_POST["debtor_code"];
  $debtorName = $_POST["debtor_name"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $inCharge = $_POST["in_charge"];
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
        a.brand_code                              AS `brand_code`,
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
      "discount"      => $discount,
      "remarks"       => $remarks
    ));

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($enquiryModelList, array(
        "enquiry_no"        => $enquiryNo,
        "brand"             => $brands[$brandCodes[$i]],
        "brand_code"        => $brandCodes[$i],
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
      $enquiryHeader["client"] = $enquiryHeader["debtor_code"] . " (" .
        (assigned($enquiryHeader["debtor_name"]) ? $enquiryHeader["debtor_name"] : isset($debtor) ? $debtor["name"] : "Unknown") . ")";
      $enquiryHeader["currency"] = $enquiryHeader["currency_code"] . " @ " . $enquiryHeader["exchange_rate"];
    }
  }

  if (count($enquiryModelList) > 0) {
    $models = query("
      SELECT
        a.brand_code                      AS `brand_code`,
        a.model_no                        AS `model_no`,
        IFNULL(b.qty_on_hand, 0)          AS `qty_on_hand`,
        IFNULL(c.qty_on_hand_reserve, 0)  AS `qty_on_hand_reserve`,
        IFNULL(d.qty_incoming, 0)         AS `qty_incoming`,
        IFNULL(e.qty_incoming_reserve, 0) AS `qty_incoming_reserve`
      FROM
        `model` AS a
      LEFT JOIN
        (SELECT
          model_no, brand_code, SUM(qty) AS `qty_on_hand`
        FROM
          `stock`
        GROUP BY
          brand_code, model_no) AS b
      ON a.brand_code=b.brand_code AND a.model_no=b.model_no
      LEFT JOIN
        (SELECT
          m.brand_code, m.model_no, SUM(m.qty) AS `qty_on_hand_reserve`
        FROM
          `sdo_model` AS m
        LEFT JOIN
          `sdo_header` AS h
        ON m.do_no=h.do_no
        WHERE
          m.ia_no=\"\" AND
          h.status=\"SAVED\"
        GROUP BY
          m.brand_code, m.model_no) AS c
      ON a.brand_code=c.brand_code AND a.model_no=c.model_no
      LEFT JOIN
        (SELECT
          model_no, brand_code, SUM(qty) AS `qty_incoming`
        FROM
          `ia_model` AS m
        LEFT JOIN
          `ia_header` AS h
        ON m.ia_no=h.ia_no
        WHERE
          h.status=\"DO\"
        GROUP BY
          m.brand_code, m.model_no) AS d
      ON a.brand_code=d.brand_code AND a.model_no=d.model_no
      LEFT JOIN
        (SELECT
          m.brand_code, m.model_no, SUM(m.qty) AS `qty_incoming_reserve`
        FROM
          `sdo_model` AS m
        LEFT JOIN
          `sdo_header` AS h
        ON m.do_no=h.do_no
        WHERE
          m.ia_no<>\"\" AND
          h.status=\"SAVED\"
        GROUP BY
          m.brand_code, m.model_no) AS e
      ON a.brand_code=e.brand_code AND a.model_no=e.model_no
      ORDER BY
        a.brand_code, a.model_no
    ");

    foreach ($models as $model) {
      $brandCode = $model["brand_code"];
      $modelNo = $model["model_no"];

      $arrayPointer = &$models;

      if (!isset($arrayPointer[$brandCode])) {
        $arrayPointer[$brandCode] = array();
      }
      $arrayPointer = &$arrayPointer[$brandCode];

      $arrayPointer[$modelNo] = $model;
    }

    foreach ($enquiryModelList as &$enquiryModel) {
      $model = $models[$enquiryModel["brand_code"]][$enquiryModel["model_no"]];
      $enquiryModel["qty_on_hand"] = $model["qty_on_hand"];
      $enquiryModel["qty_on_hand_reserve"] = $model["qty_on_hand_reserve"];
      $enquiryModel["qty_available"] = $model["qty_on_hand"] - $model["qty_on_hand_reserve"];
      $enquiryModel["qty_incoming"] = $model["qty_incoming"];
      $enquiryModel["qty_incoming_reserve"] = $model["qty_incoming_reserve"];
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
