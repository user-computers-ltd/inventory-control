<?php
  $id = $_GET["id"];
  $doNo = $_POST["do_no"];
  $doDate = $_POST["do_date"];
  $debtorCode = $_POST["debtor_code"];
  $address = $_POST["address"];
  $contact = $_POST["contact"];
  $tel = $_POST["tel"];
  $tax = $_POST["tax"];
  $warehouseCode = $_POST["warehouse_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $iaNos = $_POST["ia_no"];
  $soNos = $_POST["so_no"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];
  $prices = $_POST["price"];
  $deleteAllotments = $_POST["delete_allotments"];
  $editing = assigned($_POST["editing"]);

  $doHeader = null;
  $doModels = array();

  /* If a form is submitted, update the packing list. */
  if (
    $editing === false &&
    assigned($doNo) &&
    assigned($doDate) &&
    assigned($debtorCode) &&
    assigned($address) &&
    assigned($contact) &&
    assigned($tel) &&
    assigned($tax) &&
    assigned($warehouseCode) &&
    assigned($currencyCode) &&
    assigned($exchangeRate) &&
    assigned($discount) &&
    assigned($iaNos) &&
    assigned($brandCodes) &&
    assigned($modelNos) &&
    assigned($soNos) &&
    assigned($qtys) &&
    assigned($prices)
  ) {
    $queries = array();

    /* If an id is given, delete all models and allotments for this delivery order first. */
    if (assigned($id)) {
      $delete = isset($deleteAllotments) || $status === "SAVED" || $status === "POSTED";

      array_push($queries, "
        DELETE a, b" . ($delete ? ", d" : "") . " FROM
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
    }

    if ($status === "SAVED" || $status === "POSTED") {
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

      // Re-insert all models and allotments for this delivery order.
      if (assigned($id)) {
        array_push($queries, "
          INSERT INTO
            `so_allotment`
            (ia_no, warehouse_code, so_no, brand_code, model_no, qty)
          VALUES
          " . join(", ", $soaValues));
      }

      array_push($queries, "
        INSERT INTO
          `sdo_model`
          (do_no, do_index, ia_no, so_no, brand_code, model_no, price, qty)
        VALUES
        " . join(", ", $sdoValues));

      array_push($queries, "
        INSERT INTO
          `sdo_header`
            (
              do_no,
              do_date,
              debtor_code,
              address,
              contact,
              tel,
              currency_code,
              exchange_rate,
              discount,
              tax,
              warehouse_code,
              remarks,
              status
            )
          VALUES
            (
              \"$doNo\",
              \"$doDate\",
              \"$debtorCode\",
              \"$address\",
              \"$contact\",
              \"$tel\",
              \"$currencyCode\",
              \"$exchangeRate\",
              \"$discount\",
              \"$tax\",
              \"$warehouseCode\",
              \"$remarks\",
              \"$status\"
            )
      ");
    }

    execute($queries);

    if (assigned($status)) {
      if ($status === "POSTED") {
        execute(onPostSalesDeliveryOrder($doNo));
      }

      header("Location: " . SALES_DELIVERY_ORDER_SAVED_URL);
      exit();
    }
  }

  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  /* When an id is given, retrieve an existing packing list. */
  if (assigned($id) && $editing === false) {
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

    if (isset($doHeader)) {
      $doNo = $doHeader["do_no"];
      $isProvisional = $doHeader["is_provisional"];
      $doDate = $doHeader["do_date"];
      $debtorCode = $doHeader["debtor_code"];
      $debtor = "$debtorCode - " . $doHeader["debtor_name"];
      $address = $doHeader["debtor_address"];
      $contact = $doHeader["debtor_contact"];
      $tel = $doHeader["debtor_tel"];
      $currencyCode = $doHeader["currency_code"];
      $exchangeRate = $doHeader["exchange_rate"];
      $warehouseCode = $doHeader["warehouse_code"];
      $discount = $doHeader["discount"];
      $tax = $doHeader["tax"];
      $remarks = $doHeader["remarks"];
      $status = $doHeader["status"];

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
    }
  }

  /* Else, initialize values for a new sales delivery order. */
  else {
    $debtorResult = query("SELECT factory_address, contact, tel, english_name FROM `debtor` WHERE code=\"$debtorCode\"")[0];

    $doNo = assigned($doNo) ? $doNo : "DO" . date("YmdHis");
    $doDate = assigned($doDate) ? $doDate : date("Y-m-d");
    $debtor = "$debtorCode - " . $debtorResult["english_name"];
    $address = $debtorResult["factory_address"];
    $contact = $debtorResult["contact"];
    $tel = $debtorResult["tel"];
    $currencyCode = assigned($currencyCode) ? $currencyCode : COMPANY_CURRENCY;
    $exchangeRate = assigned($exchangeRate) ? $exchangeRate : $currencies[$currencyCode];
    $discount = assigned($discount) ? $discount : 0;
    $tax = assigned($tax) ? $tax : COMPANY_TAX;
    $warehouseCode = assigned($warehouseCode) ? $warehouseCode : "";
    $remarks = assigned($remarks) ? $remarks : "";
    $status = assigned($status) ? $status : "DRAFT";
    $isProvisional = 0;

    $doModels = array();

    if (assigned($iaNos) && assigned($soNos) && assigned($brandCodes) && assigned($modelNos) && assigned($qtys) && assigned($prices)) {

      $isProvisional = query("
        SELECT
          SUM(IF(status=\"SAVED\", 1, 0)) AS `is_provisional`
        FROM
          `ia_header`
        WHERE " . join(" OR ", array_map(function ($i) { return "ia_no=\"$i\""; }, $iaNos)) . "
      ")[0]["is_provisional"];

      for ($i = 0; $i < count($brandCodes); $i++) {
        $iaNo = $iaNos[$i];
        $soNo = $soNos[$i];
        $brandCode = $brandCodes[$i];
        $modelNo = $modelNos[$i];
        $qty = $qtys[$i];
        $price = assigned($prices) && assigned($prices[$i]) ? $prices[$i] : "";

        array_push($doModels, array(
          "ia_no"         => $iaNo,
          "so_no"         => $soNo,
          "brand_code"    => $brandCode,
          "model_no"      => $modelNo,
          "price"         => $price,
          "qty"           => $qty,
          "occurrence"    => $qty
        ));
      }
    }
  }

  $creating = assigned($id) === false;

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
                  sdh.id                AS `do_id`,
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
              z.do_id!=\"$id\" OR x.ia_no!=\"\"
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
              sdh.id                AS `do_id`,
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
          (y.debtor_code!=\"$debtorCode\" "
          . ($creating ? "" : " OR
            (y.debtor_code=\"$debtorCode\" AND (z.do_id IS NULL OR z.do_id!=\"$id\"))
          ") . "
          )
        GROUP BY
          x.warehouse_code, x.brand_code, x.model_no
      ) AS c
    ON
      a.warehouse_code=c.warehouse_code AND
      a.brand_code=c.brand_code AND
      a.model_no=c.model_no
    WHERE
      a.qty - IFNULL(c.qty_allotted, 0) > 0 AND
      a.warehouse_code=\"$warehouseCode\" AND
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
              sdh.id                AS `do_id`,
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
          x.ia_no=\"\" OR z.do_id!=\"$id\"
        GROUP BY
          x.brand_code, x.model_no, x.so_no) AS sa
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
            sdh.id                AS `do_id`,
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
        (y.debtor_code!=\"$debtorCode\" "
        . ($creating ? "" : " OR
          (y.debtor_code=\"$debtorCode\" AND (z.do_id IS NULL OR z.do_id!=\"$id\"))
        ") . "
        )
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
  $debtorIaNos = array();

  foreach ($results as $iaVoucher) {
    $iaNo = $iaVoucher["ia_no"];

    $arrayPointer = &$iaVouchers;

    if (!isset($arrayPointer[$iaNo])) {
      $arrayPointer[$iaNo] = array();
      array_push($debtorIaNos, $iaNo);
    }

    $arrayPointer = &$arrayPointer[$iaNo];

    array_push($arrayPointer, $iaVoucher);
  }
?>
