<?php
  include_once "config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  function postTransactions(
    $headerNo,
    $transactionCode,
    $date,
    $clientCode,
    $currencyCode,
    $exchangeRate,
    $discount,
    $tax,
    $warehouseCode,
    $brandCodes,
    $modelNos,
    $prices,
    $qtys
  ) {
    $discountFactor = (100 - $discount) / 100;
    $taxFactor = (100 + $tax) / 100;

    $queries = array();
    $transactionValues = array();

    for ($i = 0; $i < count($brandCodes); $i++) {
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $price = $prices[$i];
      $qty = $qtys[$i];
      $cost = $price * $discountFactor / $taxFactor * $exchangeRate;
      $stockChange = strpos($transactionCode, "R") === 0 ? $qty : -$qty;

      if ($qty > 0) {
        /* Update model average cost. */
        if ($transactionCode === "R1" || $transactionCode === "R2") {
          array_push($queries, "
            UPDATE
              `model` AS a
            LEFT JOIN
              (SELECT
                brand_code,
                model_no,
                SUM(qty) AS `qty_on_hand`
              FROM
                `stock`
              GROUP BY
                brand_code, model_no) AS b
            ON a.brand_code=b.brand_code AND a.model_no=b.model_no
            SET
              a.cost_average=(a.cost_average * IFNULL(b.qty_on_hand, 0) + $cost * $qty) / (IFNULL(b.qty_on_hand, 0) + $qty)
            WHERE
              a.brand_code=\"$brandCode\" AND a.model_no=\"$modelNo\"
          ");
        }

        /* Update or insert stock. */
        array_push($queries, "
          INSERT INTO
            `stock`
            (warehouse_code, brand_code, model_no, qty)
          VALUES
            (\"$warehouseCode\", \"$brandCode\", \"$modelNo\", \"$stockChange\")
          ON DUPLICATE KEY UPDATE qty=qty + $stockChange;
        ");

        /* Collect transaction values for insertion. */
        array_push($transactionValues, "
          (
            \"$headerNo\",
            \"$transactionCode\",
            \"$date\",
            \"$clientCode\",
            \"$currencyCode\",
            \"$exchangeRate\",
            \"$brandCode\",
            \"$modelNo\",
            (SELECT cost_average FROM `model` WHERE brand_code=\"$brandCode\" AND model_no=\"$modelNo\"),
            \"$price\",
            \"$qty\",
            \"$discount\",
            \"$tax\",
            \"$warehouseCode\"
          )
        ");
      }
    }

    if (count($transactionValues) > 0) {
      $transactionValues = join(", ", $transactionValues);

      /* Insert transactions. */
      array_push($queries, "
        INSERT INTO
          `transaction`
        (
          header_no,
          transaction_code,
          transaction_date,
          client_code,
          currency_code,
          exchange_rate,
          brand_code,
          model_no,
          cost_average,
          price,
          qty,
          discount,
          tax,
          warehouse_code
        )
        VALUES
          $transactionValues
      ");
    }

    return $queries;
  }

  function transferIncomingAllotments($iaNo, $warehouseCode) {
    $queries = array();

    $soAllotments = query("
      SELECT
        ia_no             AS `ia_no`,
        warehouse_code    AS `warehouse_code`,
        so_no             AS `so_no`,
        brand_code        AS `brand_code`,
        model_no          AS `model_no`,
        qty               AS `qty`
      FROM
        `so_allotment`
      WHERE
        ia_no=\"$iaNo\"
    ");

    /* Delete the sales incoming allotments. */
    array_push($queries, "DELETE FROM `so_allotment` WHERE ia_no=\"$iaNo\"");

    /* Update or insert those allotments into sales stock allotments. */
    foreach ($soAllotments as $soAllotment) {
      $soNo = $soAllotment["so_no"];
      $brandCode = $soAllotment["brand_code"];
      $modelNo = $soAllotment["model_no"];
      $qty = $soAllotment["qty"];

      array_push($queries, "
        INSERT INTO
          `so_allotment`
          (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
        VALUES
          (\"\", \"$warehouseCode\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")
        ON DUPLICATE KEY UPDATE qty=qty + $qty;
      ");
    }

    $sdoModels = query("
      SELECT
        do_no             AS `do_no`,
        do_index          AS `do_index`,
        so_no             AS `so_no`,
        brand_code        AS `brand_code`,
        model_no          AS `model_no`,
        price             AS `price`,
        qty               AS `qty`
      FROM
        `sdo_model`
      WHERE
        ia_no=\"$iaNo\"
    ");

    /* Delete the sales incoming delivery order models. */
    array_push($queries, "DELETE FROM `sdo_model` WHERE ia_no=\"$iaNo\"");

    /* Update or insert those models into sales stock delivery order models. */
    foreach ($sdoModels as $sdoModel) {
      $doNo = $sdoModel["do_no"];
      $doIndex = $sdoModel["do_index"];
      $soNo = $sdoModel["so_no"];
      $brandCode = $sdoModel["brand_code"];
      $modelNo = $sdoModel["model_no"];
      $price = $sdoModel["price"];
      $qty = $sdoModel["qty"];

      array_push($queries, "
        INSERT INTO
          `sdo_model`
          (do_no, do_index, ia_no, so_no, brand_code, model_no, price, qty)
        VALUES
          (\"$doNo\", \"$doIndex\", \"\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")
        ON DUPLICATE KEY UPDATE qty=qty + $qty;
      ");
    }

    return $queries;
  }

  function onPostStockInVoucher($stockInNo) {
    $queries = array();

    $stockInHeader = query("
      SELECT
        stock_in_date,
        transaction_code,
        warehouse_code,
        creditor_code,
        currency_code,
        exchange_rate,
        discount,
        tax
      FROM
        `stock_in_header`
      WHERE
        stock_in_no=\"$stockInNo\"
    ")[0];

    $stockInModels = query("
      SELECT
        brand_code      AS `brand_code`,
        model_no        AS `model_no`,
        price           AS `price`,
        qty             AS `qty`
      FROM
        `stock_in_model`
      WHERE
        stock_in_no=\"$stockInNo\"
    ");

    /* Insert corresponding transactions. */
    $brandCodes = array();
    $modelNos = array();
    $prices = array();
    $qtys = array();

    foreach ($stockInModels as $stockInModel) {
      array_push($brandCodes, $stockInModel["brand_code"]);
      array_push($modelNos, $stockInModel["model_no"]);
      array_push($prices, $stockInModel["price"]);
      array_push($qtys, $stockInModel["qty"]);
    }

    $postTransactionQueries = postTransactions(
      $stockInNo,
      $stockInHeader["transaction_code"],
      $stockInHeader["stock_in_date"],
      $stockInHeader["creditor_code"],
      $stockInHeader["currency_code"],
      $stockInHeader["exchange_rate"],
      $stockInHeader["discount"],
      $stockInHeader["tax"],
      $stockInHeader["warehouse_code"],
      $brandCodes,
      $modelNos,
      $prices,
      $qtys
    );

    return concat($queries, $postTransactionQueries);
  }

  function onPostStockOutVoucher($stockOutNo) {
    $queries = array();

    $stockOutHeader = query("
      SELECT
        stock_out_date,
        transaction_code,
        warehouse_code,
        debtor_code,
        currency_code,
        exchange_rate,
        discount,
        tax
      FROM
        `stock_out_header`
      WHERE
        stock_out_no=\"$stockOutNo\"
    ")[0];

    $stockOutModels = query("
      SELECT
        brand_code      AS `brand_code`,
        model_no        AS `model_no`,
        price           AS `price`,
        qty             AS `qty`
      FROM
        `stock_out_model`
      WHERE
        stock_out_no=\"$stockOutNo\"
    ");

    /* Insert corresponding transactions. */
    $brandCodes = array();
    $modelNos = array();
    $prices = array();
    $qtys = array();

    foreach ($stockOutModels as $stockOutModel) {
      array_push($brandCodes, $stockOutModel["brand_code"]);
      array_push($modelNos, $stockOutModel["model_no"]);
      array_push($prices, $stockOutModel["price"]);
      array_push($qtys, $stockOutModel["qty"]);
    }

    $postTransactionQueries = postTransactions(
      $stockOutNo,
      $stockOutHeader["transaction_code"],
      $stockOutHeader["stock_out_date"],
      $stockOutHeader["debtor_code"],
      $stockOutHeader["currency_code"],
      $stockOutHeader["exchange_rate"],
      $stockOutHeader["discount"],
      $stockOutHeader["tax"],
      $stockOutHeader["warehouse_code"],
      $brandCodes,
      $modelNos,
      $prices,
      $qtys
    );

    return concat($queries, $postTransactionQueries);
  }

  function onPostSalesDeliveryOrder($doNo) {
    $queries = array();

    $doModels = query("
      SELECT
        so_no           AS `so_no`,
        brand_code      AS `brand_code`,
        model_no        AS `model_no`,
        price           AS `price`,
        SUM(qty)        AS `qty`
      FROM
        `sdo_model`
      WHERE
        do_no=\"$doNo\"
      GROUP BY
        so_no, brand_code, model_no, price
    ");

    /* Update sales order outstanding quantities. */
    foreach ($doModels as $doModel) {
      $soNo = $doModel["so_no"];
      $brandCode = $doModel["brand_code"];
      $modelNo = $doModel["model_no"];
      $qty = $doModel["qty"];

      array_push($queries, "
        UPDATE
          `so_model`
        SET
          qty_outstanding=qty_outstanding - $qty
        WHERE
          so_no=\"$soNo\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
      ");
    }

    $allotments = query("
      SELECT
        b.warehouse_code    AS `warehouse_code`,
        a.so_no             AS `so_no`,
        a.brand_code        AS `brand_code`,
        a.model_no          AS `model_no`,
        a.price             AS `price`,
        a.qty               AS `qty`
      FROM
        `sdo_model` AS a
      LEFT JOIN
        `sdo_header` AS b
      ON a.do_no=b.do_no
      WHERE
        a.do_no=\"$doNo\"");

    /* Remove corresponding allotments. */
    foreach ($allotments as $allotment) {
      $warehouseCode = $allotment["warehouse_code"];
      $soNo = $allotment["so_no"];
      $brandCode = $allotment["brand_code"];
      $modelNo = $allotment["model_no"];

      array_push($queries, "
        DELETE FROM
          `so_allotment`
        WHERE
          ia_no=\"\" AND
          warehouse_code=\"$warehouseCode\" AND
          so_no=\"$soNo\" AND
          brand_code=\"$brandCode\" AND
          model_no=\"$modelNo\"
      ");
    }

    $doHeader = query("
      SELECT
        do_date,
        debtor_code,
        currency_code,
        exchange_rate,
        discount,
        tax,
        warehouse_code
      FROM
        `sdo_header`
      WHERE
        do_no=\"$doNo\"
    ")[0];

    $brandCodes = array();
    $modelNos = array();
    $prices = array();
    $qtys = array();

    foreach ($allotments as $allotment) {
      array_push($brandCodes, $allotment["brand_code"]);
      array_push($modelNos, $allotment["model_no"]);
      array_push($prices, $allotment["price"]);
      array_push($qtys, $allotment["qty"]);
    }

    /* Insert corresponding transactions. */
    $queries = concat($queries, postTransactions(
      $doNo,
      "S2",
      $doHeader["do_date"],
      $doHeader["debtor_code"],
      $doHeader["currency_code"],
      $doHeader["exchange_rate"],
      $doHeader["discount"],
      $doHeader["tax"],
      $doHeader["warehouse_code"],
      $brandCodes,
      $modelNos,
      $prices,
      $qtys
    ));

    return $queries;
  }

  function onPostIncomingAdvice($iaNo) {
    $queries = array();

    // TODO: Need to handle ia models with no allotments.
    $poAllotments = query("
      SELECT
        a.po_no           AS `po_no`,
        c.currency_code   AS `currency_code`,
        c.exchange_rate   AS `exchange_rate`,
        c.discount        AS `discount`,
        c.tax             AS `tax`,
        a.brand_code      AS `brand_code`,
        a.model_no        AS `model_no`,
        b.price           AS `price`,
        a.qty             AS `qty`
      FROM
        `po_allotment` AS a
      LEFT JOIN
        `po_model` AS b
      ON a.po_no=b.po_no AND a.brand_code=b.brand_code AND a.model_no=b.model_no
      LEFT JOIN
        `po_header` AS c
      ON a.po_no=c.po_no
      WHERE
        a.ia_no=\"$iaNo\"
    ");

    $iaHeader = query("
      SELECT
        do_no,
        ia_date,
        creditor_code,
        warehouse_code,
        remarks
      FROM
        `ia_header`
      WHERE
        ia_no=\"$iaNo\"
    ")[0];
    $doNo = $iaHeader["do_no"];
    $date = $iaHeader["ia_date"];
    $creditorCode = $iaHeader["creditor_code"];
    $warehouseCode = $iaHeader["warehouse_code"];
    $remarks = $iaHeader["remarks"];

    /* Insert a purchase delivery order as record. */
    $doValues = array();

    array_push($queries, "
      INSERT INTO
        `pdo_header`
        (do_no, do_date, creditor_code, warehouse_code, remarks)
      VALUES
        (\"$doNo\", \"$date\", \"$creditorCode\", \"$warehouseCode\", \"$remarks\")
    ");

    for ($i = 0; $i < count($poAllotments); $i++) {
      $poAllotment = $poAllotments[$i];
      $poNo = $poAllotment["po_no"];
      $currencyCode = $poAllotment["currency_code"];
      $exchangeRate = $poAllotment["exchange_rate"];
      $discount = $poAllotment["discount"];
      $tax = $poAllotment["tax"];
      $brandCode = $poAllotment["brand_code"];
      $modelNo = $poAllotment["model_no"];
      $price = $poAllotment["price"];
      $qty = $poAllotment["qty"];

      array_push($doValues, "
        (
          \"$doNo\",
          \"$i\",
          \"$poNo\",
          \"$currencyCode\",
          \"$exchangeRate\",
          \"$discount\",
          \"$tax\",
          \"$brandCode\",
          \"$modelNo\",
          \"$price\",
          \"$qty\"
        )
      ");
    }

    if (count($doValues) > 0) {
      $doValues = join(", ", $doValues);

      array_push($queries, "
        INSERT INTO
          `pdo_model`
          (
            do_no,
            do_index,
            po_no,
            currency_code,
            exchange_rate,
            discount,
            tax,
            brand_code,
            model_no,
            price,
            qty
          )
        VALUES
          $doValues
      ");
    }

    /* Update purchase order outstanding quantities. */
    foreach ($poAllotments as $poAllotment) {
      $poNo = $poAllotment["po_no"];
      $brandCode = $poAllotment["brand_code"];
      $modelNo = $poAllotment["model_no"];
      $qty = $poAllotment["qty"];

      array_push($queries, "
        UPDATE
          `po_model`
        SET
          qty_outstanding=qty_outstanding - $qty
        WHERE
          po_no=\"$poNo\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
      ");
    }

    /* Transfer all the sales incoming allotments to stock allotments. */
    $queries = concat($queries, transferIncomingAllotments($iaNo, $warehouseCode));

    /* Insert corresponding transactions. */
    foreach ($poAllotments as $poAllotment) {
      $currencyCode = $poAllotment["currency_code"];
      $ExchangeRate = $poAllotment["exchange_rate"];
      $discount = $poAllotment["discount"];
      $tax = $poAllotment["tax"];
      $brandCode = $poAllotment["brand_code"];
      $modelNo = $poAllotment["model_no"];
      $price = $poAllotment["price"];
      $qty = $poAllotment["qty"];

      $queries = concat($queries, postTransactions(
        $doNo,
        "R2",
        $date,
        $creditorCode,
        $currencyCode,
        $ExchangeRate,
        $discount,
        $tax,
        $warehouseCode,
        array($brandCode),
        array($modelNo),
        array($price),
        array($qty)
      ));
    }

    /* Delete the purchase allotments. */
    array_push($queries, "DELETE FROM `po_allotment` WHERE ia_no=\"$iaNo\"");

    return $queries;
  }

  function onDeleteSalesOrder($soNo) {
    $queries = array();

    array_push($queries, "DELETE FROM `so_allotment` WHERE so_no=\"$soNo\"");
    array_push($queries, "DELETE FROM `sdo_model` WHERE so_no=\"$soNo\"");

    return $queries;
  }
?>
