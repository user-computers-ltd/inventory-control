<?php
  $id = $_GET["id"];
  $doNo = $_POST["do_no"];
  $doDate = $_POST["do_date"];
  $address = $_POST["address"];
  $contact = $_POST["contact"];
  $tel = $_POST["tel"];
  $tax = $_POST["tax"];
  $warehouseCode = $_POST["warehouse_code"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $iaNos = $_POST["ia_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $soNos = $_POST["so_no"];
  $qtys = $_POST["qty"];
  $prices = $_POST["price"];

  $doHeader = null;
  $doModels = array();

  /* Only when an id is given, retrieve an existing packing list
     and possibly update the packing list. */
  if (assigned($id)) {

    /* If a form is submitted, update the packing list. */
    if (
      assigned($doNo) &&
      assigned($doDate) &&
      assigned($tax) &&
      assigned($warehouseCode) &&
      assigned($iaNos) &&
      assigned($brandCodes) &&
      assigned($modelNos) &&
      assigned($soNos) &&
      assigned($qtys) &&
      assigned($prices)
    ) {
      $queries = array();

      if ($status === "SAVED" || $status === "POSTED") {
        array_push($queries, "
          DELETE d FROM
            `sdo_model` AS a
          LEFT JOIN
            `sdo_header` AS b
          ON a.do_no=b.do_no
          LEFT JOIN
            `ia_header` AS c
          ON a.ia_no=c.ia_no
          LEFT JOIN
            `so_allotment` AS d
          ON
            a.ia_no=d.ia_no AND
            b.warehouse_code=IF(d.warehouse_code=\"\", c.warehouse_code, d.warehouse_code) AND
            a.so_no=d.so_no AND
            a.brand_code=d.brand_code AND
            a.model_no=d.model_no
          WHERE
            b.id=\"$id\"
        ");

        array_push($queries, "
          DELETE a, d FROM
            `sdo_model` AS a
          LEFT JOIN
            `sdo_header` AS b
          ON a.do_no=b.do_no
          LEFT JOIN
            `ia_header` AS c
          ON a.ia_no=c.ia_no
          LEFT JOIN
            `so_allotment` AS d
          ON
            a.ia_no=d.ia_no AND
            b.warehouse_code=IF(d.warehouse_code=\"\", c.warehouse_code, d.warehouse_code) AND
            a.so_no=d.so_no AND
            a.brand_code=d.brand_code AND
            a.model_no=d.model_no
          WHERE
            b.id=\"$id\"
        ");

        $soaValues = array();
        $sdoValues = array();

        for ($i = 0; $i < count($iaNos); $i++) {
          $iaNo = $iaNos[$i];
          $soNo = $soNos[$i];
          $brandCode = $brandCodes[$i];
          $modelNo = $modelNos[$i];
          $qty = $qtys[$i];
          $price = $prices[$i];
          $wCode = $iaNo === "" ? $warehouseCode : "";

          array_push($soaValues, "(\"$iaNo\", \"$wCode\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$qty\")");
          array_push($sdoValues, "(\"$doNo\", \"$i\", \"$iaNo\", \"$soNo\", \"$brandCode\", \"$modelNo\", \"$price\", \"$qty\")");
        }

        array_push($queries, "
          INSERT INTO
            `so_allotment`
            (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
          VALUES
          " . join(", ", $soaValues));

        array_push($queries, "
          INSERT INTO
            `sdo_model`
            (do_no, do_index, ia_no, so_no, brand_code, model_no, price, qty)
          VALUES
          " . join(", ", $sdoValues));

        array_push($queries, "
          UPDATE
            `sdo_header`
          SET
            do_no=\"$doNo\",
            do_date=\"$doDate\",
            address=\"$address\",
            contact=\"$contact\",
            tel=\"$tel\",
            tax=\"$tax\",
            remarks=\"$remarks\",
            status=\"$status\"
          WHERE
            id=\"$id\"
        ");

        if ($status === "POSTED") {
          $queries = concat($queries, onPostSalesDeliveryOrder($doNo));
        }

        execute($queries);
      } else if ($status === "DELETED") {
        execute(array(
          "DELETE a FROM `sdo_model` AS a LEFT JOIN `sdo_header` AS b ON a.do_no=b.do_no WHERE b.id=\"$id\"",
          "DELETE FROM `sdo_header` WHERE id=\"$id\""
        ));
      }

      header("Location: " . SALES_DELIVERY_ORDER_SAVED_URL);
    }

    /* Attempt to retrieve an existing sales order. */
    $doHeader = query("
      SELECT
        a.do_no                               AS `do_no`,
        c.is_provisional                      AS `is_provisional`,
        DATE_FORMAT(a.do_date, '%Y-%m-%d')    AS `do_date`,
        a.debtor_code                         AS `debtor_code`,
        IFNULL(b.english_name, 'Unknown')     AS `debtor_name`,
        a.address                             AS `debtor_address`,
        a.contact                             AS `debtor_contact`,
        a.tel                                 AS `debtor_tel`,
        a.currency_code                       AS `currency_code`,
        a.exchange_rate                       AS `exchange_rate`,
        a.warehouse_code                      AS `warehouse_code`,
        a.discount                            AS `discount`,
        a.tax                                 AS `tax`,
        a.remarks                             AS `remarks`,
        a.status                              AS `status`
      FROM
        `sdo_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      LEFT JOIN
        (
          SELECT
            x.do_no                             AS `do_no`,
            SUM(IF(y.status=\"SAVED\", 1, 0))   AS `is_provisional`
          FROM
            `sdo_model` AS x
          LEFT JOIN
            `ia_header` AS y
          ON x.ia_no=y.ia_no
          GROUP BY
            do_no
        ) AS c
      ON a.do_no=c.do_no
      WHERE
        a.id=\"$id\"
    ")[0];

    $doNo = $doHeader["do_no"];
    $debtorCode = $doHeader["debtor_code"];

    $doModels = query("
      SELECT
        a.ia_no                       AS `ia_no`,
        d.status                      AS `ia_status`,
        a.so_no                       AS `so_no`,
        a.brand_code                  AS `brand_code`,
        a.model_no                    AS `model_no`,
        a.price                       AS `price`,
        a.qty                         AS `qty`,
        c.occurrence                  AS `occurrence`
      FROM
        `sdo_model` AS a
      LEFT JOIN
        `sdo_header` AS b
      ON a.do_no=b.do_no
      LEFT JOIN
        `so_model` AS c
      ON a.so_no=c.so_no AND a.brand_code=c.brand_code AND a.model_no=c.model_no
      LEFT JOIN
        `ia_header` AS d
      ON a.ia_no=d.ia_no
      WHERE
        b.id=\"$id\"
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");

    $brands = query("SELECT code, name FROM `brand`");

    $stockModels = query("
      SELECT
        a.brand_code                          AS `brand_code`,
        a.model_no                            AS `model_no`,
        a.qty                                 AS `qty_stock`,
        IFNULL(c.qty_allotted, 0)             AS `qty_stock_allotted`,
        b.so_nos                              AS `so_nos`,
        b.qty_outstandings                    AS `qty_outstandings`,
        b.qty_allotteds                       AS `qty_so_allotteds`,
        b.prices                              AS `prices`
      FROM
        `stock` AS a
      LEFT JOIN
        (
          SELECT
            sm.brand_code                               AS `brand_code`,
            sm.model_no                                 AS `model_no`,
            GROUP_CONCAT(sm.so_no)                      AS `so_nos`,
            GROUP_CONCAT(IFNULL(sm.qty_outstanding, 0)) AS `qty_outstandings`,
            GROUP_CONCAT(IFNULL(sa.qty_allotted, 0))    AS `qty_allotteds`,
            GROUP_CONCAT(sm.price)                      AS `prices`
          FROM
            `so_model` AS sm
          LEFT JOIN
            `so_header` AS sh
          ON sm.so_no=sh.so_no
          LEFT JOIN
            (
              SELECT
                x.brand_code   AS `brand_code`,
                x.model_no     AS `model_no`,
                x.so_no        AS `so_no`,
                SUM(x.qty)     AS `qty_allotted`
              FROM
                `so_allotment` AS x
              LEFT JOIN
                `so_header` AS y
              ON x.so_no=y.so_no
              LEFT JOIN
                (
                  SELECT
                    sdm.do_no             AS `do_no`,
                    sdm.ia_no             AS `ia_no`,
                    sdm.so_no             AS `so_no`,
                    sdm.brand_code        AS `brand_code`,
                    sdm.model_no          AS `model_no`
                  FROM
                    `sdo_model` AS sdm
                  LEFT JOIN
                    `sdo_header` AS sdh
                  ON sdm.do_no=sdh.do_no
                  WHERE
                    sdh.status=\"SAVED\"
                ) AS z
              ON
                x.brand_code=z.brand_code AND
                x.model_no=z.model_no AND
                x.so_no=z.so_no AND
                x.ia_no=z.ia_no
              WHERE
                z.do_no!=\"$doNo\" OR x.ia_no!=\"\"
              GROUP BY
                x.brand_code, x.model_no, x.so_no
            ) AS sa
          ON sm.brand_code=sa.brand_code AND sm.model_no=sa.model_no AND sm.so_no=sa.so_no
          WHERE
            IFNULL(sm.qty_outstanding, 0) - IFNULL(sa.qty_allotted, 0) > 0 AND
            sh.debtor_code=\"$debtorCode\" AND
            sh.status=\"CONFIRMED\"
          GROUP BY
            sm.brand_code, sm.model_no
        ) AS b
      ON a.brand_code=b.brand_code AND a.model_no=b.model_no
      LEFT JOIN
        (
          SELECT
            x.warehouse_code                      AS `warehouse_code`,
            x.brand_code                          AS `brand_code`,
            x.model_no                            AS `model_no`,
            SUM(x.qty)                            AS `qty_allotted`
          FROM
            `so_allotment` AS x
          LEFT JOIN
            `so_header` AS y
          ON x.so_no=y.so_no
          LEFT JOIN
            (
              SELECT
                sdm.do_no             AS `do_no`,
                sdm.ia_no             AS `ia_no`,
                sdm.so_no             AS `so_no`,
                sdm.brand_code        AS `brand_code`,
                sdm.model_no          AS `model_no`
              FROM
                `sdo_model` AS sdm
              LEFT JOIN
                `sdo_header` AS sdh
              ON sdm.do_no=sdh.do_no
              WHERE
                sdh.status=\"SAVED\"
            ) AS z
          ON
            x.brand_code=z.brand_code AND
            x.model_no=z.model_no AND
            x.so_no=z.so_no AND
            x.ia_no=z.ia_no
          WHERE
            x.warehouse_code!=\"\" AND
            (y.debtor_code!=\"" . $doHeader["debtor_code"] . "\" OR
            (y.debtor_code=\"" . $doHeader["debtor_code"] . "\" AND (z.do_no IS NULL OR z.do_no!=\"" . $doHeader["do_no"] . "\")))
          GROUP BY
            x.warehouse_code, x.brand_code, x.model_no
        ) AS c
      ON
        a.warehouse_code=c.warehouse_code AND
        a.brand_code=c.brand_code AND
        a.model_no=c.model_no
      WHERE
        a.qty - IFNULL(c.qty_allotted, 0) > 0 AND
        a.warehouse_code=\"" . $doHeader["warehouse_code"] . "\" AND
        b.so_nos IS NOT NULL
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");

    $results = query("
      SELECT
        a.ia_no                               AS `ia_no`,
        a.brand_code                          AS `brand_code`,
        a.model_no                            AS `model_no`,
        a.qty                                 AS `qty_ia`,
        IFNULL(c.qty_allotted, 0)             AS `qty_ia_allotted`,
        b.so_nos                              AS `so_nos`,
        b.qty_outstandings                    AS `qty_outstandings`,
        b.qty_allotteds                       AS `qty_so_allotteds`,
        b.prices                              AS `prices`
      FROM
        `ia_model` AS a
      LEFT JOIN
        `ia_header` AS ah
      ON a.ia_no=ah.ia_no
      LEFT JOIN
        (SELECT
          sm.brand_code                               AS `brand_code`,
          sm.model_no                                 AS `model_no`,
          GROUP_CONCAT(sm.so_no)                      AS `so_nos`,
          GROUP_CONCAT(IFNULL(sm.qty_outstanding, 0)) AS `qty_outstandings`,
          GROUP_CONCAT(IFNULL(sa.qty_allotted, 0))    AS `qty_allotteds`,
          GROUP_CONCAT(sm.price)                      AS `prices`
        FROM
          `so_model` AS sm
        LEFT JOIN
          `so_header` AS sh
        ON sm.so_no=sh.so_no
        LEFT JOIN
          (SELECT
            x.brand_code   AS `brand_code`,
            x.model_no     AS `model_no`,
            x.so_no        AS `so_no`,
            SUM(x.qty)     AS `qty_allotted`
          FROM
            `so_allotment` AS x
          LEFT JOIN
            `so_header` AS y
          ON x.so_no=y.so_no
          LEFT JOIN
            (
              SELECT
                sdm.do_no             AS `do_no`,
                sdm.ia_no             AS `ia_no`,
                sdm.so_no             AS `so_no`,
                sdm.brand_code        AS `brand_code`,
                sdm.model_no          AS `model_no`
              FROM
                `sdo_model` AS sdm
              LEFT JOIN
                `sdo_header` AS sdh
              ON sdm.do_no=sdh.do_no
              WHERE
                sdh.status=\"SAVED\"
            ) AS z
          ON
            x.brand_code=z.brand_code AND
            x.model_no=z.model_no AND
            x.so_no=z.so_no AND
            x.ia_no=z.ia_no
          WHERE
            x.ia_no=\"\" OR z.do_no!=\"" . $doHeader["do_no"] . "\"
          GROUP BY
            x.brand_code, x.model_no, x.so_no) AS sa
        ON sm.brand_code=sa.brand_code AND sm.model_no=sa.model_no AND sm.so_no=sa.so_no
        WHERE
          IFNULL(sm.qty_outstanding, 0) - IFNULL(sa.qty_allotted, 0) > 0 AND
          sh.debtor_code=\"" . $doHeader["debtor_code"] . "\" AND
          sh.status=\"CONFIRMED\"
        GROUP BY
          sm.brand_code, sm.model_no
        ) AS b
      ON a.brand_code=b.brand_code AND a.model_no=b.model_no
      LEFT JOIN
        (SELECT
          x.ia_no                               AS `ia_no`,
          x.brand_code                          AS `brand_code`,
          x.model_no                            AS `model_no`,
          SUM(x.qty)                            AS `qty_allotted`
        FROM
          `so_allotment` AS x
        LEFT JOIN
          `so_header` AS y
        ON x.so_no=y.so_no
        LEFT JOIN
          (
            SELECT
              sdm.do_no             AS `do_no`,
              sdm.ia_no             AS `ia_no`,
              sdm.so_no             AS `so_no`,
              sdm.brand_code        AS `brand_code`,
              sdm.model_no          AS `model_no`
            FROM
              `sdo_model` AS sdm
            LEFT JOIN
              `sdo_header` AS sdh
            ON sdm.do_no=sdh.do_no
            WHERE
              sdh.status=\"SAVED\"
          ) AS z
        ON
          x.brand_code=z.brand_code AND
          x.model_no=z.model_no AND
          x.so_no=z.so_no AND
          x.ia_no=z.ia_no
        WHERE
          x.ia_no!=\"\" AND
          (y.debtor_code!=\"" . $doHeader["debtor_code"] . "\" OR
          (y.debtor_code=\"" . $doHeader["debtor_code"] . "\" AND (z.do_no IS NULL OR z.do_no!=\"" . $doHeader["do_no"] . "\")))
        GROUP BY
          x.ia_no, x.brand_code, x.model_no) AS c
      ON
        a.ia_no=c.ia_no AND
        a.brand_code=c.brand_code AND
        a.model_no=c.model_no
      WHERE
        (ah.status=\"SAVED\" OR ah.status=\"DO\") AND
        a.qty - IFNULL(c.qty_allotted, 0) > 0 AND
        b.so_nos IS NOT NULL
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");

    $iaVouchers = array();
    $iaNos = array();

    foreach ($results as $iaVoucher) {
      $iaNo = $iaVoucher["ia_no"];

      $arrayPointer = &$iaVouchers;

      if (!isset($arrayPointer[$iaNo])) {
        $arrayPointer[$iaNo] = array();
        array_push($iaNos, $iaNo);
      }

      $arrayPointer = &$arrayPointer[$iaNo];

      array_push($arrayPointer, $iaVoucher);
    }
  }
?>
