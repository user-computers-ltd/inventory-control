<?php
  include_once "config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  function postTransactions(
    $headerNo,
    $transactionCode,
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
    $date = date("Y-m-d");
    $queries = array();
    $transactionValues = array();

    for ($i = 0; $i < count($brandCodes); $i++) {
      $brandCode = $brandCodes[$i];
      $modelNo = $modelNos[$i];
      $price = $prices[$i];
      $qty = $qtys[$i];

      $costAverage = query("
        SELECT
          cost_average
        FROM
          `model`
        WHERE
          brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
      ")[0]["cost_average"];

      $qtyOnHand = query("
        SELECT
          SUM(qty) AS `qty_on_hand`
        FROM
          `stock`
        GROUP BY
          model_no, brand_code
      ")[0]["qty_on_hand"];

      $stockInWarehouse = query("
        SELECT
          SUM(qty) AS `qty_in_warehouse`
        FROM
          `stock`
        WHERE
          warechouse_code=\"$warehouseCode\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
      ")[0]["qty_in_warehouse"];

      $stockChange = strpos($transactionCode, "R") === 0 ? $qty : -$qty;

      /* Update model average cost. */
      if ($transactionCode == "R1" || $transactionCode == "R2") {
        $discountFactor = (100 - $discount) / 100;
        $taxFactor = (100 + $tax) / 100;
        $cost = $price * $discountFactor / $taxFactor * $exchangeRate;
        $newCostAverage = ($costAverage * $qtyOnHand + $cost * $qty) / ($qtyOnHand + $qty);

        array_push($queries, "
          UPDATE
            `model`
          SET
            cost_average=\"$newCostAverage\"
          WHERE
            brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
        ");
      }

      /* Update or insert stock. */
      if ($stockInWarehouse == NULL) {
        array_push($queries, "
          INSERT INTO
            `stock`
            (warehouse_code, brand_code, model_no, qty)
          VALUES
            (\"$warehouseCode\", \"$brandCode\", \"$modelNo\", \"$stockChange\")
        ");
      } else {
        $newQty = $stockInWarehouse + $stockChange;
        array_push($queries, "
          UPDATE
            `stock`
          SET
            qty=\"$newQty\"
          WHERE
            warechouse_code=\"$warehouseCode\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
        ");
      }

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
          \"$costAverage\",
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

    execute($queries);
  }

  function postPackingList($plNo) {
    $date = date("Y-m-d");
    $queries = array();

    $plHeader = query("
      SELECT
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
        so_no, name, model_no, price
    ");

    $allotmentRefs = query("
      SELECT
        ia_no             AS `ia_no`,
        warehouse_code    AS `warehouse_code`,
        so_no             AS `so_no`,
        brand_code        AS `brand_code`,
        model_no          AS `model_no`
      FROM
        `pl_model`
      WHERE
        pl_no=\"$plNo\"");

    $transactionRefs = query("
      SELECT
        brand_code      AS `brand_code`,
        model_no        AS `model_no`,
        price           AS `price`,
        SUM(qty)        AS `qty`
      FROM
        `pl_model`
      WHERE
        pl_no=\"$plNo\"
      GROUP BY
        brand_code, model_no, price
    ");

    foreach ($soModelRefs as $soModelRef) {
      $soNo = $soModelRef["so_no"];
      $brandCode = $soModelRef["brand_code"];
      $modelNo = $soModelRef["model_no"];
      $price = $soModelRef["price"];
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
      $iaNo = $allotmentRef["ia_no"];
      $warehouseCode = $allotmentRef["warehouse_code"];
      $soNo = $allotmentRef["so_no"];
      $brandCode = $allotmentRef["brand_code"];
      $modelNo = $allotmentRef["model_no"];

      array_push($queries, "
        DELETE FROM
          `so_allotment`
        WHERE
          ia_no=\"$iaNo\" AND
          warehouse_code=\"$warehouseCode\" AND
          so_no=\"$soNo\" AND
          brand_code=\"$brandCode\" AND
          model_no=\"$modelNo\"
      ");
    }

    execute($queries);

    /* Insert corresponding transactions. */
    $brandCodes = array();
    $modelNos = array();
    $prices = array();
    $qtys = array();

    foreach ($allotmentRefs as $allotmentRef) {
      $brandCode = $allotmentRef["brand_code"];
      $modelNo = $allotmentRef["model_no"];
      $price = $allotmentRef["price"];
      $qty = $allotmentRef["qty"];

      array_push($brandCodes, $brandCode);
      array_push($modelNos, $modelNo);
      array_push($prices, $price);
      array_push($qtys, $qty);
    }

    postTransactions(
      $plNo,
      "S2",
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
  }

  function processDOStockIn() {
    updatePostedIncomingAllotments();
    updateOnHandPackingListModels();
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

      /* Else, create a new stock allotment. */
      else {
        array_push($queries, "
          INSERT INTO
            `so_allotment`
            (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
          VALUES
            (\"\", \"$warehouseCode\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")
        ");
      }
    }

    execute($queries);
  }

  function updateOnHandPackingListModels() {
    $queries = array();

    $postedPackingListModels = query("
      SELECT
        a.pl_no             AS `pl_no`,
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

    foreach ($postedPackingListModels as $postedPackingListModel) {
      $plNo = $postedPackingListModel["pl_no"];
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

      /* Else, create a new packing list model. */
      else {
        array_push($queries, "
          INSERT INTO
            `pl_model`
            (pl_no, ia_no, so_no, brand_code, model_no, price, qty)
          VALUES
            (\"$plNo\", \"\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")
        ");
      }
    }

    execute($queries);
  }
?>
