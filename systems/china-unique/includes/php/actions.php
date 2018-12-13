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

      /* Update model average cost. */
      if ($transactionCode == "R1" || $transactionCode == "R2") {
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

  function onPostPackingList($plNo) {
    $queries = array();

    $plHeader = query("
      SELECT
        pl_date,
        debtor_code,
        currency_code,
        exchange_rate,
        discount,
        tax,
        warehouse_code
      FROM
        `pl_header`
      WHERE
        pl_no=\"$plNo\"
    ")[0];

    $soModelRefs = query("
      SELECT
        so_no           AS `so_no`,
        brand_code      AS `brand_code`,
        model_no        AS `model_no`,
        price           AS `price`,
        SUM(qty)        AS `qty`
      FROM
        `pl_model`
      WHERE
        pl_no=\"$plNo\"
      GROUP BY
        so_no, brand_code, model_no, price
    ");

    $allotmentRefs = query("
      SELECT
        b.warehouse_code    AS `warehouse_code`,
        a.so_no             AS `so_no`,
        a.brand_code        AS `brand_code`,
        a.model_no          AS `model_no`,
        a.price             AS `price`,
        a.qty               AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `pl_header` AS b
      ON a.pl_no=b.pl_no
      WHERE
        a.pl_no=\"$plNo\"");

    foreach ($soModelRefs as $soModelRef) {
      $soNo = $soModelRef["so_no"];
      $brandCode = $soModelRef["brand_code"];
      $modelNo = $soModelRef["model_no"];
      $qty = $soModelRef["qty"];

      /* Update sales order outsanding quantities. */
      array_push($queries, "
        UPDATE
          `so_model`
        SET
          qty_outstanding=qty_outstanding-$qty
        WHERE
          so_no=\"$soNo\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
      ");
    }

    /* Remove corresponding allotments. */
    foreach ($allotmentRefs as $allotmentRef) {
      $warehouseCode = $allotmentRef["warehouse_code"];
      $soNo = $allotmentRef["so_no"];
      $brandCode = $allotmentRef["brand_code"];
      $modelNo = $allotmentRef["model_no"];

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

    /* Insert corresponding transactions. */
    $brandCodes = array();
    $modelNos = array();
    $prices = array();
    $qtys = array();

    foreach ($allotmentRefs as $allotmentRef) {
      array_push($brandCodes, $allotmentRef["brand_code"]);
      array_push($modelNos, $allotmentRef["model_no"]);
      array_push($prices, $allotmentRef["price"]);
      array_push($qtys, $allotmentRef["qty"]);
    }

    $postTransactionQueries = postTransactions(
      $plNo,
      "S2",
      $plHeader["pl_date"],
      $plHeader["debtor_code"],
      $plHeader["currency_code"],
      $plHeader["exchange_rate"],
      $plHeader["discount"],
      $plHeader["tax"],
      $plHeader["warehouse_code"],
      $brandCodes,
      $modelNos,
      $prices,
      $qtys
    );

    return concat($queries, $postTransactionQueries);
  }

  function processDOStockIn() {
    $allotmentQueries = updatePostedIncomingAllotments();
    $packingListQueries = updateOnHandPackingListModels();

    return concat($allotmentQueries, $packingListQueries);
  }

  function updatePostedIncomingAllotments() {
    $queries = array();

    $postedAllotments = query("
      SELECT
        a.ia_no             AS `ia_no`,
        b.warehouse_code    AS `warehouse_code`,
        a.so_no             AS `so_no`,
        a.brand_code        AS `brand_code`,
        a.model_no          AS `model_no`,
        a.qty               AS `qty`
      FROM
        `so_allotment` AS a
      LEFT JOIN
        `ia_header` AS b
      ON a.ia_no=b.ia_no
      WHERE
        b.ia_no IS NOT NULL AND
        b.status='POSTED'
    ");

    $stockAllotments = query("
      SELECT
        a.warehouse_code    AS `warehouse_code`,
        a.so_no             AS `so_no`,
        a.brand_code        AS `brand_code`,
        a.model_no          AS `model_no`,
        a.qty               AS `qty`
      FROM
        `so_allotment` AS a
      LEFT JOIN
        `warehouse` AS b
      ON a.warehouse_code=b.code
      WHERE
        b.code IS NOT NULL
    ");

    /* Delete the incoming allotment which are already posted. */
    array_push($queries, "
      DELETE a FROM
        `so_allotment` AS a
      LEFT JOIN
        `ia_header` AS b
      ON a.ia_no=b.ia_no
      WHERE
        b.ia_no IS NOT NULL AND
        b.status='POSTED'
    ");

    $allotmentValues = array();

    foreach ($postedAllotments as $postedAllotment) {
      $iaNo = $postedAllotment["ia_no"];
      $warehouseCode = $postedAllotment["warehouse_code"];
      $soNo = $postedAllotment["so_no"];
      $brandCode = $postedAllotment["brand_code"];
      $modelNo = $postedAllotment["model_no"];
      $qty = $postedAllotment["qty"];
      $existingStockAllotment = array_filter($stockAllotments, function ($a) {
        return
          $a["warehouse_code"] == $warehouseCode &&
          $a["so_no"] == $soNo &&
          $a["brand_code"] == $brandCode &&
          $a["model_no"] == $modelNo;
      })[0];

      /* If a stock allotment exists, sum up the quantity with the existing one. */
      if (isset($existingStockAllotment)) {
        array_push($queries, "
          UPDATE
            `so_allotment`
          SET
            qty=qty+$qty
          WHERE
            warehouse_code=\"$warehouseCode\" AND
            so_no=\"$soNo\" AND
            brand_code=\"$brandCode\" AND
            model_no=\"$modelNo\"
        ");
      }

      /* Else, collect values for new allotment insertion. */
      else {
        array_push($allotmentValues, "
          (
            \"\",
            \"$warehouseCode\",
            \"$soNo\",
            \"$brandCode\",
            \"$modelNo\",
            \"$qty\"
          )
        ");
      }
    }

    /* Insert new stock allotments. */
    if (count($allotmentValues) > 0) {
      $allotmentValues = join(", ", $allotmentValues);

      array_push($queries, "
        INSERT INTO
          `so_allotment`
          (
            ia_no,
            warehouse_code,
            so_no,
            brand_code,
            model_no,
            qty
          )
        VALUES
          $allotmentValues
      ");
    }

    return $queries;
  }

  function updateOnHandPackingListModels() {
    $queries = array();

    $postedPackingListModels = query("
      SELECT
        a.pl_no             AS `pl_no`,
        a.pl_index          AS `pl_index`,
        a.ia_no             AS `ia_no`,
        b.warehouse_code    AS `warehouse_code`,
        a.so_no             AS `so_no`,
        a.brand_code        AS `brand_code`,
        a.model_no          AS `model_no`,
        a.price             AS `price`,
        a.qty               AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `pl_header` AS b
      ON a.pl_no=b.pl_no
      LEFT JOIN
        `ia_header` AS c
      ON a.ia_no=c.ia_no
      WHERE
        c.ia_no IS NOT NULL AND
        c.status='POSTED'
    ");

    $stockPackingListModels = query("
      SELECT
        b.warehouse_code    AS `warehouse_code`,
        a.so_no             AS `so_no`,
        a.brand_code        AS `brand_code`,
        a.model_no          AS `model_no`,
        a.qty               AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `pl_header` AS b
      ON a.pl_no=b.pl_no
      WHERE
        a.ia_no=''
    ");

    /* Delete the packing list models which is linked with the posted incoming advice. */
    array_push($queries, "
      DELETE a FROM
        `pl_model` AS a
      LEFT JOIN
        `pl_header` AS b
      ON a.pl_no=b.pl_no
      LEFT JOIN
        `ia_header` AS c
      ON a.ia_no=c.ia_no
      WHERE
        c.ia_no IS NOT NULL AND
        c.status='POSTED'
    ");

    $packingListModelValues = array();

    foreach ($postedPackingListModels as $postedPackingListModel) {
      $plNo = $postedPackingListModel["pl_no"];
      $plIndex = $postedPackingListModel["pl_index"];
      $iaNo = $postedPackingListModel["ia_no"];
      $warehouseCode = $postedPackingListModel["warehouse_code"];
      $soNo = $postedPackingListModel["so_no"];
      $brandCode = $postedPackingListModel["brand_code"];
      $modelNo = $postedPackingListModel["model_no"];
      $price = $postedPackingListModel["price"];
      $qty = $postedPackingListModel["qty"];
      $existingStockPackingListModel = array_filter($stockPackingListModels, function ($a) {
        return
          $a["warehouse_code"] == $warehouseCode &&
          $a["so_no"] == $soNo &&
          $a["brand_code"] == $brandCode &&
          $a["model_no"] == $modelNo;
      })[0];

      /* If an on hand packing list model exists, sum up the quantity with the existing one. */
      if (isset($existingStockPackingListModel)) {
        array_push($queries, "
          UPDATE
            `pl_model` AS a
          LEFT JOIN
            `pl_header` AS b
          ON a.pl_no=b.pl_no
          SET
            a.qty=a.qty+$qty
          WHERE
            b.warehouse_code=\"$warehouseCode\" AND
            a.so_no=\"$soNo\" AND
            a.brand_code=\"$brandCode\" AND
            a.model_no=\"$modelNo\"
        ");
      }

      /* Else, collect values for new packing list model insertion. */
      else {
        array_push($packingListModelValues, "
          (
            \"$plNo\",
            \"$plIndex\",
            \"\",
            \"$soNo\",
            \"$brandCode\",
            \"$modelNo\",
            \"$price\",
            \"$qty\"
          )
        ");
      }
    }

    /* Insert new packing list models. */
    if (count($packingListModelValues) > 0) {
      $packingListModelValues = join(", ", $packingListModelValues);

      array_push($queries, "
        INSERT INTO
          `pl_model`
          (
            pl_no,
            pl_index,
            ia_no,
            so_no,
            brand_code,
            model_no,
            price,
            qty
          )
        VALUES
          $packingListModelValues
      ");
    }

    return $queries;
  }
?>
