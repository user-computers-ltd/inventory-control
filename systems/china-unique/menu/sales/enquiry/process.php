<?php
  $debtors = query("SELECT code, english_name AS name FROM `debtor`");
  $brands = query("SELECT code, name FROM `brand`");
  $models = query("
    SELECT
      a.brand_code                      AS `brand_code`,
      a.model_no                        AS `model_no`,
      a.retail_normal                   AS `normal_price`,
      a.retail_special                  AS `special_price`,
      IFNULL(b.qty_on_hand, 0)          AS `qty_on_hand`,
      IFNULL(c.qty_on_hand_reserve, 0)  AS `qty_on_hand_reserve`,
      IFNULL(d.qty_incoming, 0)         AS `qty_incoming`,
      IFNULL(e.qty_incoming_reserve, 0) AS `qty_incoming_reserve`
    FROM
      `model` AS a
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        brand_code, model_no) AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty) AS `qty_on_hand_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        m.ia_no=\"\" AND
        h.status=\"SAVED\"
      GROUP BY
        m.brand_code, m.model_no) AS c
    ON a.brand_code=c.brand_code AND a.model_no=c.model_no
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_incoming`
      FROM
        `ia_model` AS m
      LEFT JOIN
        `ia_header` AS h
      ON m.ia_no=h.ia_no
      WHERE
        h.status=\"DO\"
      GROUP BY
        m.brand_code, m.model_no) AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty) AS `qty_incoming_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        m.ia_no<>\"\" AND
        h.status=\"SAVED\"
      GROUP BY
        m.brand_code, m.model_no) AS e
    ON a.brand_code=e.brand_code AND a.model_no=e.model_no
    ORDER BY
      a.brand_code, a.model_no
  ");
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }
  $discount = 0;
  $currencyCode = COMPANY_CURRENCY;
  $exchangeRate = $currencies[COMPANY_CURRENCY];
?>
