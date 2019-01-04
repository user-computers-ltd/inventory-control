<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $brandCodes = $_GET["brand_code"];
  $showMode = assigned($_GET["show_mode"]) ? $_GET["show_mode"] : "outstanding_only";

  $whereClause = "";

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  if ($showMode == "outstanding_only") {
    $whereClause = $whereClause . "
      AND a.qty_outstanding > 0";
  }

  $soModels = query("
    SELECT
      a.brand_code                                                                    AS `brand_code`,
      c.name                                                                          AS `brand_name`,
      SUM(a.qty)                                                                      AS `qty`,
      SUM(a.qty_outstanding)                                                          AS `qty_outstanding`,
      SUM(a.qty_outstanding * a.price * (100 - b.discount) / 100 * b.exchange_rate)   AS `amt_outstanding_base`,
      SUM(a.qty_outstanding * a.price * b.exchange_rate)                              AS `amt_outstanding_gross_base`
    FROM
      `so_model` AS a
    LEFT JOIN
      `so_header` AS b
    ON a.so_no=b.so_no
    LEFT JOIN
      `brand` AS c
    ON a.brand_code=c.code
    WHERE
      b.status=\"POSTED\"
      $whereClause
    GROUP BY
      a.brand_code, c.name
    ORDER BY
      a.brand_code ASC
  ");

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
      b.status=\"POSTED\"
    ORDER BY
      a.brand_code ASC
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
      <div class="headline"><?php echo SALES_REPORT_BRAND_SUMMARY_TITLE; ?></div>
      <form>
        <table id="so-input" class="web-only">
          <tr>
            <th>Brand:</th>
          </tr>
          <tr>
            <td>
              <select name="brand_code[]" multiple>
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($brandCodes) && in_array($code, $brandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
          <tr>
            <th>
              <input
                id="input-outstanding-only"
                type="checkbox"
                onchange="onOutstandingOnlyChanged(event)"
                <?php echo $showMode == "outstanding_only" ? "checked" : "" ?>
              />
              <label for="input-outstanding-only">Outstanding only</label>
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
      <?php if (count($soModels) > 0) : ?>
        <table class="so-results">
          <colgroup>
            <col>
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Brand</th>
              <th class="number">Total Qty</th>
              <th class="number">Outstanding Qty</th>
              <th class="number">Outstanding Amt <?php echo $InBaseCurrency; ?></th>
              <th class="number">(Exc. Discount)</th>
            </tr>
          </thead>
          <tbody>
          <?php
            $totalQty = 0;
            $totalOutstanding = 0;
            $totalAmtBase = 0;
            $totalGrossBase = 0;

            for ($i = 0; $i < count($soModels); $i++) {
              $soModel = $soModels[$i];
              $brandCode = $soModel["brand_code"];
              $brandName = $soModel["brand_name"];
              $qty = $soModel["qty"];
              $outstandingQty = $soModel["qty_outstanding"];
              $outstandingAmtBase = $soModel["amt_outstanding_base"];
              $outstandingGrossBase = $soModel["amt_outstanding_gross_base"];

              $totalQty += $qty;
              $totalOutstanding += $outstandingQty;
              $totalAmtBase += $outstandingAmtBase;
              $totalGrossBase += $outstandingGrossBase;

              echo "
                <tr>
                  <td title=\"$brandCode\">
                    <a class=\"link\" href=\"" . SALES_REPORT_BRAND_DETAIL_URL . "?show_mode=$showMode&brand_code[]=$brandCode\">$brandCode - $brandName</a>
                  </td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                  <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                  <td title=\"$outstandingGrossBase\" class=\"number\">" . number_format($outstandingGrossBase, 2) . "</td>
                </tr>
              ";
            }
          ?>
          </tbody>
          <tfoot>
            <tr>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalOutstanding); ?></th>
              <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
              <th class="number"><?php echo number_format($totalGrossBase, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="so-brand-no-results">No results</div>
    <?php endif ?>
    <script>
      function onOutstandingOnlyChanged(event) {
        var showMode = event.target.checked ? "outstanding_only" : "show_all";
        document.querySelector("#input-show-mode").value = showMode;
        event.target.form.submit();
      }
    </script>
  </body>
</html>
