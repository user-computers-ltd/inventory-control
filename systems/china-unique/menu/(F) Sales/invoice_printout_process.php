<?php
  $piNo = $_GET["pi_no"];

  $piHeader = null;
  $piModels = array();

  /* Only populate the data if an order number is given. */
  if (assigned($piNo)) {

    /* Attempt to retrieve an existing sales order. */
    $piHeader = query("
      SELECT
        a.pi_no                                                           AS `pi_no`,
        DATE_FORMAT(a.pi_date, '%d-%m-%Y')                                AS `date`,
        a.debtor_code                                                     AS `debtor_code`,
        IFNULL(b.english_name, 'Unknown')                                 AS `debtor_name`,
        IFNULL(b.bill_address, 'Unknown')                                 AS `debtor_address`,
        IFNULL(b.contact, 'Unknown')                                      AS `debtor_contact`,
        a.currency_code                                                   AS `currency_code`,
        a.exchange_rate                                                   AS `exchange_rate`,
        a.discount                                                        AS `discount`,
        a.tax                                                             AS `tax`,
        a.ref_no                                                          AS `ref_no`,
        a.remarks                                                         AS `remarks`,
        a.status                                                          AS `status`
      FROM
        `pi_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      WHERE
        a.pi_no=\"$piNo\"
    ")[0];

    $piModels = query("
      SELECT
        b.name                                  AS `brand`,
        a.model_no                              AS `model_no`,
        a.price                                 AS `price`,
        SUM(a.qty)                              AS `qty`
      FROM
        `pi_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      WHERE
        a.pi_no=\"$piNo\"
      GROUP BY
        b.name, a.model_no, a.price
    ");
  }
?>
