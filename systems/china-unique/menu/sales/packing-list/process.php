<?php
  $id = $_GET["id"];
  $plNo = $_POST["pl_no"];
  $plDate = $_POST["pl_date"];
  $refNo = $_POST["ref_no"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $paid = $_POST["paid"];

  $plHeader = null;
  $plModels = array();

  /* Only when an id is given, retrieve an existing packing list
     and possibly update the packing list. */
  if (assigned($id)) {

    /* If a form is submitted, update the packing list. */
    if (assigned($plNo) && assigned($plDate)) {
      $queries = array();

      $setValues = array(
        "pl_no=\"$plNo\"",
        "pl_date=\"$plDate\"",
        "ref_no=\"$refNo\"",
        "remarks=\"$remarks\""
      );

      if (assigned($status)) {
        array_push($setValues, "status=\"$status\"");
      }

      if (assigned($paid)) {
        array_push($setValues, "paid=\"$paid\"");
      }

      array_push($queries, "UPDATE `pl_model` AS a LEFT JOIN `pl_header` AS b ON a.pl_no=b.pl_no SET a.pl_no=\"$plNo\" WHERE b.id=\"$id\"");
      array_push($queries, "UPDATE `pl_header` SET " . join(", ", $setValues) . " WHERE id=\"$id\"");

      if ($status == "POSTED") {
        $queries = concat($queries, onPostPackingList($plNo));
      } else if ($status == "DELETED") {
        $queries = array(
          "DELETE FROM `pl_header` WHERE id=\"$id\"",
          "DELETE a FROM `pl_model` AS a LEFT JOIN `pl_header` AS b ON a.pl_no=b.pl_no WHERE b.id=\"$id\""
        );
      }

      execute($queries);

      header("Location: " . PACKING_LIST_SAVED_URL);
    }

    /* Attempt to retrieve an existing sales order. */
    $plHeader = query("
      SELECT
        a.pl_no                               AS `pl_no`,
        DATE_FORMAT(a.pl_date, '%Y-%m-%d')    AS `pl_date`,
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
        a.id=\"$id\"
    ")[0];

    $plModels = query("
      SELECT
        a.ia_no                       AS `ia_no`,
        c.id                          AS `so_id`,
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
      LEFT JOIN
        `so_header` AS c
      ON a.so_no=c.so_no
      LEFT JOIN
        `pl_header` AS d
      ON a.pl_no=d.pl_no
      WHERE
        d.id=\"$id\"
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");
  }
?>
