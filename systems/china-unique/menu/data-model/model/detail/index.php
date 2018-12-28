<?php
  define("SYSTEM_PATH", "../../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include "process.php";
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
      <div class="headline"><?php echo DATA_MODEL_MODEL_DETAIL_TITLE; ?></div>
      <?php if (isset($model)): ?>
        <form class="web-only" action="<?php echo DATA_MODEL_MODEL_ENTRY_URL; ?>">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button type="submit">Edit</button>
        </form>
        <table id="model-header">
          <tr>
            <th>Model No.:</th>
            <td class="number"><?php echo $model["model_no"]; ?></td>
          </tr>
          <tr>
            <th>Description:</th>
            <td class="number multi-line"><?php echo $model["description"]; ?></td>
          </tr>
          <tr>
            <th>Brand:</th>
            <td class="number"><?php echo $model["brand_code"] . " - " . $model["brand_name"]; ?></td>
          </tr>
          <tr>
            <th>Cost Primary (正價):</th>
            <td class="number"><?php echo $model["cost_pri"]; ?></td>
            <th><?php echo $InBaseCurrency; ?>:</th>
            <td class="number"><?php echo number_format($model["cost_pri_base"], 6); ?></td>
          </tr>
          <tr>
            <th>Cost Special (特價):</th>
            <td class="number"><?php echo $model["cost_sec"]; ?></td>
            <th><?php echo $InBaseCurrency; ?>:</th>
            <td class="number"><?php echo number_format($model["cost_sec_base"], 6); ?></td>
          </tr>
          <tr>
            <th>Retail Normal Price (正價):</th>
            <td class="number"><?php echo number_format($model["retail_normal"], 6); ?></td>
          </tr>
          <tr>
            <th>Retail Special Price (特價):</th>
            <td class="number"><?php echo number_format($model["retail_special"], 6); ?></td>
          </tr>
          <tr>
            <th>End User Price (廠價):</th>
            <td class="number"><?php echo number_format($model["wholesale_special"], 6); ?></td>
          </tr>
          <tr>
            <th>Wholesale Price (批發價):</th>
            <td class="number"><?php echo number_format($model["wholesale_normal"], 6); ?></td>
          </tr>
          <tr>
            <th>Average Cost:</th>
            <td class="number"><?php echo number_format($model["cost_average"], 6); ?></td>
          </tr>
          <tr>
            <th>Qty On Hand:</th>
            <td class="number"><?php echo number_format($model["qty_on_hand"]); ?></td>
          </tr>
          <tr>
            <th>Qty On Order:</th>
            <td class="number"><?php echo number_format($model["qty_on_order"]); ?></td>
          </tr>
          <tr>
            <th>Qty On Reserve:</th>
            <td class="number"><?php echo number_format($model["qty_on_reserve"]); ?></td>
          </tr>
        </table>
        <table id="model-performance">
          <colgroup>
            <col style="width: 80px;">
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
            <col>
          </colgroup>
          <thead>
            <tr>
              <th rowspan="2">Period</th>
              <th colspan="2">Sales</th>
              <th colspan="2">Sales Return</th>
              <th colspan="2">Purchase</th>
              <th colspan="2">Purchase Return</th>
            </tr>
            <tr>
              <th class="number">Qty</th>
              <th class="number">Amount</th>
              <th class="number">Qty</th>
              <th class="number">Amount</th>
              <th class="number">Qty</th>
              <th class="number">Amount</th>
              <th class="number">Qty</th>
              <th class="number">Amount</th>
            </tr>
          </thead>
          <tbody>
            <?php
              function generateRows($transactions) {
                for ($i = 0; $i < count($transactions); $i++) {
                  $transaction = $transactions[$i];
                  $date = $transaction["date"];
                  $salesCount = $transaction["sales_count"];
                  $salesQty = $transaction["sales_qty"];
                  $salesAmt = $transaction["sales_amt"];
                  $salesReturnCount = $transaction["sales_return_count"];
                  $salesReturnQty = $transaction["sales_return_qty"];
                  $salesReturnAmt = $transaction["sales_return_amt"];
                  $purchaseCount = $transaction["purchase_count"];
                  $purchaseQty = $transaction["purchase_qty"];
                  $purchaseAmt = $transaction["purchase_amt"];
                  $purchaseReturnCount = $transaction["purchase_return_count"];
                  $purchaseReturnQty = $transaction["purchase_return_qty"];
                  $purchaseReturnAmt = $transaction["purchase_return_amt"];

                  echo "
                    <tr>
                      <td title=\"$date\">$date</td>
                      <td class=\"number\" title=\"$salesQty\">" . number_format($salesQty) . "</td>
                      <td class=\"number\" title=\"$salesAmt\">" . number_format($salesAmt, 2) . "</td>
                      <td class=\"number\" title=\"$salesReturnQty\">" . number_format($salesReturnQty) . "</td>
                      <td class=\"number\" title=\"$salesReturnAmt\">" . number_format($salesReturnAmt, 2) . "</td>
                      <td class=\"number\" title=\"$purchaseQty\">" . number_format($purchaseQty) . "</td>
                      <td class=\"number\" title=\"$purchaseAmt\">" . number_format($purchaseAmt, 2) . "</td>
                      <td class=\"number\" title=\"$purchaseReturnQty\">" . number_format($purchaseReturnQty) . "</td>
                      <td class=\"number\" title=\"$purchaseReturnAmt\">" . number_format($purchaseReturnAmt, 2) . "</td>
                    </tr>
                  ";
                }
              }

              generateRows($monthlyTransactions);
              generateRows($ytdTransactions);
              generateRows($ytdPreviousTransactions);
            ?>
          </tbody>
        </table>
        <?php if (count($warehouseStocks) > 0): ?>
          <table id="model-stock">
            <thead>
              <tr>
                <th>Warehouse</th>
                <th>Qty</th>
              </tr>
            </thead>
            <tbody>
              <?php
                for ($i = 0; $i < count($warehouseStocks); $i++) {
                  $warehouseStock = $warehouseStocks[$i];
                  $warehouseCode = $warehouseStock["warehouse_code"];
                  $warehouseName = $warehouseStock["warehouse_name"];
                  $qty = $warehouseStock["qty"];

                  echo "
                    <tr>
                      <td title=\"$warehouseCode\">$warehouseCode - $warehouseName</td>
                      <td class=\"number\" title=\"$qty\">" . number_format($qty) . "</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="model-no-result">No stocks</div>
        <?php endif ?>
      <?php else: ?>
        <div class="model-no-result">Model not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
