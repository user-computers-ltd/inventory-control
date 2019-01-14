<?php
  $id = $_GET["id"];
  $soNo = $_POST["so_no"];
  $soDate = $_POST["so_date"];
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $tax = $_POST["tax"];
  $priority = $_POST["priority"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $prices = $_POST["price"];
  $qtys = $_POST["qty"];

  /* If a form is submitted, update or insert the sales order. */
  if (
    assigned($soNo) &&
    assigned($soDate) &&
    assigned($debtorCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($tax) &&
    assigned($priority) &&
    assigned($status) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($prices) &&
    assigned($qtys)
  ) {
    $queries = array();

    /* If an id is given, remove the previous sales order first. */
    if (assigned($id)) {
      array_push($queries, "DELETE a FROM `so_model` AS a LEFT JOIN `so_header` AS b ON a.so_no=b.so_no WHERE b.id=\"$id\"");
      array_push($queries, "DELETE FROM `so_header` WHERE id=\"$id\"");
    }

    /* If the status is not delete, insert a new sales order. */
    if ($status != "DELETED") {

      $values = array();

      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = $prices[$i];
        $qty = $qtys[$i];

        array_push($values, "(\"$soNo\", \"$i\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\", \"$qty\")");
      }

      if (count($values) > 0) {
        array_push($queries, "
          INSERT INTO
            `so_model`
              (so_no, so_index, brand_code, model_no, price, qty, qty_outstanding)
            VALUES
        " . join(", ", $values));
      }

      array_push($queries, "
        INSERT INTO
          `so_header`
            (so_no, so_date, debtor_code, currency_code, exchange_rate, discount, tax, priority, status, remarks)
          VALUES
            (
              \"$soNo\",
              \"$soDate\",
              \"$debtorCode\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$discount\",
              \"$tax\",
              \"$priority\",
              \"$status\",
              \"$remarks\"
            )
      ");
    }

    execute($queries);

    header("Location: " . SALES_ORDER_SAVED_URL);
  }

  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
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
    $headline = SALES_ORDER_PRINTOUT_TITLE;

    $soHeader = query("
      SELECT
        *,
        DATE_FORMAT(so_date, '%Y-%m-%d') AS `so_date`
      FROM
        `so_header`
      WHERE id=\"$id\"
    ")[0];

    if (isset($soHeader)) {
      $soNo = $soHeader["so_no"];
      $soDate = $soHeader["so_date"];
      $debtorCode = $soHeader["debtor_code"];
      $currencyCode = $soHeader["currency_code"];
      $exchangeRate = $soHeader["exchange_rate"];
      $discount = $soHeader["discount"];
      $tax = $soHeader["tax"];
      $priority = $soHeader["priority"];
      $remarks = $soHeader["remarks"];
      $status = $soHeader["status"];
      $soModels = query("
        SELECT
          brand_code              AS `brand_code`,
          model_no                AS `model_no`,
          price                   AS `price`,
          qty                     AS `qty`,
          qty - qty_outstanding   AS `qty_delivered`
        FROM
          `so_model`
        WHERE
          so_no=\"$soNo\"
        ORDER BY
          so_index ASC
      ");
    }
  }

  /* Else, initialize values for a new sales order. */
  else {
    $headline = SALES_ORDER_CREATE_TITLE;
    $soNo = "SO" . date("YmdHis");
    $soDate = date("Y-m-d");
    $currencyCode = COMPANY_CURRENCY;
    $exchangeRate = $currencies[COMPANY_CURRENCY];
    $discount = 0;
    $tax = COMPANY_TAX;
    $priority = 0;
    $status = "DRAFT";
    $soModels = array();

    if (assigned($brandCodes) && assigned($modelNos) && assigned($qtys)) {
      $modelMap = array();

      foreach ($models as $model) {
        $brandCode = $model["brand_code"];
        $modelNo = $model["model_no"];

        $arrayPointer = &$modelMap;

        if (!isset($arrayPointer[$brandCode])) {
          $arrayPointer[$brandCode] = array();
        }
        $arrayPointer = &$arrayPointer[$brandCode];

        $arrayPointer[$modelNo] = $model;
      }

      for ($i = 0; $i < count($brandCodes); $i++) {
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $price = $modelMap[$brandCode][$modelNo]["normal_price"];
        $qty = $qtys[$i];

        array_push($soModels, array(
          "brand_code"  => $brandCode,
          "model_no"    => $modelNo,
          "price"       => $price,
          "qty"         => $qty
        ));
      }
    }
  }
?>
