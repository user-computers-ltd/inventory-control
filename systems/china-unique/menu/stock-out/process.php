<?php
  $id = $_GET["id"];
  $stockOutNo = $_POST["stock_out_no"];
  $stockOutDate = $_POST["stock_out_date"];
  $transactionCode = $_POST["transaction_code"];
  $warehouseCode = $_POST["warehouse_code"];
  $debtorCode = isset($_POST["debtor_code"]) ? $_POST["debtor_code"] : "MISC";
  $currencyCode = isset($_POST["currency_code"]) ? $_POST["currency_code"] : "RMB";
  $exchangeRate = isset($_POST["exchange_rate"]) ? $_POST["exchange_rate"] : 1;
  $netAmount = isset($_POST["net_amount"]) ? $_POST["net_amount"] : 0;
  $discount = isset($_POST["discount"]) ? $_POST["discount"] : 0;
  $tax = $_POST["tax"];
  $refNo = $_POST["ref_no"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = isset($_POST["price"]) ? $_POST["price"] : array();
  $qtys = $_POST["qty"];

  /* If a form is submitted, update or insert the stock out voucher. */
  if (
    assigned($stockOutNo) &&
    assigned($stockOutDate) &&
    assigned($transactionCode) &&
    assigned($warehouseCode) &&
    assigned($tax) &&
    assigned($status) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($qtys)
  ) {
    $queries = array();

    /* If an id is given, remove the previous stock out voucher first. */
    if (assigned($id)) {
      array_push($queries, "DELETE a FROM `stock_out_model` AS a LEFT JOIN `stock_out_header` AS b ON a.stock_out_no=b.stock_out_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `stock_out_header` WHERE id=\"$id\"");
    }

    /* If the status is not delete, insert a new stock out voucher. */
    if ($status != "DELETED") {

      $values = array();

      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = isset($prices[$i]) ? $prices[$i] : 0;
        $qty = $qtys[$i];

        array_push($values, "(\"$stockOutNo\", \"$i\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `stock_out_model`
              (stock_out_no, stock_out_index, brand_code, model_no, price, qty)
            VALUES
        " . join(", ", $values));
      }

      array_push($queries, "
        INSERT INTO
          `stock_out_header`
            (
              stock_out_no,
              stock_out_date,
              transaction_code,
              warehouse_code,
              debtor_code,
              currency_code,
              exchange_rate,
              net_amount,
              discount,
              tax,
              status,
              ref_no,
              remarks
            )
          VALUES
            (
              \"$stockOutNo\",
              \"$stockOutDate\",
              \"$transactionCode\",
              \"$warehouseCode\",
              \"$debtorCode\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$netAmount\",
              \"$discount\",
              \"$tax\",
              \"$status\",
              \"$refNo\",
              \"$remarks\"
            )
      ");

      if (assigned($id) && $status == "POSTED") {
        $queries = concat($queries, onPostStockOutVoucher($stockOutNo));
      }
    }

    execute($queries);

    header("Location: " . STOCK_OUT_SAVED_URL);
  }

  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
  $brands = query("SELECT code, name FROM `brand`");
  $models = query("
    SELECT
      a.brand_code                                                          AS `brand_code`,
      a.model_no                                                            AS `model_no`,
      a.retail_normal                                                       AS `normal_price`,
      a.retail_special                                                      AS `special_price`,
      a.cost_average                                                        AS `cost_average`,
      IFNULL(b.qty_on_hand, 0)                                              AS `qty_on_hand`,
      IFNULL(c.qty_on_order, 0)                                             AS `qty_on_order`,
      IFNULL(d.qty_on_reserve, 0)                                           AS `qty_on_reserve`,
      GREATEST(IFNULL(b.qty_on_hand, 0) - IFNULL(d.qty_on_reserve, 0), 0)   AS `qty_available`
    FROM
      `model` AS a
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        model_no, brand_code) AS b
    ON a.model_no=b.model_no AND a.brand_code=b.brand_code
    LEFT JOIN
      (SELECT
        m.model_no, m.brand_code, SUM(GREATEST(qty_outstanding, 0)) AS `qty_on_order`
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
    LEFT JOIN
      (SELECT
        brand_code, model_no, SUM(qty) AS `qty_on_reserve`
      FROM
        `so_allotment`
      GROUP BY
        model_no, brand_code) AS d
    ON a.model_no=d.model_no AND a.brand_code=d.brand_code
  ");
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }
  $warehouses = query("SELECT code, name FROM `warehouse`");
  $transactionCodes = array_filter($TRANSACTION_CODES, function ($code) {
    return strpos($code, "S") === 0 && $code != "S2";
  }, ARRAY_FILTER_USE_KEY);

  /* If an id is given, attempt to retrieve an existing stock out voucher. */
  if (assigned($id)) {
    $headline = STOCK_OUT_PRINTOUT_TITLE;

    $stockOutHeader = query("
      SELECT
        *,
        DATE_FORMAT(stock_out_date, '%Y-%m-%d') AS `stock_out_date`
      FROM
        `stock_out_header`
      WHERE id=\"$id\"
    ")[0];

    if (isset($stockOutHeader)) {
      $stockOutNo = $stockOutHeader["stock_out_no"];
      $stockOutDate = $stockOutHeader["stock_out_date"];
      $transactionCode = $stockOutHeader["transaction_code"];
      $warehouseCode = $stockOutHeader["warehouse_code"];
      $debtorCode = $stockOutHeader["debtor_code"];
      $currencyCode = $stockOutHeader["currency_code"];
      $exchangeRate = $stockOutHeader["exchange_rate"];
      $netAmount = $stockOutHeader["net_amount"];
      $discount = $stockOutHeader["discount"];
      $tax = $stockOutHeader["tax"];
      $refNo = $stockOutHeader["ref_no"];
      $remarks = $stockOutHeader["remarks"];
      $status = $stockOutHeader["status"];
      $stockOutModels = query("
        SELECT
          brand_code,
          model_no,
          price,
          qty
        FROM
          `stock_out_model`
        WHERE
          stock_out_no=\"$stockOutNo\"
        ORDER BY
          stock_out_index ASC
      ");
    }
  }

  /* Else, initialize values for a new stock out voucher. */
  else {
    $headline = STOCK_OUT_CREATE_TITLE;
    $stockOutNo = "SO" . date("YmdHis");
    $stockOutDate = date("Y-m-d");
    $transactionCode = "";
    $warehouseCode = "";
    $debtorCode = "";
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[COMPANY_CURRENCY];
    $netAmount = 0;
    $discount = 0;
    $tax = COMPANY_TAX;
    $status = "DRAFT";
    $stockOutModels = array();
  }
?>
