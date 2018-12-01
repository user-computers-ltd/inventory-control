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

    if (assigned($status) || assigned($paid)) {
      $queries = array();
      $directionLocation = "";

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

      array_push($queries, "
        UPDATE
          `pl_header`
        SET
          " . join(", ", $setValues) . "
        WHERE
          pl_no=\"$plNo\"
      ");

      if ($status == "POSTED") {
        $queries = concat($queries, postPackingList($plNo));
        $directionLocation = PACKING_LIST_POSTED_URL;
      } else if ($status == "DELETED") {
        $queries = array(
          "DELETE FROM `pl_header` WHERE pl_no=\"$plNo\"",
          "DELETE FROM `pl_model` WHERE pl_no=\"$plNo\""
        );
        $directionLocation = PACKING_LIST_SAVED_URL;
      }

      execute($queries);

      if (assigned($directionLocation)) {
        header("Location: $directionLocation");
      }
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
        a.ia_no                       AS `ia_no`,
        a.so_no                       AS `so_no`,
        b.name                        AS `brand`,
        a.model_no                    AS `model_no`,
        a.price                       AS `price`,
        a.qty                         AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      WHERE
        a.pl_no=\"$plNo\"
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");
  }
?>
