<?php
  $soNo = $_GET["so_no"];
  $soDate = $_GET["so_date"];
  $debtorCode = $_GET["debtor_code"];
  $currencyCode = $_GET["currency_code"];
  $exchangeRate = $_GET["exchange_rate"];
  $discount = $_GET["discount"];
  $tax = $_GET["tax"];
  $remarks = assigned($_GET["remarks"]) || "";

  $brandCodes = $_GET["brand_code"];
  $modelNos = $_GET["model_no"];
  $prices = $_GET["price"];
  $qtys = $_GET["qty"];

  $soHeader = null;
  $soModels = array();

  /* Only populate the data if an order number is given. */
  if (assigned($soNo)) {

    /* If a complete form is given, follow all the data to printout. */
    if (assigned($soDate) && assigned($debtorCode) && assigned($currencyCode) && assigned($exchangeRate) && assigned($discount) && assigned($tax)) {

      $debtors = query("SELECT english_name AS name FROM `debtor` WHERE code=\"$debtorCode\"");

      $soHeader = array(
        "Order No." => $soNo,
        "Date"      => $soDate,
        "Customer"  => "$debtorCode - " . (count($debtors) > 0 ? $debtors[0]["name"] : "Unknown"),
        "Currency"  => "$currencyCode @ $exchangeRate",
        "Discount"  => $discount,
        "Tax"       => $tax,
      );

      /* If a model list is given, follow all the data to printout. */
      if (assigned($brandCodes) && assigned($modelNos) && assigned($prices) && assigned($qtys) && count($brandCodes) > 0 && count($modelNos) > 0 && count($prices) > 0 && count($qtys) > 0) {
        $soModels = array();

        for ($i = 0; $i < count($brandCodes); $i++) {
          array_push($soModels, array(
            "Brand"         => $brandCodes[$i],
            "Model No."     => $modelNos[$i],
            "Selling Price" => $prices[$i],
            "Quantity"      => $qtys[$i],
            "Outstanding"   => $qtys[$i],
            "Sub Total"     => $prices[$i] * $qtys[$i]
          ));
        }
      }
    }

    /* If the sales order was not filled-in completely, attempt to retrieve an existing sales order. */
    else {
      $soHeader = query("
        SELECT
          a.so_no                                                           AS `Order No.`,
          DATE_FORMAT(a.so_date, '%d-%m-%Y')                                AS `Date`,
          CONCAT(a.debtor_code, ' - ', IFNULL(b.english_name, 'Unknown'))   AS `Customer`,
          CONCAT(a.currency_code, ' @ ', a.exchange_rate)                   AS `Currency`,
          a.discount                                                        AS `Discount`,
          a.tax                                                             AS `Tax`
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
          b.name                                  AS `Brand`,
          a.model_no                              AS `Model No.`,
          a.price                                 AS `Selling Price`,
          a.qty                                   AS `Quantity`,
          a.qty_outstanding                       AS `Outstanding`,
          a.qty * a.price                         AS `Sub Total`
        FROM
          `so_model` AS a
        LEFT JOIN
          `brand` AS b
          ON a.brand_code=b.code
        WHERE
          a.so_no=\"$soNo\"
      ");
    }
  }
?>
