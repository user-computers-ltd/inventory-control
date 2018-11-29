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
?>
