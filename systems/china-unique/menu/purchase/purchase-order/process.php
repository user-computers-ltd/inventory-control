<?php
  $id = $_GET["id"];
  $poNo = $_POST["po_no"];
  $poDate = $_POST["po_date"];
  $creditorCode = $_POST["creditor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  /* If a form is submitted, update or insert the sales order. */
  if (
    assigned($poNo) &&
    assigned($poDate) &&
    assigned($creditorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($tax) &&
    assigned($status) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($prices) &&
    assigned($qtys)
  ) {
    $queries = array();

    /* If an id is given, remove the previous sales order first. */
    if (assigned($id)) {
      array_push($queries, "DELETE a FROM `po_model` AS a LEFT JOIN `po_header` AS b ON a.po_no=b.po_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `po_header` WHERE id=\"$id\"");
    }

    /* If the status is not delete, insert a new sales order. */
    if ($status != "DELETED") {

      $values = array();

      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = $prices[$i];
        $qty = $qtys[$i];

        array_push($values, "(\"$poNo\", \"$i\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\", \"$qty\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `po_model`
              (po_no, po_index, brand_code, model_no, price, qty, qty_outstanding)
            VALUES
        " . join(", ", $values));
      }

      array_push($queries, "
        INSERT INTO
          `po_header`
            (po_no, po_date, creditor_code, currency_code, exchange_rate, discount, tax, status, remarks)
          VALUES
            (
              \"$poNo\",
              \"$poDate\",
              \"$creditorCode\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$discount\",
              \"$tax\",
              \"$status\",
              \"$remarks\"
            )
      ");
    } else {
      $queries = concat($queries, onDeleteSalesOrder($poNo));
    }

    execute($queries);

    header("Location: " . PURCHASE_ORDER_SAVED_URL);
  }

  $creditors = query("SELECT code, english_name AS name FROM `creditor`");
  $brands = query("SELECT code, name FROM `brand`");
  $models = query("
    SELECT
      a.brand_code                AS `brand_code`,
      a.model_no                  AS `model_no`,
      a.cost_pri_currency_code    AS `cost_pri_currency_code`,
      a.cost_pri                  AS `primary_cost`,
      a.cost_sec_currency_code    AS `cost_sec_currency_code`,
      a.cost_sec                  AS `secondary_cost`,
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
        h.status='POSTED'
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

  /* If an id is given, attempt to retrieve an existing sales order. */
  if (assigned($id)) {
    $headline = PURCHASE_ORDER_PRINTOUT_TITLE;

    $poHeader = query("
      SELECT
        *,
        DATE_FORMAT(po_date, '%Y-%m-%d') AS `po_date`
      FROM
        `po_header`
      WHERE id=\"$id\"
    ")[0];

    if (isset($poHeader)) {
      $poNo = $poHeader["po_no"];
      $poDate = $poHeader["po_date"];
      $creditorCode = $poHeader["creditor_code"];
      $currencyCode = $poHeader["currency_code"];
      $exchangeRate = $poHeader["exchange_rate"];
      $discount = $poHeader["discount"];
      $tax = $poHeader["tax"];
      $priority = $poHeader["priority"];
      $remarks = $poHeader["remarks"];
      $status = $poHeader["status"];
      $poModels = query("
        SELECT
          brand_code              AS `brand_code`,
          model_no                AS `model_no`,
          price                   AS `price`,
          qty                     AS `qty`,
          qty - qty_outstanding   AS `qty_delivered`
        FROM
          `po_model`
        WHERE
          po_no=\"$poNo\"
        ORDER BY
          po_index ASC
      ");
    }
  }

  /* Else, initialize values for a new sales order. */
  else {
    $headline = PURCHASE_ORDER_CREATE_TITLE;
    $poNo = "PO" . date("YmdHis");
    $poDate = date("Y-m-d");
    $currencyCode = assigned($currencyCode) ? $currencyCode : COMPANY_CURRENCY;
    $exchangeRate = assigned($exchangeRate) ? $exchangeRate : $currencies[$currencyCode];
    $discount = assigned($discount) ? $discount : 0;
    $tax = COMPANY_TAX;
    $priority = 0;
    $status = "DRAFT";
    $poModels = array();

    if (assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = $prices[$i];
        $qty = $qtys[$i];

        array_push($poModels, array(
          "brand_code"  => $brandCode,
          "model_no"    => $modelNo,
          "price"       => $price,
          "qty"         => $qty
        ));
      }
    }
  }
?>
