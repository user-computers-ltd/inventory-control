<?php
  $id = $_GET["id"];
  $enquiryNo = $_POST["enquiry_no"];
  $enquiryDate = $_POST["enquiry_date"];
  $debtorCode = $_POST["debtor_code"];
  $debtorName = $_POST["debtor_name"];
  $currencyCode = assigned($_POST["currency_code"]) ? $_POST["currency_code"] : COMPANY_CURRENCY;
  $exchangeRate = assigned($_POST["exchange_rate"]) ? $_POST["exchange_rate"] : 1;
  $inCharge = $_POST["in_charge"];
  $priceStandard = assigned($_POST["price_standard"]) ? $_POST["price_standard"] : "normal_price";
  $showPrice = assigned($_POST["show_price"]) && $_POST["show_price"] === "on" ? "TRUE" : "FALSE";
  $discount = assigned($_POST["discount"]) ? $_POST["discount"] : 0;
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];
  $qtysAllotted = $_POST["qty_allotted"];
  $remarks = $_POST["remarks"];

  /* If a form is submitted, update or insert the sales enquiry. */
  if (
    assigned($enquiryNo) &&
    assigned($enquiryDate) &&
    assigned($debtorCode) &&
    assigned($inCharge) &&
    assigned($priceStandard) &&
    assigned($showPrice) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($qtys) &&
    assigned($qtysAllotted)
  ) {
    $queries = array();

    /* If an id is given, remove the previous sales enquiry first. */
    if (assigned($id)) {
      array_push($queries, "DELETE a FROM `enquiry_model` AS a LEFT JOIN `enquiry_header` AS b ON a.enquiry_no=b.enquiry_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `enquiry_header` WHERE id=\"$id\"");
    }

    /* If the status is not delete, insert a new sales enquiry. */
    if ($status != "DELETED") {

      $values = array();

      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = $showPrice === "TRUE" ? $prices[$i] : -1;
        $qty = $qtys[$i];
        $qtyAllotted = $qtysAllotted[$i];

        array_push($values, "(\"$enquiryNo\", \"$i\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\", \"$qtyAllotted\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `enquiry_model`
              (enquiry_no, enquiry_index, brand_code, model_no, price, qty, qty_allotted)
            VALUES
        " . join(", ", $values));
      }

      array_push($queries, "
        INSERT INTO
          `enquiry_header`
            (enquiry_no, enquiry_date, in_charge, debtor_code, debtor_name, currency_code, exchange_rate, show_price, price_standard, discount, remarks)
          VALUES
            (
              \"$enquiryNo\",
              \"$enquiryDate\",
              \"$inCharge\",
              \"$debtorCode\",
              \"$debtorName\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$showPrice\",
              \"$priceStandard\",
              \"$discount\",
              \"$remarks\"
            )
      ");
    }

    execute($queries);

    header("Location: " . SALES_ENQUIRY_SAVED_URL);
  }

  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
  $brands = query("SELECT code, name FROM `brand`");
  $models = query("
    SELECT
      a.brand_code                      AS `brand_code`,
      a.model_no                        AS `model_no`,
      a.retail_normal                   AS `normal_price`,
      a.retail_special                  AS `special_price`,
      a.wholesale_special               AS `end_user_price`,
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
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  /* If an id is given, attempt to retrieve an existing sales order. */
  if (assigned($id)) {
    $headline = SALES_ENQUIRY_PRINTOUT_TITLE;

    $enquiryHeader = query("
      SELECT
        *,
        DATE_FORMAT(enquiry_date, '%Y-%m-%d') AS `enquiry_date`
      FROM
        `enquiry_header`
      WHERE id=\"$id\"
    ")[0];

    if (isset($enquiryHeader)) {
      $enquiryNo = $enquiryHeader["enquiry_no"];
      $enquiryDate = $enquiryHeader["enquiry_date"];
      $debtorCode = $enquiryHeader["debtor_code"];
      $debtorName = $enquiryHeader["debtor_name"];
      $currencyCode = $enquiryHeader["currency_code"];
      $exchangeRate = $enquiryHeader["exchange_rate"];
      $inCharge = $enquiryHeader["in_charge"];
      $priceStandard = $enquiryHeader["price_standard"];
      $showPrice = $enquiryHeader["show_price"] === "TRUE";
      $discount = $enquiryHeader["discount"];
      $remarks = $enquiryHeader["remarks"];
      $enquiryModels = query("
        SELECT
          brand_code              AS `brand_code`,
          model_no                AS `model_no`,
          price                   AS `price`,
          qty                     AS `qty`,
          qty_allotted            AS `qty_allotted`
        FROM
          `enquiry_model`
        WHERE
          enquiry_no=\"$enquiryNo\"
        ORDER BY
          enquiry_index ASC
      ");
    }
  }

  /* Else, initialize values for a new sales enquiry. */
  else {
    $headline = SALES_ENQUIRY_CREATE_TITLE;
    $enquiryNo = "ENQ" . date("YmdHis");
    $enquiryDate = date("Y-m-d");
    $debtorCode = assigned($debtorCode) ? $debtorCode : "1";
    $currencyCode = assigned($currencyCode) ? $currencyCode : COMPANY_CURRENCY;
    $exchangeRate = assigned($exchangeRate) ? $exchangeRate : $currencies[$currencyCode];
    $priceStandard = assigned($priceStandard) ? $priceStandard : "normal_price";
    $showPrice = assigned($showPrice) && $_POST["show_price"] === "on";
    $discount = assigned($discount) ? $discount : 0;
    $enquiryModels = array();

    if (assigned($brandCodes) && assigned($modelNos) && assigned($qtys) && assigned($qtysAllotted)) {
      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = assigned($prices) && assigned($prices[$i]) ? $prices[$i] : -1;
        $qty = $qtys[$i];
        $qtyAllotted = $qtysAllotted[$i];

        array_push($enquiryModels, array(
          "brand_code"  => $brandCode,
          "model_no"    => $modelNo,
          "price"       => $price,
          "qty"         => $qty,
          "qty_allotted"=> $qtyAllotted
        ));
      }
    }
  }
?>
