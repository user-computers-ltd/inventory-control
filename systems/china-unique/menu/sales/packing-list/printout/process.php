<?php
  $soNo = assigned($_GET["so_no"]) ? $_GET["so_no"] : $_POST["so_no"];
  $soDate = $_POST["so_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $remarks = assigned($_POST["remarks"]) ? $_POST["remarks"] : "";
  $status = assigned($_POST["status"]) ? $_POST["status"] : "DRAFT";

  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  $soHeader = null;
  $soModels = array();

  /* Only populate the data if an order number is given. */
  if (assigned($soNo)) {

    /* If a complete form is given, follow all the data to printout. */
    if (assigned($soDate) && assigned($debtorCode) && assigned($currencyCode) && assigned($exchangeRate) && assigned($discount) && assigned($tax)) {

      $debtors = query("SELECT english_name AS name FROM `debtor` WHERE code=\"$debtorCode\"");

      $soHeader = array(
        "so_no"     => $soNo,
        "date"      => $soDate,
        "customer"  => "$debtorCode - " . (count($debtors) > 0 ? $debtors[0]["name"] : "Unknown"),
        "currency"  => "$currencyCode @ $exchangeRate",
        "discount"  => $discount,
        "tax"       => $tax,
        "status"    => $status
      );

      /* If a model list is given, follow all the data to printout. */
      if (assigned($brandCodes) && assigned($modelNos) && assigned($prices) && assigned($qtys) && count($brandCodes) > 0 && count($modelNos) > 0 && count($prices) > 0 && count($qtys) > 0) {
        $soModels = array();

        $results = query("SELECT code, name FROM `brand`");
        $brands = array();

        foreach ($results as $brand) {
          $brands[$brand["code"]] = $brand["name"];
        }

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
    }

    /* If the sales order was not filled-in completely, attempt to retrieve an existing sales order. */
    else {
      $soHeader = query("
        SELECT
          a.so_no                                                           AS `so_no`,
          DATE_FORMAT(a.so_date, '%d-%m-%Y')                                AS `date`,
          CONCAT(a.debtor_code, ' - ', IFNULL(b.english_name, 'Unknown'))   AS `customer`,
          CONCAT(a.currency_code, ' @ ', a.exchange_rate)                   AS `currency`,
          a.discount                                                        AS `discount`,
          a.tax                                                             AS `tax`,
          a.status                                                          AS `status`,
        FROM
          `so_header` AS a
        LEFT JOIN
          `debtor` AS b
        ON a.debtor_code=b.code
        WHERE
          a.so_no=\"$soNo\"
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
        WHERE
          a.so_no=\"$soNo\"
        ORDER BY
          a.so_index ASC
      ");
    }
  }
?>
