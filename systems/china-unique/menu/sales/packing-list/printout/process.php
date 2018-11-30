<?php
  $plNo = $_GET["pl_no"];

  $plHeader = null;
  $plModels = array();

  /* Only populate the data if an order number is given. */
  if (assigned($plNo)) {
    $plHeader = query("
      SELECT
        a.pl_no                                           AS `pl_no`,
        DATE_FORMAT(a.pl_date, '%d-%m-%Y')                AS `date`,
        IFNULL(b.english_name, 'Unknown')                 AS `customer_name`,
        b.bill_address                                    AS `customer_address`,
        b.contact                                         AS `customer_contact`,
        b.tel                                             AS `customer_tel`,
        CONCAT(a.currency_code, ' @ ', a.exchange_rate)   AS `currency`,
        a.discount                                        AS `discount`,
        a.tax                                             AS `tax`,
        c.name                                            AS `warehouse`,
        a.status                                          AS `status`
      FROM
        `pl_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      LEFT JOIN
        `warehouse` AS c
      ON a.warehouse_code=c.code
      WHERE
        a.pl_no=\"$plNo\"
    ")[0];

    $plModels = query("
      SELECT
        b.name            AS `brand`,
        a.model_no        AS `model_no`,
        a.so_no           AS `so_no`,
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