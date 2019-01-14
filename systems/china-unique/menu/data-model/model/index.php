<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterBrandCodes = $_GET["filter_brand_code"];
  $filterModelNos = $_GET["filter_model_no"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "live_only";

  $whereClause = "";

  if (assigned($filterBrandCodes) && count($filterBrandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $filterBrandCodes)) . ")";
  }

  if (assigned($filterModelNos) && count($filterModelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.model_no=\"$i\""; }, $filterModelNos)) . ")";
  }

  if ($showMode == "live_only") {
    $whereClause = $whereClause . "
      AND (
        IFNULL(c.qty_on_hand, 0) <> 0 OR
        IFNULL(d.qty_on_order, 0) <> 0 OR
        IFNULL(e.qty_on_reserve, 0) <> 0
      )
    ";
  }

  $results = query("
    SELECT
      a.id                          AS `id`,
      a.brand_code                  AS `brand_code`,
      b.name                        AS `brand_name`,
      a.model_no                    AS `model_no`,
      a.cost_average                AS `cost_average`,
      IFNULL(c.qty_on_hand, 0)      AS `qty_on_hand`,
      IFNULL(d.qty_on_order, 0)     AS `qty_on_order`,
      IFNULL(e.qty_on_reserve, 0)   AS `qty_on_reserve`,
      IFNULL(f.qty_outstanding, 0)  AS `qty_outstanding`
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
        h.status=\"SAVED\"
      GROUP BY
        m.model_no, m.brand_code) AS e
    ON a.brand_code=e.brand_code AND a.model_no=e.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty_outstanding) AS `qty_outstanding`
      FROM
        `so_model` AS m
      LEFT JOIN
        `so_header` AS h
      ON m.so_no=h.so_no
      WHERE
        h.status=\"POSTED\"
      GROUP BY
        m.brand_code, m.model_no) AS f
    ON a.brand_code=f.brand_code AND a.model_no=f.model_no
    WHERE
      a.brand_code IS NOT NULL
      $whereClause
    ORDER BY
      a.brand_code ASC,
      a.model_no ASC
  ");

  $brands = query("
    SELECT DISTINCT
      code AS `code`,
      name AS `name`
    FROM
      `brand`
    ORDER BY
      code ASC
  ");

  $modelWhereClause = "";

  if (assigned($filterBrandCodes) && count($filterBrandCodes) > 0) {
    $modelWhereClause = $modelWhereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "brand_code=\"$i\""; }, $filterBrandCodes)) . ")";
  }

  $models = query("
    SELECT DISTINCT
      model_no
    FROM
      `model`
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
    <?php include_once ROOT_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo DATA_MODEL_MODEL_TITLE; ?></div>
      <form>
        <table id="model-input" class="web-only">
          <tr>
            <th>Brand:</th>
            <th>Model No.:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_brand_code[]" multiple>
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($filterBrandCodes) && in_array($code, $filterBrandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td>
              <select name="filter_model_no[]" multiple>
                <?php
                  foreach ($models as $model) {
                    $modelNo = $model["model_no"];
                    $selected = assigned($filterModelNos) && in_array($modelNo, $filterModelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
          <tr>
            <th>
              <input
                id="input-live-only"
                type="checkbox"
                onchange="onLiveOnlyChanged(event)"
                <?php echo $showMode == "live_only" ? "checked" : "" ?>
              />
              <label for="input-live-only">Live only</label>
              <input
                id="input-show-mode"
                type="hidden"
                name="show_mode"
                value="<?php echo $showMode; ?>"
              />
            </th>
          </tr>
        </table>
      </form>
      <form action="<?php echo DATA_MODEL_MODEL_ENTRY_URL; ?>">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="model-results">
          <colgroup>
            <col>
            <col>
            <col>
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th rowspan="2">Brand</th>
              <th rowspan="2">Model No.</th>
              <th rowspan="2" class="number">Average Cost</th>
              <th colspan="5" class="quantity">Quantity</th>
            </tr>
            <tr>
              <th class="number">On Hand</th>
              <th class="number">On Reserve</th>
              <th class="number">Available</th>
              <th class="number">On Order</th>
              <th class="number">To Order</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalOnHand = 0;
              $totalOnReserve = 0;
              $totalAvailable = 0;
              $totalOnOrder = 0;
              $totalToOrder = 0;

              for ($i = 0; $i < count($results); $i++) {
                $model = $results[$i];
                $id = $model["id"];
                $brandCode = $model["brand_code"];
                $brandName = $model["brand_name"];
                $modelNo = $model["model_no"];
                $costAverage = $model["cost_average"];
                $qtyOnHand = $model["qty_on_hand"];
                $qtyOnReserve = $model["qty_on_reserve"];
                $qtyAvailable = $qtyOnHand - $qtyOnReserve;
                $qtyOnOrder = $model["qty_on_order"];
                $qtyToOrder = max(0, $model["qty_outstanding"] - $qtyOnHand - $qtyOnOrder);

                $totalOnHand += $qtyOnHand;
                $totalOnReserve += $qtyOnReserve;
                $totalAvailable += $qtyAvailable;
                $totalOnOrder += $qtyOnOrder;
                $totalToOrder += $qtyToOrder;

                echo "
                  <tr>
                    <td title=\"$brandCode\">$brandCode - $brandName</td>
                    <td title=\"$modelNo\"><a href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$id\">$modelNo</a></td>
                    <td class=\"number\" title=\"$costAverage\">" . number_format($costAverage, 2) . "</td>
                    <td class=\"number\" title=\"$qtyOnHand\">" . number_format($qtyOnHand) . "</td>
                    <td class=\"number\" title=\"$qtyOnReserve\">" . number_format($qtyOnReserve) . "</td>
                    <td class=\"number\" title=\"$qtyAvailable\">" . number_format($qtyAvailable) . "</td>
                    <td class=\"number\" title=\"$qtyOnOrder\">" . number_format($qtyOnOrder) . "</td>
                    <td class=\"number\" title=\"$qtyToOrder\">" . number_format($qtyToOrder) . "</td>
                  </tr>
                ";
              }
            ?>
            <tr>
              <th></th>
              <th></th>
              <th class="number">Total:</th>
              <th class="number" title="<?php echo $totalOnHand; ?>"><?php echo number_format($totalOnHand); ?></th>
              <th class="number" title="<?php echo $totalOnReserve; ?>"><?php echo number_format($totalOnReserve); ?></th>
              <th class="number" title="<?php echo $totalAvailable; ?>"><?php echo number_format($totalAvailable); ?></th>
              <th class="number" title="<?php echo $totalOnOrder; ?>"><?php echo number_format($totalOnOrder); ?></th>
              <th class="number" title="<?php echo $totalToOrder; ?>"><?php echo number_format($totalToOrder); ?></th>
            </tr>
          </tbody>
        </table>
      <?php else: ?>
        <div class="model-no-results">No results</div>
      <?php endif ?>
    </div>
    <script>
      function onLiveOnlyChanged(event) {
        var showMode = event.target.checked ? "live_only" : "show_all";
        document.querySelector("#input-show-mode").value = showMode;
        event.target.form.submit();
      }
    </script>
  </body>
</html>
