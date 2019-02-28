<?php
  $debtorCode = $_POST["debtor_code"];
  $debtorName = $_POST["debtor_name"];
  $inCharge = $_POST["in_charge"];
  $showPrice = $_POST["show_price"] == "on" ? true : false;
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];
  $prices = $_POST["price"];
  $qtysAllotted = $_POST["qty_allotted"];
  $remarks = $_POST["remarks"];

  $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"$debtorCode\"")[0];
  $debtorName = assigned($debtorName) ? $debtorName : $debtor["name"];
  $client = "$debtorName ($debtorCode)";
  $currency = "$currencyCode @ $exchangeRate";

  $items = array();

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

  $brands = query("SELECT code, name FROM `brand`");
  foreach ($brands as $brand) {
    $brands[$brand["code"]] = $brand["name"];
  }

  $date = date("d-m-Y   H:i:s");

  for ($i = 0; $i < count($brandCodes); $i++) {
    $model = $models[$brandCodes[$i]][$modelNos[$i]];

    array_push($items, array(
      "brand"                 => $brands[$brandCodes[$i]],
      "model_no"              => $modelNos[$i],
      "qty"                   => $qtys[$i],
      "qty_on_hand"           => $model["qty_on_hand"],
      "qty_on_hand_reserve"   => $model["qty_on_hand_reserve"],
      "qty_available"         => $model["qty_on_hand"] - $model["qty_on_hand_reserve"],
      "qty_allotted"          => $qtysAllotted[$i],
      "qty_incoming"          => $model["qty_incoming"],
      "qty_incoming_reserve"  => $model["qty_incoming_reserve"]
    ));
  }
?>
