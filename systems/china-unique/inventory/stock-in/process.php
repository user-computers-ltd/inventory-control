<?php
  $id = $_GET["id"];
  $stockInNo = $_POST["stock_in_no"];
  $stockInDate = $_POST["stock_in_date"];
  $transactionCode = $_POST["transaction_code"];
  $warehouseCode = $_POST["warehouse_code"];
  $creditorCode = isset($_POST["creditor_code"]) ? $_POST["creditor_code"] : "MISC";
  $currencyCode = isset($_POST["currency_code"]) ? $_POST["currency_code"] : "RMB";
  $exchangeRate = isset($_POST["exchange_rate"]) ? $_POST["exchange_rate"] : 1;
  $netAmount = isset($_POST["net_amount"]) ? $_POST["net_amount"] : 0;
  $discount = isset($_POST["discount"]) ? $_POST["discount"] : 0;
  $tax = $_POST["tax"];
  $returnVoucherNo = $_POST["return_voucher_no"];
  $remarks = $_POST["remarks"];
  $action = $_POST["action"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = isset($_POST["price"]) ? $_POST["price"] : array();
  $qtys = $_POST["qty"];

  /* If a form is submitted, update or insert the stock in voucher. */
  if (
    assigned($stockInNo) &&
    assigned($stockInDate) &&
    assigned($transactionCode) &&
    assigned($warehouseCode) &&
    assigned($creditorCode) &&
    assigned($tax) &&
    assigned($action) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($qtys)
  ) {
    $values = array();

    for ($i = 0; $i < count($brandCodes); $i++) {
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $price = isset($prices[$i]) ? $prices[$i] : 0;
      $qty = $qtys[$i];

      if ($qty > 0) {
        array_push($values, "(\"$stockInNo\", \"$i\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")");
      }
    }

    $queries = array();

    if ($action === "delete" && assigned($id)) {
      array_push($queries, "DELETE a FROM `stock_in_model` AS a LEFT JOIN `stock_in_header` AS b ON a.stock_in_no=b.stock_in_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `stock_in_header` WHERE id=\"$id\"");
    } else if ($action === "create") {
      array_push($queries, "
        INSERT INTO
          `stock_in_header`
            (
              stock_in_no,
              stock_in_date,
              transaction_code,
              warehouse_code,
              creditor_code,
              currency_code,
              exchange_rate,
              net_amount,
              discount,
              tax,
              return_voucher_no,
              remarks,
              status
            )
          VALUES
            (
              \"$stockInNo\",
              \"$stockInDate\",
              \"$transactionCode\",
              \"$warehouseCode\",
              \"$creditorCode\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$netAmount\",
              \"$discount\",
              \"$tax\",
              \"$returnVoucherNo\",
              \"$remarks\",
              \"SAVED\"
            )
      ");

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `stock_in_model`
              (stock_in_no, stock_in_index, brand_code, model_no, price, qty)
            VALUES
        " . join(", ", $values));
      }
    } else if ($action === "update" || $action === "post" && assigned($id)) {
      array_push($queries, "
        UPDATE
          `stock_in_header`
        SET
          stock_in_no=\"$stockInNo\",
          stock_in_date=\"$stockInDate\",
          transaction_code=\"$transactionCode\",
          warehouse_code=\"$warehouseCode\",
          creditor_code=\"$creditorCode\",
          currency_code=\"$currencyCode\",
          exchange_rate=\"$exchangeRate\",
          net_amount=\"$netAmount\",
          discount=\"$discount\",
          tax=\"$tax\",
          return_voucher_no=\"$returnVoucherNo\",
          remarks=\"$remarks\",
          status=\"SAVED\"
        WHERE
          id=\"$id\"
      ");

      array_push($queries, "DELETE a FROM `stock_in_model` AS a LEFT JOIN `stock_in_header` AS b ON a.stock_in_no=b.stock_in_no WHERE b.id=\"$id\"");

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `stock_in_model`
              (stock_in_no, stock_in_index, brand_code, model_no, price, qty)
            VALUES
        " . join(", ", $values));
      }
    }

    execute($queries);

    if ($action === "post" && assigned($id) && assigned($stockInNo)) {
      $queries = array("UPDATE `stock_in_header` SET status=\"POSTED\" WHERE id=\"$id\"");
      concat($queries, onPostStockInVoucher($stockInNo));

      execute($queries);
    }

    header("Location: " . STOCK_IN_SAVED_URL);
  }

  $creditors = query("SELECT creditor_code AS code, creditor_name_eng AS name FROM `cu_ap`.`creditor`");
  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
  $brands = query("SELECT code, name FROM `brand`");
  $models = query("
    SELECT
      a.brand_code                AS `brand_code`,
      a.model_no                  AS `model_no`,
      a.cost_pri                  AS `normal_price`,
      a.cost_pri_currency_code    AS `normal_price_currency_code`,
      a.cost_sec                  AS `special_price`,
      a.cost_sec_currency_code    AS `special_price_currency_code`,
      a.cost_average              AS `cost_average`,
      IFNULL(b.qty_on_hand, 0)    AS `qty_on_hand`,
      IFNULL(c.qty_on_order, 0)   AS `qty_on_order`
    FROM
      `model` AS a
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        brand_code, model_no) AS b
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
        h.status=\"POSTED\"
      GROUP BY
        m.brand_code, m.model_no) AS c
    ON a.model_no=c.model_no AND a.brand_code=c.brand_code
    ORDER BY
      a.model_no, a.brand_code
  ");
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }
  $warehouses = query("SELECT code, name FROM `warehouse`");
  $transactionCodes = array_filter($TRANSACTION_CODES, function ($code) {
    return strpos($code, "R") === 0 && $code != "R2";
  }, ARRAY_FILTER_USE_KEY);

  $voucherResults = query("
    SELECT
      b.debtor_code AS `debtor_code`,
      b.do_no       AS `voucher_no`,
      a.brand_code  AS `brand_code`,
      a.model_no    AS `model_no`,
      a.qty         AS `qty`,
      a.price       AS `price`,
      b.discount    AS `discount`
    FROM
      `sdo_model` AS a
    LEFT JOIN
      `sdo_header` AS b
    ON a.do_no=b.do_no
    WHERE
      b.status=\"POSTED\"
    UNION
    SELECT
      b.debtor_code   AS `debtor_code`,
      b.stock_out_no  AS `voucher_no`,
      a.brand_code    AS `brand_code`,
      a.model_no      AS `model_no`,
      a.qty           AS `qty`,
      a.price         AS `price`,
      b.discount      AS `discount`
    FROM
      `stock_out_model` AS a
    LEFT JOIN
      `stock_out_header` AS b
    ON a.stock_out_no=b.stock_out_no
    WHERE
      b.transaction_code=\"S1\" AND
      b.status=\"POSTED\"
  ");

  $R3Vouchers = array();

  foreach ($voucherResults as $voucherResult) {
    $dCode = $voucherResult["debtor_code"];
    $vNo = $voucherResult["voucher_no"];

    $pointer = &$R3Vouchers;

    if (!isset($pointer[$dCode])) {
      $pointer[$dCode] = array();
    }
    $pointer = &$pointer[$dCode];

    if (!isset($pointer[$vNo])) {
      $pointer[$vNo] = array();
    }
    $pointer = &$pointer[$vNo];

    array_push($pointer, $voucherResult);
  }

  /* If an id is given, attempt to retrieve an existing stock in voucher. */
  if (assigned($id)) {
    $headline = STOCK_IN_PRINTOUT_TITLE;

    $stockInHeader = query("
      SELECT
        *,
        DATE_FORMAT(stock_in_date, \"%Y-%m-%d\") AS `stock_in_date`
      FROM
        `stock_in_header`
      WHERE id=\"$id\"
    ")[0];

    if (isset($stockInHeader)) {
      $stockInNo = $stockInHeader["stock_in_no"];
      $stockInDate = $stockInHeader["stock_in_date"];
      $transactionCode = $stockInHeader["transaction_code"];
      $warehouseCode = $stockInHeader["warehouse_code"];
      $creditorCode = $stockInHeader["creditor_code"];
      $currencyCode = $stockInHeader["currency_code"];
      $exchangeRate = $stockInHeader["exchange_rate"];
      $netAmount = $stockInHeader["net_amount"];
      $discount = $stockInHeader["discount"];
      $tax = $stockInHeader["tax"];
      $returnVoucherNo = $stockInHeader["return_voucher_no"];
      $remarks = $stockInHeader["remarks"];
      $status = $stockInHeader["status"];
      $stockInModels = query("
        SELECT
          brand_code,
          model_no,
          price,
          qty
        FROM
          `stock_in_model`
        WHERE
          stock_in_no=\"$stockInNo\"
        ORDER BY
          stock_in_index ASC
      ");
    }
  }

  /* Else, initialize values for a new stock in voucher. */
  else {
    $headline = STOCK_IN_CREATE_TITLE;
    $stockInNo = "SI" . date("YmdHis");
    $stockInDate = date("Y-m-d");
    $transactionCode = "";
    $warehouseCode = "";
    $creditorCode = "";
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[COMPANY_CURRENCY];
    $netAmount = 0;
    $discount = 0;
    $tax = COMPANY_TAX;
    $returnVoucherNo = "";
    $status = "DRAFT";
    $stockInModels = array();
  }

  $useDebtor = $transactionCode === "R3";
?>
