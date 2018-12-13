<?php
  define("SYSTEM_PATH", "../../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(in " . COMPANY_CURRENCY . ")";

  $modelNos = $_GET["model_no"];

  $whereClause = "";

  if (assigned($modelNos) && count($modelNos) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($m) { return "a.model_no=\"$m\""; }, $modelNos)) . ")";
  }

  $soModels = query("
    SELECT
      d.id                                                                            AS `id`,
      a.brand_code                                                                    AS `brand_code`,
      c.name                                                                          AS `brand_name`,
      a.model_no                                                                      AS `model_no`,
      SUM(a.qty)                                                                      AS `qty`,
      SUM(a.qty_outstanding)                                                          AS `outstanding_qty`,
      SUM(a.qty_outstanding * a.price * (100 - b.discount) / 100 * b.exchange_rate)   AS `outstanding_amt_base`
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
    WHERE
      b.status=\"POSTED\"
      $whereClause
    GROUP BY
      d.id, a.brand_code, c.name, a.model_no
    ORDER BY
      a.model_no ASC
  ");

  $models = query("
    SELECT DISTINCT
      model_no AS `model_no`
    FROM
      `so_model`
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
      <div class="headline"><?php echo SALES_REPORT_MODEL_SUMMARY_TITLE; ?></div>
      <form>
        <table id="so-input">
          <tr>
            <th>Model No.:</th>
          </tr>
          <tr>
            <td>
              <select name="model_no[]" multiple>
                <?php
                  foreach ($models as $model) {
                    $modelNo = $model["model_no"];
                    $selected = assigned($modelNos) && in_array($modelNo, $modelNos) ? "selected" : "";
                    echo "<option value=\"$modelNo\" $selected>$modelNo</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($soModels) > 0): ?>
        <table class="so-results">
          <colgroup>
            <col>
            <col>
            <col style="width: 100px">
            <col style="width: 100px">
            <col style="width: 100px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Brand</th>
              <th>Model No.</th>
              <th class="number">Total Qty</th>
              <th class="number">Outstanding Qty</th>
              <th class="number">Outstanding Amt <?php echo $InBaseCurrency; ?></th>
            </tr>
          </thead>
          <tbody>
          <?php
            $totalQty = 0;
            $totalOutstanding = 0;
            $totalAmtBase = 0;

            for ($i = 0; $i < count($soModels); $i++) {
              $soModel = $soModels[$i];
              $id = $soModel["id"];
              $brandCode = $soModel["brand_code"];
              $brandName = $soModel["brand_name"];
              $modelNo = $soModel["model_no"];
              $qty = $soModel["qty"];
              $outstandingQty = $soModel["outstanding_qty"];
              $outstandingAmtBase = $soModel["outstanding_amt_base"];

              $totalQty += $qty;
              $totalOutstanding += $outstandingQty;
              $totalAmtBase += $outstandingAmtBase;

              echo "
                <tr>
                  <td title=\"$brandCode\">$brandCode - $brandName</td>
                  <td title=\"$modelNo\"><a class=\"link\" href=\"" . SALES_REPORT_MODEL_DETAIL_URL . "?id[]=$id\">$modelNo</a></td>
                  <td title=\"$qty\" class=\"number\">" . number_format($qty) . "</td>
                  <td title=\"$outstandingQty\" class=\"number\">" . number_format($outstandingQty) . "</td>
                  <td title=\"$outstandingAmtBase\" class=\"number\">" . number_format($outstandingAmtBase, 2) . "</td>
                </tr>
              ";
            }
          ?>
          </tbody>
          <tfoot>
            <tr>
              <th></th>
              <th class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQty); ?></th>
              <th class="number"><?php echo number_format($totalOutstanding); ?></th>
              <th class="number"><?php echo number_format($totalAmtBase, 2); ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="so-model-no-results">No results</div>
    <?php endif ?>
  </body>
</html>
