<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $InBaseCurrency = "(" . COMPANY_CURRENCY . ")";

  $from = $_GET["from"];
  $to = $_GET["to"];
  $brandCodes = $_GET["brand_code"];
  $modelNos = $_GET["model_no"];
  $transactionCodes = $_GET["transaction_code"];
  $clientCodes = $_GET["client_code"];

  $whereClause = "";

  if (assigned($from)) {
    $whereClause = $whereClause . "
      AND a.transaction_date>=\"$from\"";
  }

  if (assigned($to)) {
    $whereClause = $whereClause . "
      AND a.transaction_date<=\"$to\"";
  }

  if (assigned($clientCodes)) {
    $whereClause = $whereClause . "
    AND (" . join(" OR ", array_map(function ($i) { return "a.client_code=\"$i\""; }, $clientCodes)) . ")";
  }

  if (assigned($brandCodes) && count($brandCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.brand_code=\"$i\""; }, $brandCodes)) . ")";
  }

  if (assigned($modelNos) && count($modelNos) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($m) { return "a.model_no=\"$m\""; }, $modelNos)) . ")";
  }

  if (assigned($transactionCodes) && count($transactionCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "a.transaction_code=\"$i\""; }, $transactionCodes)) . ")";
  }

  $hasFilter = assigned($whereClause);

  $transactions = array();

  if ($hasFilter) {
    $transactions = query("
      SELECT
        DATE_FORMAT(a.transaction_date, \"%d-%m-%Y\")                                       AS `date`,
        a.header_no                                                                         AS `header_no`,
        a.client_code                                                                       AS `client_code`,
        IFNULL(b.english_name, IFNULL(c.creditor_name_eng, \"\"))                           AS `client_name`,
        a.transaction_code                                                                  AS `transaction_code`,
        a.warehouse_code                                                                    AS `warehouse_code`,
        a.discount                                                                          AS `discount`,
        a.currency_code                                                                     AS `currency`,
        a.exchange_rate                                                                     AS `exchange_rate`,
        a.brand_code                                                                        AS `brand_code`,
        d.name                                                                              AS `brand_name`,
        a.model_no                                                                          AS `model_no`,
        a.cost_average                                                                      AS `cost_average`,
        a.price                                                                             AS `price`,
        a.qty                                                                               AS `qty`
      FROM
        `transaction` AS a
      LEFT JOIN
        `debtor` AS b
      ON a.client_code=b.code
      LEFT JOIN
        `cu_ap`.`creditor` AS c
      ON a.client_code=c.creditor_code
      LEFT JOIN
        `brand` AS d
      ON a.brand_code=d.code
      WHERE
        a.header_no IS NOT NULL
        $whereClause
      ORDER BY
        a.transaction_date DESC,
        a.header_no ASC,
        a.brand_code ASC,
        a.model_no ASC
    ");
  }

  $clients = query("
    SELECT code, IFNULL(english_name, \"Unknown\") AS `name` FROM `debtor`
    UNION
    SELECT creditor_code AS `code`, IFNULL(creditor_name_eng, \"Unknown\") AS `name` FROM `cu_ap`.`creditor`
    ORDER BY code ASC
  ");

  $brands = query("
    SELECT DISTINCT
      code  AS `code`,
      name  AS `name`
    FROM
      `brand`
    ORDER BY
      code ASC
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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo REPORT_HISTORY_LOG_TITLE; ?></div>
      <form>
        <table id="trans-input">
          <tr>
            <th>From:</th>
            <th>To:</th>
            <th>Client Code:</th>
            <th>Brand Code:</th>
            <th>Model No:</th>
            <th>Transaction Code:</th>
          </tr>
          <tr>
            <td>
              <input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date("Y-m-d"); ?>" class="web-only" />
              <span class="print-only"><?php echo assigned($from) ? $from : "ANY"; ?></span>
            </td>
            <td>
              <input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date("Y-m-d"); ?>" class="web-only" />
              <span class="print-only"><?php echo assigned($to) ? $to : "ANY"; ?></span>
            </td>
            <td>
              <select name="client_code[]" multiple class="web-only">
                <?php
                  foreach ($clients as $client) {
                    $code = $client["code"];
                    $name = $client["name"];
                    $selected = assigned($clientCodes) && in_array($code, $clientCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($clientCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($clients, function ($i) use ($clientCodes) {
                    return in_array($i["code"], $clientCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
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
              <select name="transaction_code[]" multiple class="web-only">
                <?php
                  foreach ($TRANSACTION_CODES as $code => $desc) {
                    $selected = assigned($transactionCodes) && in_array($code, $transactionCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $desc</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php echo assigned($transactionCodes) ? join(", ", $transactionCodes) : "ALL"; ?>
              </span>
            </td>
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($transactions) > 0) : ?>
        <table id="trans-results" class="sortable">
          <colgroup>
            <col style="width: 80px">
            <col>
            <col style="width: 60px">
            <col>
            <col style="width: 60px">
            <col>
            <col style="width: 45px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 80px">
            <col style="width: 45px">
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Date</th>
              <th>Order No.</th>
              <th>Code</th>
              <th>Client</th>
              <th>Brand</th>
              <th>Model No.</th>
              <th>T.C.</th>
              <th class="number">Qty (In)</th>
              <th class="number">Qty (Out)</th>
              <th class="number">Unit Price <?php echo $InBaseCurrency; ?></th>
              <th class="number">Total (In)</th>
              <th class="number">Total (Out)</th>
              <th>W.H.</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $totalQtyIn = 0;
              $totalQtyOut = 0;
              $totalAmtIn = 0;
              $totalAmtOut = 0;

              for ($i = 0; $i < count($transactions); $i++) {
                $transaction = $transactions[$i];
                $date = $transaction["date"];
                $headerNo = $transaction["header_no"];
                $clientCode = $transaction["client_code"];
                $clientName = $transaction["client_name"];
                $exchangeRate = $transaction["exchange_rate"];
                $discount = $transaction["discount"];
                $tax = $transaction["tax"];
                $transactionCode = $transaction["transaction_code"];
                $transactionName = $TRANSACTION_CODES[$transactionCode];
                $warehouseCode = $transaction["warehouse_code"];
                $brandName = $transaction["brand_name"];
                $modelNo = $transaction["model_no"];
                $price = $transaction["price"];
                $qty = $transaction["qty"];
                $isInBound = strpos($transactionCode, "R") === 0;
                $discountFactor = (100 - $discount) / 100;
                $unitPrice = $price * $exchangeRate * $discountFactor;
                $amt = $qty * $unitPrice;

                $totalQtyIn += $isInBound ? $qty : 0;
                $totalQtyOut += $isInBound ? 0 : $qty;
                $totalAmtIn += $isInBound ? $amt : 0;
                $totalAmtOut += $isInBound ? 0 : $amt;

                echo "
                  <tr>
                    <td title=\"$date\">$date</td>
                    <td title=\"$headerNo\">$headerNo</td>
                    <td title=\"$clientCode\">$clientCode</td>
                    <td title=\"$clientName\">$clientName</td>
                    <td title=\"$brandName\">$brandName</td>
                    <td title=\"$modelNo\">$modelNo</td>
                    <td title=\"$transactionCode - $transactionName\">$transactionCode</td>
                    " . ($isInBound ? "<td class=\"number\" title=\"$qty\">" . number_format($qty) . "</td>" : "<td></td>") . "
                    " . ($isInBound ? "<td></td>" : "<td class=\"number\" title=\"$qty\">" . number_format($qty) . "</td>") . "
                    <td class=\"number\" title=\"$unitPrice\">" . number_format($unitPrice, 2) . "</td>
                    " . ($isInBound ? "<td class=\"number\" title=\"$amt\">" . number_format($amt, 2) . "</td>" : "<td></td>") . "
                    " . ($isInBound ? "<td></td>" : "<td class=\"number\" title=\"$amt\">" . number_format($amt, 2) . "</td>") . "
                    <td title=\"$warehouseCode\">$warehouseCode</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
          <tbody>
            <tr>
              <th colspan="7" class="number">Total:</th>
              <th class="number"><?php echo number_format($totalQtyIn); ?></th>
              <th class="number"><?php echo number_format($totalQtyOut); ?></th>
              <th></th>
              <th class="number"><?php echo number_format($totalAmtIn, 2); ?></th>
              <th class="number"><?php echo number_format($totalAmtOut, 2); ?></th>
              <th></th>
            </tr>
          </tbody>
        </table>
      <?php elseif ($hasFilter) : ?>
        <div class="trans-client-no-results">No results</div>
      <?php else : ?>
        <div class="trans-client-no-results">Please select any filter</div>
      <?php endif ?>
    </div>
  </body>
</html>
