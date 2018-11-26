<?php
  $plNo = $_GET["pl_no"];

  $refNo = $_POST["ref_no"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $paid = $_POST["paid"];

  $date = date("Y-m-d");
  $plHeader = null;
  $plModels = array();

  /* Only populate the data if an order number is given. */
  if (assigned($plNo)) {

    if (assigned($refNo) && assigned($remarks)) {
      $queries = array();

      $setValues = array(
        "ref_no=\"$refNo\"",
        "remarks=\"$remarks\"",
        "pl_date=\"$date\""
      );

      if (assigned($status)) {
        array_push($setValues, "status=\"$status\"");
      }

      if (assigned($paid)) {
        array_push($setValues, "paid=\"$paid\"");
      }

      $setValues = join(", ", $setValues);
      array_push($queries, "UPDATE `pl_header` SET $setValues WHERE pl_no=\"$plNo\"");

      if (assigned($status) && $status == "POSTED") {

        $plModels = query("
          SELECT
            a.pl_no           AS `pl_no`,
            b.debtor_code     AS `debtor_code`,
            b.currency_code   AS `currency_code`,
            b.exchange_rate   AS `exchange_rate`,
            b.discount        AS `discount`,
            b.tax             AS `tax`,
            b.warehouse_code  AS `warehouse_code`,
            a.so_no           AS `so_no`,
            a.brand_code      AS `brand_code`,
            a.model_no        AS `model_no`,
            a.price           AS `price`,
            SUM(a.qty)        AS `qty`
          FROM
            `pl_model` AS a
          LEFT JOIN
            `pl_header` AS b
          ON
            a.pl_no=b.pl_no
          WHERE
            a.pl_no=\"$plNo\"
          GROUP BY
            a.so_no, b.name, a.model_no, a.price
        ");

        $allotmentRefs = query("SELECT `allotment_id` FROM `pl_model` WHERE pl_no=\"$plNo\"");

        $transactionValues = array();

        foreach ($plModels as $plModel) {
          $plNo = $plModel["pl_no"];
          $debtorCode = $plModel["debtor_code"];
          $currencyCode = $plModel["currency_code"];
          $exchangeRate = $plModel["exchange_rate"];
          $discount = $plModel["discount"];
          $tax = $plModel["tax"];
          $warehouseCode = $plModel["warehouse_code"];
          $soNo = $plModel["so_no"];
          $brandCode = $plModel["brand_code"];
          $modelNo = $plModel["model_no"];
          $price = $plModel["price"];
          $qty = $plModel["qty"];

          /* Update sales order outsanding quantities. */
          array_push($queries, "
            UPDATE
              `so_model`
            SET
              qty=qty - $qty
            WHERE
              so_no=\"$soNo\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
          ");

          array_push($transactionValues, "
            (
              \"$plNo\",
              \"S2\",
              \"$date\",
              \"$debtorCode\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$brandCode\",
              \"$modelNo\",
              \"$price\",
              \"$qty\",
              \"$discount\",
              \"$tax\",
              \"$warehouseCode\"
            )
          ");
        }

        $transactionValues = join(", ", $transactionValues);

        /* Insert corresponding transactions. */
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
            price,
            qty,
            discount,
            tax,
            warehouse_code
          )
          VALUES
            $transactionValues
        ");

        /* Remove corresponding allotments. */
        foreach ($allotmentRefs as $allotmentRef) {
          $allotmentId = $allotmentRef["allotment_id"];

          array_push($queries, "DELETE FROM `so_allotment` WHERE allotment_id=\"$allotmentId\"");
        }
      }

      execute($queries);
    }

    /* Attempt to retrieve an existing sales order. */
    $plHeader = query("
      SELECT
        a.pl_no                               AS `pl_no`,
        DATE_FORMAT(a.pl_date, '%d-%m-%Y')    AS `date`,
        a.debtor_code                         AS `debtor_code`,
        IFNULL(b.english_name, 'Unknown')     AS `debtor_name`,
        IFNULL(b.bill_address, 'Unknown')     AS `debtor_address`,
        IFNULL(b.contact, 'Unknown')          AS `debtor_contact`,
        a.currency_code                       AS `currency_code`,
        a.exchange_rate                       AS `exchange_rate`,
        a.discount                            AS `discount`,
        a.tax                                 AS `tax`,
        a.ref_no                              AS `ref_no`,
        a.remarks                             AS `remarks`,
        a.status                              AS `status`,
        a.paid                                AS `paid`
      FROM
        `pl_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      WHERE
        a.pl_no=\"$plNo\"
    ")[0];

    $plModels = query("
      SELECT
        a.so_no       AS `so_no`,
        b.name        AS `brand`,
        a.model_no    AS `model_no`,
        a.price       AS `price`,
        SUM(a.qty)    AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      WHERE
        a.pl_no=\"$plNo\"
      GROUP BY
        a.so_no, b.name, a.model_no, a.price
    ");
  }
?>
