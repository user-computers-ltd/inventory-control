<?php
  $ids = $_GET["id"];

  $doHeaders = array();
  $doModelList = array();
  $doModels = array();

  /* Only populate the data if an id is given. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "a.id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $doHeaders = query("
      SELECT
        a.do_no                                           AS `do_no`,
        DATE_FORMAT(a.do_date, '%d-%m-%Y')                AS `date`,
        a.debtor_code                                     AS `client_code`,
        IFNULL(b.english_name, 'Unknown')                 AS `client_name`,
        a.address                                         AS `client_address`,
        a.contact                                         AS `client_contact`,
        a.tel                                             AS `client_tel`,
        CONCAT(a.currency_code, ' @ ', a.exchange_rate)   AS `currency`,
        a.discount                                        AS `discount`,
        a.tax                                             AS `tax`,
        c.name                                            AS `warehouse`,
        a.invoice_no                                      AS `invoice_no`,
        a.remarks                                         AS `remarks`,
        a.status                                          AS `status`
      FROM
        `sdo_header` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.debtor_code=b.code
      LEFT JOIN
        `warehouse` AS c
      ON a.warehouse_code=c.code
      WHERE
        $headerWhereClause
    ");

    $doModelList = query("
      SELECT
        a.do_no           AS `do_no`,
        b.name            AS `brand`,
        a.model_no        AS `model_no`,
        a.so_no           AS `so_no`,
        a.price           AS `price`,
        SUM(a.qty)        AS `qty`,
        d.occurrence      AS `occurrence`
      FROM
        `sdo_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `sdo_header` AS c
      ON a.do_no=c.do_no
      LEFT JOIN
        `so_model` AS d
      ON a.so_no=d.so_no AND a.brand_code=d.brand_code AND a.model_no=d.model_no
      WHERE
        $modelWhereClause
      GROUP BY
        a.do_no, a.brand_code, a.model_no, a.so_no, a.price, d.occurrence
      ORDER BY
        a.do_no ASC,
        a.brand_code ASC,
        a.model_no ASC
    ");
  }

  if (count($doModelList) > 0) {
    foreach ($doModelList as $doModel) {
      $doNo = $doModel["do_no"];

      $arrayPointer = &$doModels;

      if (!isset($arrayPointer[$doNo])) {
        $arrayPointer[$doNo] = array();
      }
      $arrayPointer = &$arrayPointer[$doNo];

      array_push($arrayPointer, $doModel);
    }
  }
?>
