<?php
  $debtors = query("SELECT code, english_name AS name FROM debtor");
  $currencyList = query("SELECT code, rate FROM currency");
  $currencies = array();

  foreach ($currencyList as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  $brands = query("SELECT code, name FROM brand");
  $models = query("
    SELECT
      a.brand_code                AS `brand_code`,
      a.model_no                  AS `model_no`,
      a.retail_normal             AS `normal_price`,
      a.retail_special            AS `special_price`,
      a.cost_average              AS `cost_average`,
      IFNULL(b.qty_on_hand, 0)    AS `qty_on_hand`,
      IFNULL(c.qty_on_order, 0)   AS `qty_on_order`
    FROM
      `model` AS a
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS qty_on_hand
      FROM
        `stock`
      GROUP BY
        model_no, brand_code) AS b
    ON a.model_no=b.model_no AND a.brand_code=b.brand_code
    LEFT JOIN
      (SELECT
        m.model_no, m.brand_code, SUM(GREATEST(qty_outstanding, 0)) AS qty_on_order
      FROM
        `po_model` AS m
      LEFT JOIN
        `po_header` AS h
      ON m.po_no=h.po_no
      WHERE
        h.status='POSTED'
      GROUP BY
        model_no, m.brand_code) AS c
    ON a.model_no=c.model_no AND a.brand_code=c.brand_code
  ");

  $so_no = $_GET["so_no"];
  $so_date = $_GET["so_date"];
  $debtor_code = $_GET["debtor_code"];
  $currency_code = $_GET["currency_code"];
  $exchange_rate = $_GET["exchange_rate"];
  $discount = $_GET["discount"];
  $tax = $_GET["tax"];
  $priceStandard = assigned($_GET["price_standard"]) ? $_GET["price_standard"] : "normal_price";
  $remarks = assigned($_GET["remarks"]) || "";

  $brand_codes = $_GET["brand_code"];
  $model_nos = $_GET["model_no"];
  $prices = $_GET["price"];
  $qtys = $_GET["qty"];

  $status = $_GET["status"];

  /* If an order number is given, attempt to retrieve an existing sales order. */
  if (assigned($so_no)) {
    $so_header = query("SELECT *, DATE_FORMAT(so_date, '%Y-%m-%d') AS `so_date` FROM `so_header` WHERE so_no=\"$so_no\"");

    /* If a complete form is given, submit the sales order. */
    if (assigned($so_date) && assigned($debtor_code) && assigned($currency_code) && assigned($exchange_rate) && assigned($discount) && assigned($tax) && assigned($status)) {

      /* Upon submission, if the sales number already exists, update the existing sales order header.
         Also delete all existing sales models, new ones will be inserted afterwards. */
      if (count($so_header) > 0) {
        query("
          UPDATE
            `so_header`
          SET
            so_date=\"$so_date\",
            debtor_code=\"$debtor_code\",
            currency_code=\"$currency_code\",
            exchange_rate=\"$exchange_rate\",
            discount=\"$discount\",
            tax=\"$tax\",
            remarks=\"$remarks\",
            status=\"$status\"
          WHERE
            so_no=\"$so_no\"
        ");

        query("DELETE FROM so_model WHERE so_no=\"$so_no\"");
      }

      /* If the sales number does not exist create as a new sales order. */
      else {
        query("
          INSERT INTO
            `so_header`
              (so_no, so_date, debtor_code, currency_code, exchange_rate, discount, tax, status, remarks)
            VALUES
              (\"$so_no\", \"$so_date\", \"$debtor_code\", \"$currency_code\", \"$exchange_rate\", \"$discount\", \"$tax\", \"$status\", \"$remarks\")
        ");
      }

      /* Create the sales order models as they are given. */
      if (assigned($brand_codes) && assigned($model_nos) && assigned($prices) && assigned($qtys) && count($brand_codes) > 0 && count($model_nos) > 0 && count($prices) > 0 && count($qtys) > 0) {
        $values = array();

        for ($i = 0; $i < count($brand_codes); $i++) {
          $brand_code = $brand_codes[$i];
          $model_no = $model_nos[$i];
          $price = $prices[$i];
          $qty = $qtys[$i];
          array_push($values, "(\"$so_no\", \"$i\", \"$brand_code\", \"$model_no\", \"$price\", \"$qty\", \"$qty\")");
        }

        query("
          INSERT INTO
            `so_model`
              (so_no, so_index, brand_code, model_no, price, qty, qty_outstanding)
            VALUES
        " . join(", ", $values));
      }
    }

    /* If the sales order was not filled-in completely and the order number does exists,
       retrieve it from the database and auto-fill in the entry form with the retrieved data. */
    else if (count($so_header) > 0) {
      $so_date = $so_header[0]["so_date"];
      $debtor_code = $so_header[0]["debtor_code"];
      $currency_code = $so_header[0]["currency_code"];
      $exchange_rate = $so_header[0]["exchange_rate"];
      $discount = $so_header[0]["discount"];
      $tax = $so_header[0]["tax"];
      $remarks = $so_header[0]["remarks"];
      $status = $so_header[0]["status"];

      $so_models = query("SELECT so_index, brand_code, model_no, price, qty FROM `so_model` WHERE so_no=\"$so_no\" ORDER BY so_index ASC");

      $brand_codes = array_map(function ($s) { return $s["brand_code"]; }, $so_models);
      $model_nos = array_map(function ($s) { return $s["model_no"]; }, $so_models);
      $prices = array_map(function ($s) { return $s["price"]; }, $so_models);
      $qtys = array_map(function ($s) { return $s["qty"]; }, $so_models);
    }

    /* If the sales order was not filled-in completely and the order number does not exists,
       display not found. */
    else {
      unset($so_no);
    }
  }

  /* If no data is given, treat it as a new entry form. */
  else {
    $so_no = "SO" . date("YmdHi");
    $so_date = date("Y-m-d");
    $currency_code = COMPANY_CURRENCY;
    $exchange_rate = $currencies[COMPANY_CURRENCY];
    $discount = 0;
    $tax = COMPANY_TAX;
  }

  $so_models = array();

  for ($i = 0; $i < count($model_nos); $i++) {
    array_push($so_models, array(
      "model_no" => $model_nos[$i],
      "brand_code" => $brand_codes[$i],
      "price" => $prices[$i],
      "qty" => $qtys[$i]
    ));
  }
?>
