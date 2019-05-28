<?php
  $id = $_GET["id"];

  $InBaseCurrency = "in " . COMPANY_CURRENCY . "";
  $date = date("Y-m-d");
  $year = date("Y");

  $model = query("
    SELECT
      a.model_no                                          AS `model_no`,
      a.brand_code                                        AS `brand_code`,
      a.product_type                                      AS `product_type`,
      a.description                                       AS `description`,
      b.name                                              AS `brand_name`,
      CONCAT(a.cost_pri_currency_code, ' ', a.cost_pri)   AS `cost_pri`,
      a.cost_pri_currency_code                            AS `cost_pri_currency_code`,
      a.cost_pri * IFNULL(f.rate, 1)                      AS `cost_pri_base`,
      CONCAT(a.cost_sec_currency_code, ' ', a.cost_sec)   AS `cost_sec`,
      a.cost_sec_currency_code                            AS `cost_sec_currency_code`,
      a.cost_sec * IFNULL(g.rate, 1)                      AS `cost_sec_base`,
      a.cost_average                                      AS `cost_average`,
      a.retail_normal                                     AS `retail_normal`,
      a.retail_special                                    AS `retail_special`,
      a.wholesale_normal                                  AS `wholesale_normal`,
      a.wholesale_special                                 AS `wholesale_special`,
      IFNULL(c.qty_on_hand, 0)                            AS `qty_on_hand`,
      IFNULL(d.qty_on_order, 0)                           AS `qty_on_order`,
      IFNULL(e.qty_on_reserve, 0)                         AS `qty_on_reserve`
    FROM
      `model` AS a
    LEFT JOIN
      `brand` AS b
    ON a.brand_code=b.code
    LEFT JOIN
      (SELECT
        model_no, brand_code, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        model_no, brand_code) AS c
    ON a.model_no=c.model_no AND a.brand_code=c.brand_code
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(GREATEST(qty_outstanding, 0)) AS `qty_on_order`
      FROM
        `po_model` AS m
      LEFT JOIN
        `po_header` AS h
      ON m.po_no=h.po_no
      WHERE
        h.status='POSTED'
      GROUP BY
        m.model_no, m.brand_code) AS d
    ON a.model_no=d.model_no AND a.brand_code=d.brand_code
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty) AS `qty_on_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        m.ia_no=\"\" AND
        h.status=\"SAVED\"
      GROUP BY
        m.model_no, m.brand_code) AS e
    ON a.model_no=e.model_no AND a.brand_code=e.brand_code
    LEFT JOIN
      `currency` AS f
    ON a.cost_pri_currency_code=f.code
    LEFT JOIN
      `currency` AS g
    ON a.cost_sec_currency_code=g.code
    WHERE
      a.id=\"$id\"
  ")[0];

  execute(array(
    "DROP TABLE IF EXISTS `temp_dates`",
    "CREATE TABLE `temp_dates` (date VARCHAR(30) NOT NULL)",
    "INSERT INTO `temp_dates` VALUES
      (DATE_FORMAT(NOW(), '%Y-%m')),
      (DATE_FORMAT(NOW() - INTERVAL 1 MONTH, '%Y-%m')),
      (DATE_FORMAT(NOW() - INTERVAL 2 MONTH, '%Y-%m')),
      (DATE_FORMAT(NOW() - INTERVAL 3 MONTH, '%Y-%m'))
    "
  ));

  function getMonthlyTransactions($id, $whereClause) {
    return "
      SELECT
        DATE_FORMAT(t.transaction_date, '%Y-%m')                            AS `date`,
        COUNT(*)                                                            AS `count`,
        SUM(t.qty)                                                          AS `qty`,
        SUM(t.qty * t.price * (100 - t.discount) / 100 * t.exchange_rate)   AS `amt`
      FROM
        `transaction` AS t
      LEFT JOIN
        `model` AS m
      ON t.brand_code=m.brand_code AND t.model_no=m.model_no
      WHERE
        m.id=\"$id\" AND ($whereClause)
      GROUP BY
        DATE_FORMAT(transaction_date, '%Y-%m')
    ";
  }

  function getYTDTransactions($id, $whereClause) {
    return "
      SELECT
        \"YTD\"                                                             AS `date`,
        COUNT(*)                                                            AS `count`,
        SUM(t.qty)                                                          AS `qty`,
        SUM(t.qty * t.price * (100 - t.discount) / 100 * t.exchange_rate)   AS `amt`
      FROM
        `transaction` AS t
      LEFT JOIN
        `model` AS m
      ON t.brand_code=m.brand_code AND t.model_no=m.model_no
      WHERE
        m.id=\"$id\" AND
        t.transaction_date
          BETWEEN
            DATE_FORMAT(NOW() ,'%Y-01-01') AND
            NOW()
          AND
        ($whereClause)
    ";
  }

  function getPreviousYTDTransactions($id, $whereClause) {
    return "
      SELECT
        \"Previous YTD\"                                                    AS `date`,
        COUNT(*)                                                            AS `count`,
        SUM(t.qty)                                                          AS `qty`,
        SUM(t.qty * t.price * (100 - t.discount) / 100 * t.exchange_rate)   AS `amt`
      FROM
        `transaction` AS t
      LEFT JOIN
        `model` AS m
      ON t.brand_code=m.brand_code AND t.model_no=m.model_no
      WHERE
        m.id=\"$id\" AND
        t.transaction_date
          BETWEEN
            DATE_FORMAT(NOW() - INTERVAL 1 YEAR, '%Y-01-01') AND
            LAST_DAY(DATE_FORMAT(NOW() - INTERVAL 1 YEAR, '%Y-12-%d'))
          AND
        ($whereClause)
    ";
  }

  $monthlyTransactions = query("
    SELECT
      a.date              AS `date`,
      IFNULL(b.count, 0)  AS `sales_count`,
      IFNULL(b.qty, 0)    AS `sales_qty`,
      IFNULL(b.amt, 0)    AS `sales_amt`,
      IFNULL(c.count, 0)  AS `sales_return_count`,
      IFNULL(c.qty, 0)    AS `sales_return_qty`,
      IFNULL(c.amt, 0)    AS `sales_return_amt`,
      IFNULL(d.count, 0)  AS `purchase_count`,
      IFNULL(d.qty, 0)    AS `purchase_qty`,
      IFNULL(d.amt, 0)    AS `purchase_amt`,
      IFNULL(e.count, 0)  AS `purchase_return_count`,
      IFNULL(e.qty, 0)    AS `purchase_return_qty`,
      IFNULL(e.amt, 0)    AS `purchase_return_amt`
    FROM
      temp_dates AS a
    LEFT JOIN
      (" . getMonthlyTransactions($id, "transaction_code=\"S1\" OR transaction_code=\"S2\"") . ") AS b
    ON a.date=b.date
    LEFT JOIN
      (" . getMonthlyTransactions($id, "transaction_code=\"R3\"") . ") AS c
    ON a.date=c.date
    LEFT JOIN
      (" . getMonthlyTransactions($id, "transaction_code=\"R1\" OR transaction_code=\"R2\"") . ") AS d
    ON a.date=d.date
    LEFT JOIN
      (" . getMonthlyTransactions($id, "transaction_code=\"S3\"") . ") AS e
    ON a.date=e.date
  ");

  $ytdTransactions = query("
    SELECT
      a.date              AS `date`,
      IFNULL(a.count, 0)  AS `sales_count`,
      IFNULL(a.qty, 0)    AS `sales_qty`,
      IFNULL(a.amt, 0)    AS `sales_amt`,
      IFNULL(b.count, 0)  AS `sales_return_count`,
      IFNULL(b.qty, 0)    AS `sales_return_qty`,
      IFNULL(b.amt, 0)    AS `sales_return_amt`,
      IFNULL(c.count, 0)  AS `purchase_count`,
      IFNULL(c.qty, 0)    AS `purchase_qty`,
      IFNULL(c.amt, 0)    AS `purchase_amt`,
      IFNULL(d.count, 0)  AS `purchase_return_count`,
      IFNULL(d.qty, 0)    AS `purchase_return_qty`,
      IFNULL(d.amt, 0)    AS `purchase_return_amt`
    FROM
      (" . getYTDTransactions($id, "transaction_code=\"S1\" OR transaction_code=\"S2\"") . ") AS a
    LEFT JOIN
      (" . getYTDTransactions($id, "transaction_code=\"R3\"") . ") AS b
    ON a.date=b.date
    LEFT JOIN
      (" . getYTDTransactions($id, "transaction_code=\"R1\" OR transaction_code=\"R2\"") . ") AS c
    ON a.date=c.date
    LEFT JOIN
      (" . getYTDTransactions($id, "transaction_code=\"S3\"") . ") AS d
    ON a.date=d.date
  ");

  $ytdPreviousTransactions = query("
    SELECT
      a.date              AS `date`,
      IFNULL(a.count, 0)  AS `sales_count`,
      IFNULL(a.qty, 0)    AS `sales_qty`,
      IFNULL(a.amt, 0)    AS `sales_amt`,
      IFNULL(b.count, 0)  AS `sales_return_count`,
      IFNULL(b.qty, 0)    AS `sales_return_qty`,
      IFNULL(b.amt, 0)    AS `sales_return_amt`,
      IFNULL(c.count, 0)  AS `purchase_count`,
      IFNULL(c.qty, 0)    AS `purchase_qty`,
      IFNULL(c.amt, 0)    AS `purchase_amt`,
      IFNULL(d.count, 0)  AS `purchase_return_count`,
      IFNULL(d.qty, 0)    AS `purchase_return_qty`,
      IFNULL(d.amt, 0)    AS `purchase_return_amt`
    FROM
      (" . getPreviousYTDTransactions($id, "transaction_code=\"S1\" OR transaction_code=\"S2\"") . ") AS a
    LEFT JOIN
      (" . getPreviousYTDTransactions($id, "transaction_code=\"R3\"") . ") AS b
    ON a.date=b.date
    LEFT JOIN
      (" . getPreviousYTDTransactions($id, "transaction_code=\"R1\" OR transaction_code=\"R2\"") . ") AS c
    ON a.date=c.date
    LEFT JOIN
      (" . getPreviousYTDTransactions($id, "transaction_code=\"S3\"") . ") AS d
    ON a.date=d.date
  ");

  query("DROP TABLE `temp_dates`");

  $warehouseStocks = query("
    SELECT
      c.code    AS `warehouse_code`,
      c.name    AS `warehouse_name`,
      SUM(qty)  AS `qty`
    FROM
      `stock` as a
    LEFT JOIN
      `model` AS b
    ON a.brand_code=b.brand_code AND a.model_no=b.model_no
    LEFT JOIN
      `warehouse` AS c
    ON a.warehouse_code=c.code
    WHERE
      b.id=\"$id\"
    GROUP BY
      c.code, c.name
  ");
?>
