<?php
  $soNo = assigned($_GET["so_no"]) ? $_GET["so_no"] : $_POST["so_no"];
  $soDate = $_POST["so_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $status = $_POST["status"];
  $remarks = $_POST["remarks"];
  $priceStandard = assigned($_POST["price_standard"]) ? $_POST["price_standard"] : "normal_price";

  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  $debtors = query("SELECT code, english_name AS name FROM `debtor`");

  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();

  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  $brands = query("SELECT code, name FROM `brand`");
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

  /* If an order number is given, attempt to retrieve an existing sales order. */
  if (assigned($soNo)) {
    $headline = SALES_ORDER_DETAIL_TITLE;

    $soHeader = query("SELECT *, DATE_FORMAT(so_date, '%Y-%m-%d') AS `so_date` FROM `so_header` WHERE so_no=\"$soNo\"");

    /* If a complete form is given, submit the sales order. */
    if (assigned($soDate) && assigned($debtorCode) && assigned($currencyCode) && assigned($exchangeRate) && assigned($discount) && assigned($tax) && assigned($status)) {

      /* Upon submission, if the sales number already exists, update the existing sales order header.
         Also delete all existing sales models, new ones will be inserted afterwards. */
      if (count($soHeader) > 0) {
        query("
          UPDATE
            `so_header`
          SET
            so_date=\"$soDate\",
            debtor_code=\"$debtorCode\",
            currency_code=\"$currencyCode\",
            exchange_rate=\"$exchangeRate\",
            discount=\"$discount\",
            tax=\"$tax\",
            remarks=\"$remarks\",
            status=\"$status\"
          WHERE
            so_no=\"$soNo\"
        ");

        query("DELETE FROM `so_model` WHERE so_no=\"$soNo\"");
      }

      /* If the sales number does not exist create as a new sales order. */
      else {
        query("
          INSERT INTO
            `so_header`
              (so_no, so_date, debtor_code, currency_code, exchange_rate, discount, tax, status, remarks)
            VALUES
              (\"$soNo\", \"$soDate\", \"$debtorCode\", \"$currencyCode\", \"$exchangeRate\", \"$discount\", \"$tax\", \"$status\", \"$remarks\")
        ");
      }

      /* Create the sales order models as they are given. */
      if (assigned($brandCodes) && assigned($modelNos) && assigned($prices) && assigned($qtys) && count($brandCodes) > 0 && count($modelNos) > 0 && count($prices) > 0 && count($qtys) > 0) {
        $values = array();

        for ($i = 0; $i < count($brandCodes); $i++) {
          $brandCode = $brandCodes[$i];
          $modelNo = $modelNos[$i];
          $price = $prices[$i];
          $qty = $qtys[$i];

          array_push($values, "(\"$soNo\", \"$i\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\", \"$qty\")");
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
    else if (count($soHeader) > 0) {
      $soDate = $soHeader[0]["so_date"];
      $debtorCode = $soHeader[0]["debtor_code"];
      $currencyCode = $soHeader[0]["currency_code"];
      $exchangeRate = $soHeader[0]["exchange_rate"];
      $discount = $soHeader[0]["discount"];
      $tax = $soHeader[0]["tax"];
      $remarks = $soHeader[0]["remarks"];
      $status = $soHeader[0]["status"];

      $soModels = query("SELECT so_index, brand_code, model_no, price, qty FROM `so_model` WHERE so_no=\"$soNo\" ORDER BY so_index ASC");

      $brandCodes = array_map(function ($s) { return $s["brand_code"]; }, $soModels);
      $modelNos = array_map(function ($s) { return $s["model_no"]; }, $soModels);
      $prices = array_map(function ($s) { return $s["price"]; }, $soModels);
      $qtys = array_map(function ($s) { return $s["qty"]; }, $soModels);
    }

    /* If the sales order was not filled-in completely and the order number does not exists,
       display not found. */
    else {
      unset($soNo);
    }
  }

  /* If no data is given, treat it as a new entry form. */
  else {
    $headline = SALES_ORDER_CREATE_TITLE;

    $soNo = "SO" . date("YmdHis");
    $soDate = date("Y-m-d");
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[COMPANY_CURRENCY];
    $discount = 0;
    $tax = COMPANY_TAX;
    $status = "";
  }

  $soModels = array();

  for ($i = 0; $i < count($modelNos); $i++) {
    array_push($soModels, array(
      "model_no" => $modelNos[$i],
      "brand_code" => $brandCodes[$i],
      "price" => $prices[$i],
      "qty" => $qtys[$i]
    ));
  }
?>
