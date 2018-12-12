<?php
  $id = $_GET["id"];

  $plHeader = null;
  $plModels = array();

  /* Only populate the data if an id is given. */
  if (assigned($id)) {
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
        a.ref_no                                          AS `ref_no`,
        a.remarks                                         AS `remarks`,
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
        a.id=\"$id\"
    ")[0];

    $plModels = query("
      SELECT
        b.name            AS `brand`,
        a.model_no        AS `model_no`,
        a.so_no           AS `so_no`,
        a.price           AS `price`,
        c.cost_average    AS `cost_average`,
        SUM(a.qty)        AS `qty`
      FROM
        `pl_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `model` AS c
      ON a.model_no=c.model_no
      LEFT JOIN
        `pl_header` AS d
      ON a.pl_no=d.pl_no
      WHERE
        d.id=\"$id\"
      GROUP BY
        a.brand_code, a.model_no, a.so_no, a.price, c.cost_average
      ORDER BY
        a.brand_code ASC,
        a.model_no ASC
    ");
  }
?>
