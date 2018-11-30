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
            so_no             AS `so_no`,
            brand_code        AS `brand_code`,
            model_no          AS `model_no`,
            warehouse_code    AS `warehouse_code`
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
          $soNo = $allotmentRef["so_no"];
          $brandCode = $allotmentRef["brand_code"];
          $modelNo = $allotmentRef["model_no"];

          array_push($queries, "
            DELETE FROM
              `so_allotment`
            WHERE
              ia_no=\"$iaNo\" AND so_no=\"$soNo\" AND brand_code=\"$brandCode\" AND model_no=\"$modelNo\"
          ");
        }

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
        a.so_no           AS `so_no`,
        b.name            AS `brand`,
        a.model_no        AS `model_no`,
        a.price           AS `price`,
        SUM(a.qty)        AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      WHERE
        a.pl_no=\"$plNo\"
      GROUP BY
        a.brand_code, a.model_no, a.so_no, a.price
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");
  }
?>
