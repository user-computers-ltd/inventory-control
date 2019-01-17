<?php
  $id = $_GET["id"];
  $doNo = $_POST["do_no"];
  $doDate = $_POST["do_date"];
  $address = $_POST["address"];
  $contact = $_POST["contact"];
  $tel = $_POST["tel"];
  $invoiceNo = $_POST["invoice_no"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];

  $doHeader = null;
  $doModels = array();

  /* Only when an id is given, retrieve an existing packing list
     and possibly update the packing list. */
  if (assigned($id)) {

    /* If a form is submitted, update the packing list. */
    if (assigned($doNo) && assigned($doDate)) {
      $queries = array();

      $setValues = array(
        "do_no=\"$doNo\"",
        "do_date=\"$doDate\"",
        "address=\"$address\"",
        "contact=\"$contact\"",
        "tel=\"$tel\"",
        "invoice_no=\"$invoiceNo\"",
        "remarks=\"$remarks\"",
        "status=\"$status\""
      );

      array_push($queries, "UPDATE `sdo_model` AS a LEFT JOIN `sdo_header` AS b ON a.do_no=b.do_no SET a.do_no=\"$doNo\" WHERE b.id=\"$id\"");
      array_push($queries, "UPDATE `sdo_header` SET " . join(", ", $setValues) . " WHERE id=\"$id\"");

      if ($status == "POSTED") {
        $queries = concat($queries, onPostSalesDeliveryOrder($doNo));
      } else if ($status == "DELETED") {
        $queries = array(
          "DELETE a FROM `sdo_model` AS a LEFT JOIN `sdo_header` AS b ON a.do_no=b.do_no WHERE b.id=\"$id\"",
          "DELETE FROM `sdo_header` WHERE id=\"$id\""
        );
      }

      execute($queries);

      header("Location: " . SALES_DELIVERY_ORDER_SAVED_URL);
    }

    /* Attempt to retrieve an existing sales order. */
    $doHeader = query("
      SELECT
        a.do_no                               AS `do_no`,
        DATE_FORMAT(a.do_date, '%Y-%m-%d')    AS `do_date`,
        a.debtor_code                         AS `debtor_code`,
        IFNULL(b.english_name, 'Unknown')     AS `debtor_name`,
        a.address                             AS `debtor_address`,
        a.contact                             AS `debtor_contact`,
        a.tel                                 AS `debtor_tel`,
        a.currency_code                       AS `currency_code`,
        a.exchange_rate                       AS `exchange_rate`,
        a.discount                            AS `discount`,
        a.tax                                 AS `tax`,
        a.invoice_no                          AS `invoice_no`,
        a.remarks                             AS `remarks`,
        a.status                              AS `status`
      FROM
        `sdo_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      WHERE
        a.id=\"$id\"
    ")[0];

    $doModels = query("
      SELECT
        a.ia_no                       AS `ia_no`,
        c.id                          AS `so_id`,
        a.so_no                       AS `so_no`,
        b.name                        AS `brand`,
        a.model_no                    AS `model_no`,
        a.price                       AS `price`,
        a.qty                         AS `qty`,
        e.occurrence                  AS `occurrence`
      FROM
        `sdo_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `so_header` AS c
      ON a.so_no=c.so_no
      LEFT JOIN
        `sdo_header` AS d
      ON a.do_no=d.do_no
      LEFT JOIN
        `so_model` AS e
      ON a.so_no=e.so_no AND a.brand_code=e.brand_code AND a.model_no=e.model_no
      WHERE
        d.id=\"$id\"
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");
  }
?>
