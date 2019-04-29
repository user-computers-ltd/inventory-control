<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $brandCodes = $_GET["brand_code"];
  $modelNos = $_GET["model_no"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "outstanding_only";
  $showToOrderOnly = assigned($_GET["to_order_only"]) && $_GET["to_order_only"] == "on";

  $whereClause = "";
  $periodWhereClause = "";

  if (assigned($from)) {
    $periodWhereClause = $periodWhereClause . "
      AND transaction_date >= \"$from\"";
  }

  if (assigned($to)) {
    $periodWhereClause = $periodWhereClause . "
      AND transaction_date <= \"$to\"";
  }

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  if (assigned($modelNos) && count($modelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($m) { return "a.model_no=\"$m\""; }, $modelNos)) . ")";
  }

  if ($showMode == "outstanding_only") {
    $whereClause = $whereClause . "
      AND a.qty_outstanding > 0";
  }

  $results = query("
    SELECT
      a.brand_code                                                                                AS `brand_code`,
      c.name                                                                                      AS `brand_name`,
      a.model_no                                                                                  AS `model_no`,
      COUNT(DISTINCT b.debtor_code)                                                               AS `client_count`,
      SUM(a.qty_outstanding)                                                                      AS `qty_outstanding`,
      IFNULL(e.qty_on_hand, 0)                                                                    AS `qty_on_hand`,
      IFNULL(g.qty_on_reserve, 0)                                                                 AS `qty_on_reserve`,
      GREATEST(0, IFNULL(e.qty_on_hand, 0) - IFNULL(g.qty_on_reserve, 0))                         AS `qty_available`,
      IFNULL(f.qty_on_order, 0)                                                                   AS `qty_on_order`,
      GREATEST(0, SUM(a.qty_outstanding) - IFNULL(e.qty_on_hand, 0) - IFNULL(f.qty_on_order, 0))  AS `qty_to_order`,
      IFNULL(h.qty_sold, 0)                                                                       AS `qty_sold`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    LEFT JOIN
      `model` AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
    LEFT JOIN
      (SELECT
        brand_code, model_no, SUM(qty) AS `qty_on_hand`
      FROM
        `stock`
      GROUP BY
        brand_code, model_no) AS e
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
        h.status='POSTED'
      GROUP BY
        m.brand_code, m.model_no) AS f
    ON a.brand_code=f.brand_code AND a.model_no=f.model_no
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
        m.brand_code, m.model_no) AS g
    ON a.brand_code=g.brand_code AND a.model_no=g.model_no
    LEFT JOIN
      (SELECT
        brand_code, model_no, SUM(qty) AS `qty_sold`
      FROM
        `transaction`
      WHERE
        (transaction_code=\"S1\" OR transaction_code=\"S2\")
        $periodWhereClause
      GROUP BY
        brand_code, model_no) AS h
    ON a.brand_code=h.brand_code AND a.model_no=h.model_no
    WHERE
      b.status=\"CONFIRMED\"
      $whereClause
    GROUP BY
      a.brand_code, c.name, a.model_no
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC
  ");

  $soModels = array();

  foreach ($results as $soModel) {
    if (!$showToOrderOnly || $soModel["qty_to_order"] > 0) {
      $brand = $soModel["brand_code"] . " - " . $soModel["brand_name"];

      $arrayPointer = &$soModels;

      if (!isset($arrayPointer[$brand])) {
        $arrayPointer[$brand] = array();
      }
      $arrayPointer = &$arrayPointer[$brand];

      array_push($arrayPointer, $soModel);
    }
  }

  $brands = query("
    SELECT DISTINCT
      a.brand_code  AS `code`,
      c.name        AS `name`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    WHERE
      b.status=\"CONFIRMED\"
    ORDER BY
      a.brand_code ASC
  ");

  $modelWhereClause = "";

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  $models = query("
    SELECT DISTINCT
      model_no AS `model_no`
    FROM
      `so_model`
    WHERE
      model_no IS NOT NULL
      $modelWhereClause
    ORDER BY
      model_no ASC
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
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo SALES_REPORT_MODEL_SUMMARY_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Brand:</th>
            <th>Model No.:</th>
            <th>Sales From:</th>
            <th>Sales To:</th>
          </tr>
          <tr>
            <td>
              <select name="brand_code[]" multiple class="web-only">
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($brandCodes) && in_array($code, $brandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php echo assigned($brandCodes) ? join(", ", $brandCodes) : "ALL"; ?>
              </span>
            </td>
            <td>
              <select name="model_no[]" multiple class="web-only">
                <?php
                  foreach ($models as $model) {
                    $modelNo = $model["model_no"];
                    $selected = assigned($modelNos) && in_array($modelNo, $modelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php echo assigned($modelNos) ? join(", ", $modelNos) : "ALL"; ?>
              </span>
            </td>
            <td>
              <input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" class="web-only" />
              <span class="print-only"><?php echo assigned($from) ? $from : "ANY"; ?></span>
            </td>
            <td>
              <input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" class="web-only" />
              <span class="print-only"><?php echo assigned($to) ? $to : "ANY"; ?></span>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
          <tr>
            <th>
              <input
                id="input-outstanding-only"
                class="web-only"
                type="checkbox"
                onchange="onOutstandingOnlyChanged(event)"
                <?php echo $showMode === "outstanding_only" ? "checked" : "" ?>
              />
              <label class="web-only" for="input-outstanding-only">Outstanding only</label>
              <span id="input-outstanding-only-print" class="print-only">
                <?php echo $showMode === "outstanding_only" ? "Outstanding only" : ""; ?>
              </span>
              <input
                id="input-show-mode"
                type="hidden"
                name="show_mode"
                value="<?php echo $showMode; ?>"
              />
              <input
                id="input-to-order-only"
                class="web-only"
                type="checkbox"
                name="to_order_only"
                onchange="this.form.submit()"
                <?php echo $showToOrderOnly ? "checked" : "" ?>
              />
              <label class="web-only" for="input-to-order-only">To order only</label>
              <span id="input-to-order-only-print" class="print-only">
                <?php echo $showToOrderOnly ? "To order only" : ""; ?>
              </span>
            </th>
          </tr>
        </table>
      </form>
      <?php if (count($soModels) > 0) : ?>
        <?php foreach ($soModels as $brand => &$models) : ?>
          <div class="so-brand">
            <h4><?php echo $brand; ?></h4>
            <table class="so-results">
              <colgroup>
                <col>
                <col style="width: 80px">
                <col style="width: 100px">
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
                  <th rowspan="2">Model No.</th>
                  <th rowspan="2" class="number"># Clients</th>
                  <th colspan="7" class="category">Qty</th>
                </tr>
                <tr>
                  <th class="number">SO Outstanding</th>
                  <th class="number">On Hand</th>
                  <th class="number">Reserved</th>
                  <th class="number">Available</th>
                  <th class="number">On Order</th>
                  <th class="number">To Order</th>
                  <th class="number">Sales</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $totalOutstanding = 0;
                  $totalOnHandQty = 0;
                  $totalReserveQty = 0;
                  $totalAvailableQty = 0;
                  $totalOnOrderQty = 0;
                  $totalToOrderQty = 0;

                  for ($i = 0; $i < count($models); $i++) {
                    $soModel = $models[$i];
                    $brandCode = $soModel["brand_code"];
                    $modelNo = $soModel["model_no"];
                    $clientCount = $soModel["client_count"];
                    $outstandingQty = $soModel["qty_outstanding"];
                    $onHandQty = $soModel["qty_on_hand"];
                    $onReserveQty = $soModel["qty_on_reserve"];
                    $availableQty = $soModel["qty_available"];
                    $onOrderQty = $soModel["qty_on_order"];
                    $toOrderQty = $soModel["qty_to_order"];
                    $soldQty = $soModel["qty_sold"];

                    $linkParams = "?show_mode=$showMode&brand_code[]=" . urlencode($brandCode) . "&model_no[]=" . urlencode($modelNo);

                    $totalOutstanding += $outstandingQty;
                    $totalOnHandQty += $onHandQty;
                    $totalReserveQty += $onReserveQty;
                    $totalAvailableQty += $availableQty;
                    $totalOnOrderQty += $onOrderQty;
                    $totalToOrderQty += $toOrderQty;
                    $totalSoldQty += $soldQty;

                    echo "
                      <tr>
                        <td title=\"$modelNo\">
                          <a class=\"link\" href=\"" . SALES_REPORT_MODEL_DETAIL_URL . "$linkParams\">$modelNo</a>
                        </td>
                        <td title=\"$clientCount\" class=\"number\">" . number_format($clientCount) . "</td>
                        <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                        <td title=\"$onHandQty\" class=\"number\">" . number_format($onHandQty) . "</td>
                        <td title=\"$onReserveQty\" class=\"number\">" . number_format($onReserveQty) . "</td>
                        <td title=\"$availableQty\" class=\"number\">" . number_format($availableQty) . "</td>
                        <td title=\"$onOrderQty\" class=\"number\">" . number_format($onOrderQty) . "</td>
                        <td title=\"$toOrderQty\" class=\"number\">" . number_format($toOrderQty) . "</td>
                        <td title=\"$soldQty\" class=\"number\">" . number_format($soldQty) . "</td>
                      </tr>
                    ";
                  }
                ?>
                <tr>
                  <th class="number">Total:</th>
                  <th></th>
                  <th class="number"><?php echo number_format($totalOutstanding); ?></th>
                  <th class="number"><?php echo number_format($totalOnHandQty); ?></th>
                  <th class="number"><?php echo number_format($totalReserveQty); ?></th>
                  <th class="number"><?php echo number_format($totalAvailableQty); ?></th>
                  <th class="number"><?php echo number_format($totalOnOrderQty); ?></th>
                  <th class="number"><?php echo number_format($totalToOrderQty); ?></th>
                  <th class="number"><?php echo number_format($totalSoldQty); ?></th>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="so-model-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      function onOutstandingOnlyChanged(event) {
        var showMode = event.target.checked ? "outstanding_only" : "show_all";
        document.querySelector("#input-show-mode").value = showMode;
        event.target.form.submit();
      }
    </script>
  </body>
</html>
