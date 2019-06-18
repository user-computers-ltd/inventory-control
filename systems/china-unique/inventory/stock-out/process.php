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
    assigned($debtorCode) &&
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
    if ($status !== "DELETED") {

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
              remarks,
              status
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
              \"$remarks\",
              \"$status\"
            )
      ");

      execute($queries);

      if ($status === "POSTED") {
        execute(onPostStockOutVoucher($stockOutNo));
      }
    }

    header("Location: " . STOCK_OUT_SAVED_URL);
  }

  function getWarehouseModels($warehouseCode) {
    return query("
      SELECT
        a.brand_code                                                          AS `brand_code`,
        a.model_no                                                            AS `model_no`,
        a.retail_normal                                                       AS `normal_price`,
        a.retail_special                                                      AS `special_price`,
        a.cost_average                                                        AS `cost_average`,
        IFNULL(b.qty_on_hand, 0)                                              AS `qty_on_hand`,
        IFNULL(c.qty_on_reserve, 0)                                           AS `qty_on_reserve`,
        GREATEST(IFNULL(b.qty_on_hand, 0) - IFNULL(c.qty_on_reserve, 0), 0)   AS `qty_available`
      FROM
        `model` AS a
      LEFT JOIN
        (SELECT
          brand_code, model_no, SUM(qty) AS `qty_on_hand`
        FROM
          `stock`
        WHERE
          warehouse_code=\"$warehouseCode\"
        GROUP BY
          brand_code, model_no) AS b
      ON a.brand_code=b.brand_code AND a.model_no=b.model_no
      LEFT JOIN
        (SELECT
          brand_code, model_no, SUM(qty) AS `qty_on_reserve`
        FROM
          `so_allotment`
        WHERE
          ia_no=\"\" AND warehouse_code=\"$warehouseCode\"
        GROUP BY
          brand_code, model_no) AS c
      ON a.brand_code=c.brand_code AND a.model_no=c.model_no
      WHERE
        GREATEST(IFNULL(b.qty_on_hand, 0) - IFNULL(c.qty_on_reserve, 0), 0) > 0
      ORDER BY
        a.brand_code, a.model_no
    ");
  }

  $creditors = query("SELECT creditor_code AS code, creditor_name_eng AS name FROM `cu_ap`.`creditor`");
  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
  $brands = query("SELECT code, name FROM `brand`");
  $warehouses = query("SELECT code, name FROM `warehouse`");
  $models = array();
  foreach ($warehouses as $warehouse) {
    $models[$warehouse["code"]] = getWarehouseModels($warehouse["code"]);
  }
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }
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

  $useCreditor = $transactionCode === "S3";
?>
