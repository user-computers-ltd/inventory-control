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

  if ($showMode === "live_only") {
    $whereClause = $whereClause . "
      AND (
        IFNULL(c.qty_on_hand, 0)>0 OR
        IFNULL(d.qty_on_hand_reserve, 0)>0 OR
        IFNULL(e.qty_on_order, 0)>0 OR
        IFNULL(f.qty_incoming, 0)>0 OR
        IFNULL(g.qty_incoming_reserve, 0)>0 OR
        IFNULL(h.qty_on_demand, 0)>0
      )
    ";
  }

  $results = query("
    SELECT
      a.id                              AS `id`,
      a.brand_code                      AS `brand_code`,
      b.name                            AS `brand_name`,
      a.model_no                        AS `model_no`,
      a.cost_average                    AS `cost_average`,
      a.cost_pri                        AS `cost_normal`,
      a.cost_sec                        AS `cost_special`,
      a.retail_normal                   AS `price_normal`,
      a.retail_special                  AS `price_special`,
      a.wholesale_special               AS `price_end_user`,
      a.wholesale_normal                AS `price_wholesale`,
      IFNULL(c.qty_on_hand, 0)          AS `qty_on_hand`,
      IFNULL(d.qty_on_hand_reserve, 0)  AS `qty_on_hand_reserve`,
      IFNULL(e.qty_on_order, 0)         AS `qty_on_order`,
      IFNULL(f.qty_incoming, 0)         AS `qty_incoming`,
      IFNULL(g.qty_incoming_reserve, 0) AS `qty_incoming_reserve`,
      IFNULL(h.qty_on_demand, 0)        AS `qty_on_demand`
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
        m.brand_code, m.model_no) AS d
    ON a.brand_code=d.brand_code AND a.model_no=d.model_no
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
        m.model_no, m.brand_code) AS e
    ON a.model_no=e.model_no AND a.brand_code=e.brand_code
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
        m.brand_code, m.model_no) AS f
    ON a.brand_code=f.brand_code AND a.model_no=f.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty) AS `qty_incoming_reserve`
      FROM
        `sdo_model` AS m
      LEFT JOIN
        `sdo_header` AS h
      ON m.do_no=h.do_no
      WHERE
        m.ia_no!=\"\" AND
        h.status=\"SAVED\"
      GROUP BY
        m.brand_code, m.model_no) AS g
    ON a.brand_code=g.brand_code AND a.model_no=g.model_no
    LEFT JOIN
      (SELECT
        m.brand_code, m.model_no, SUM(m.qty_outstanding) AS `qty_on_demand`
      FROM
        `so_model` AS m
      LEFT JOIN
        `so_header` AS h
      ON m.so_no=h.so_no
      WHERE
        h.status=\"CONFIRMED\"
      GROUP BY
        m.brand_code, m.model_no) AS h
    ON a.brand_code=h.brand_code AND a.model_no=h.model_no
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

  $models = query("
    SELECT DISTINCT
      model_no
    FROM
      `model`
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
      <form action="<?php echo DATA_MODEL_MODEL_ENTRY_URL; ?>" class="web-only">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="model-results">
          <colgroup>
            <col style="width: 50px;">
            <col>
            <col style="width: 110px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
            <col style="width: 80px;">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Brand</th>
              <th>Model No.</th>
              <th class="number">Average Cost</th>
              <th class="number">Cost Price List</th>
              <th class="number">Cost Price Special</th>
              <th class="number">Sales Price List</th>
              <th class="number">Sales Price Special</th>
              <th class="number">Sales Price End User</th>
            </tr>
          </thead>
          <tbody>
            <?php
              for ($i = 0; $i < count($results); $i++) {
                $model = $results[$i];
                $id = $model["id"];
                $brandCode = $model["brand_code"];
                $brandName = $model["brand_name"];
                $modelNo = $model["model_no"];
                $costAverage = $model["cost_average"];
                $costNormal = $model["cost_normal"];
                $costSpecial = $model["cost_special"];
                $priceNormal = $model["price_normal"];
                $priceSpecial = $model["price_special"];
                $priceEndUser = $model["price_end_user"];

                echo "
                  <tr>
                    <td title=\"$brandCode\">$brandName</td>
                    <td title=\"$modelNo\"><a href=\"" . DATA_MODEL_MODEL_DETAIL_URL . "?id=$id\">$modelNo</a></td>
                    <td class=\"number\" title=\"$costAverage\">" . number_format($costAverage, 6) . "</td>
                    <td class=\"number\" title=\"$costNormal\">" . number_format($costNormal, 2) . "</td>
                    <td class=\"number\" title=\"$costSpecial\">" . number_format($costSpecial, 2) . "</td>
                    <td class=\"number\" title=\"$priceNormal\">" . number_format($priceNormal, 2) . "</td>
                    <td class=\"number\" title=\"$priceSpecial\">" . number_format($priceSpecial, 2) . "</td>
                    <td class=\"number\" title=\"$priceEndUser\">" . number_format($priceEndUser, 2) . "</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
        </table>
      <?php else : ?>
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
