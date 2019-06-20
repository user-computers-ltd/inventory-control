<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $debtorCodes = $_GET["debtor_code"];

  $whereClause = " AND a.qty_outstanding > 0";

  if (assigned($debtorCodes) && count($debtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($d) { return "b.debtor_code=\"$d\""; }, $debtorCodes)) . ")";
  }

  $results = query("
    SELECT
      CONCAT(b.debtor_code, \" - \", IFNULL(c.english_name, \"Unknown\"))   AS `debtor`,
      DATE_FORMAT(b.so_date, \"%d-%m-%Y\")                                  AS `date`,
      a.brand_code                                                          AS `brand_code`,
      a.model_no                                                            AS `model_no`,
      a.so_no                                                               AS `so_no`,
      a.qty                                                                 AS `qty`,
      a.qty_outstanding                                                     AS `qty_outstanding`,
      a.qty_outstanding * a.price * (100 - b.discount) / 100                AS `amt_outstanding`,
      IFNULL(g.qty_on_do, 0)                                                AS `qty_on_do`,
      IFNULL(d.qty_on_hand, 0) - IFNULL(e.qty_on_reserve, 0)                AS `qty_available`,
      IFNULL(f.qty_on_order, 0)                                             AS `qty_on_order`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON b.debtor_code=c.code
    LEFT JOIN
      (SELECT
        brand_code, model_no, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        brand_code, model_no) AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty) AS `qty_on_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        h.status=\"SAVED\" AND
        m.ia_no=\"\"
      GROUP BY
        m.brand_code, m.model_no) AS e
    ON a.brand_code=e.brand_code AND a.model_no=e.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(GREATEST(qty_outstanding, 0)) AS `qty_on_order`
      FROM
        `po_model` AS m
      LEFT JOIN
        `po_header` AS h
      ON m.po_no=h.po_no
      WHERE
        h.status=\"POSTED\"
      GROUP BY
        m.brand_code, m.model_no) AS f
    ON a.brand_code=f.brand_code AND a.model_no=f.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, m.so_no, SUM(m.qty) AS `qty_on_do`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        h.status=\"SAVED\"
      GROUP BY
        m.brand_code, m.model_no, m.so_no) AS g
    ON a.brand_code=g.brand_code AND a.model_no=g.model_no AND a.so_no=g.so_no
    WHERE
      b.status=\"CONFIRMED\"
      $whereClause
    ORDER BY
      b.debtor_code ASC,
      b.so_date ASC
  ");

  $soHeaders = array();

  foreach ($results as $soHeader) {
    $client = $soHeader["debtor"];

    if (!isset($soHeaders[$client])) {
      $soHeaders[$client] = array();
    }

    array_push($soHeaders[$client], $soHeader);
  }

  $debtors = query("
    SELECT DISTINCT
      a.debtor_code                         AS `code`,
      IFNULL(c.english_name, \"Unknown\")   AS `name`
    FROM
      `so_header` AS a
    LEFT JOIN
      (SELECT
        so_no                         AS `so_no`,
        SUM(qty_outstanding)          AS `total_qty_outstanding`
      FROM
        `so_model`
      GROUP BY
        so_no) AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `debtor` AS c
    ON a.debtor_code=c.code
    WHERE
      a.status=\"CONFIRMED\" AND
      IFNULL(b.total_qty_outstanding, 0) > 0
    ORDER BY
      a.debtor_code ASC
  ");
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_REPORT_OUTSTANDING_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($debtorCodes) && in_array($code, $debtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($debtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($debtorCodes) {
                    return in_array($i["code"], $debtorCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($soHeaders) > 0) : ?>
        <?php foreach ($soHeaders as $client => &$headers) : ?>
          <div class="so-client">
            <h4><?php echo $client; ?></h4>
            <table class="so-results sortable">
              <colgroup>
                <col style="width: 60px">
                <col>
                <col style="width: 80px">
                <col>
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 80px">
                <col style="width: 80px">
              </colgroup>
              <thead>
                <tr></tr>
                <tr>
                  <th>Brand</th>
                  <th>Model No.</th>
                  <th>Date</th>
                  <th>Order No.</th>
                  <th class="number">Qty<br/> Ordered</th>
                  <th class="number">Qty<br/> Outstanding</th>
                  <th class="number">Outstanding Amt</th>
                  <th class="number">Qty<br/> On DO</th>
                  <th class="number">Qty<br/> Available</th>
                  <th class="number">Qty<br/> On Order</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalQty = 0;
                  $totalQtyOutstanding = 0;
                  $totalAmtOutstanding = 0;
                  $totalDOQty = 0;

                  for ($i = 0; $i < count($headers); $i++) {
                    $soHeader = $headers[$i];
                    $date = $soHeader["date"];
                    $brandCode = $soHeader["brand_code"];
                    $modelNo = $soHeader["model_no"];
                    $soId = $soHeader["so_id"];
                    $soNo = $soHeader["so_no"];
                    $qty = $soHeader["qty"];
                    $outstandingQty = $soHeader["qty_outstanding"];
                    $outstandingAmt = $soHeader["amt_outstanding"];
                    $qtyOnDO = $soHeader["qty_on_do"];
                    $qtyAvailable = $soHeader["qty_available"];
                    $qtyOnOrder = $soHeader["qty_on_order"];

                    $totalQty += $qty;
                    $totalQtyOutstanding += $outstandingQty;
                    $totalAmtOutstanding += $outstandingAmt;
                    $totalDOQty += $qtyOnDO;

                    echo "
                      <tr>
                        <td title=\"$brandCode\">$brandCode</td>
                        <td title=\"$modelNo\">$modelNo</td>
                        <td title=\"$date\">$date</td>
                        <td title=\"$soNo\">
                          <a class=\"link\" href=\"" . SALES_ORDER_INTERNAL_PRINTOUT_URL . "?id[]=$soId\">$soNo</a>
                        </td>
                        <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                        <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                        <td title=\"$outstandingAmt\" class=\"number\">" . number_format($outstandingAmt, 2) . "</td>
                        <td title=\"$qtyOnDO\" class=\"number\">" . number_format($qtyOnDO) . "</td>
                        <td title=\"$qtyAvailable\" class=\"number\">" . number_format($qtyAvailable) . "</td>
                        <td title=\"$qtyOnOrder\" class=\"number\">" . number_format($qtyOnOrder) . "</td>
                      </tr>
                    ";
                  }
                ?>
                <tr>
                  <th></th>
                  <th></th>
                  <th></th>
                  <th class="number">Total:</th>
                  <th class="number"><?php echo number_format($totalQty); ?></th>
                  <th class="number"><?php echo number_format($totalQtyOutstanding); ?></th>
                  <th class="number"><?php echo number_format($totalAmtOutstanding, 2); ?></th>
                  <th class="number"><?php echo number_format($totalDOQty); ?></th>
                  <th></th>
                  <th></th>
                  <th></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="so-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
