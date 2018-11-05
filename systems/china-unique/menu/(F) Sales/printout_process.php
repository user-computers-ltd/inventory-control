<?php
  $so_no = $_GET["so_no"];
  $so_date = $_GET["so_date"];
  $debtor_code = $_GET["debtor_code"];
  $currency_code = $_GET["currency_code"];
  $exchange_rate = $_GET["exchange_rate"];
  $discount = $_GET["discount"];
  $tax = $_GET["tax"];
  $remarks = assigned($_GET["remarks"]) || "";

  $brand_codes = $_GET["brand_code"];
  $model_nos = $_GET["model_no"];
  $prices = $_GET["price"];
  $qtys = $_GET["qty"];

  $so_header = null;
  $so_models = array();

  /* Only populate the data if an order number is given. */
  if (assigned($so_no)) {

    /* If a complete form is given, follow all the data to printout. */
    if (assigned($so_date) && assigned($debtor_code) && assigned($currency_code) && assigned($exchange_rate) && assigned($discount) && assigned($tax)) {

      $debtors = query("SELECT english_name AS name FROM debtor WHERE code=\"$debtor_code\"");

      $so_header = array(
        "Order No." => $so_no,
        "Date"      => $so_date,
        "Customer"  => "$debtor_code - " . (count($debtors) > 0 ? $debtors[0]["name"] : "Unknown"),
        "Currency"  => "$currency_code @ $exchange_rate",
        "Discount"  => $discount,
        "Tax"       => $tax,
      );

      /* If a model list is given, follow all the data to printout. */
      if (assigned($brand_codes) && assigned($model_nos) && assigned($prices) && assigned($qtys) && count($brand_codes) > 0 && count($model_nos) > 0 && count($prices) > 0 && count($qtys) > 0) {
        $so_models = array();

        for ($i = 0; $i < count($brand_codes); $i++) {
          array_push($so_models, array(
            "Brand"         => $brand_codes[$i],
            "Model No."     => $model_nos[$i],
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
      $so_header = query("
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
          a.so_no=\"$so_no\"
      ")[0];

      $so_models = query("
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
          a.so_no=\"$so_no\"
      ");
    }
  }
?>
