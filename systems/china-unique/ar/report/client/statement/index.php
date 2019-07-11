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
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper landscape">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo AR_REPORT_CLIENT_STATEMENT_TITLE; ?></div>
      <form>
        <table id="invoice-input">
          <tr>
            <th>Client:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_debtor_code[]" multiple class="web-only">
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
              <span class="print-only">
                <?php
                  echo assigned($filterDebtorCodes) ? join(", ", array_map(function ($d) {
                    return $d["code"] . " - " . $d["name"];
                  }, array_filter($debtors, function ($i) use ($filterDebtorCodes) {
                    return in_array($i["code"], $filterDebtorCodes);
                  }))) : "ALL";
                ?>
              </span>
            </td>
            <td><button type="submit" class="web-only">Go</button></td>
          </tr>
        </table>
      </form>
      <?php if (count($results) > 0) : ?>
        <form id="client-statements-form" method="post">
          <button type="submit" name="action" value="print" class="web-only">Print</button>
          <table id="invoice-results" class="sortable">
            <colgroup>
              <col class="web-only" style="width: 30px">
              <col style="width: 80px">
              <col>
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
              <col style="width: 80px">
            </colgroup>
            <thead>
              <tr></tr>
              <tr>
                <th class="web-only"></th>
                <th>Code</th>
                <th>Client</th>
                <th class="number">Invoice Amount</th>
                <th class="number">DR/CR Amount</th>
                <th class="number">Paid</th>
                <th class="number">Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php
                $totalInvAmount = 0;
                $totalDRCRAmount = 0;
                $totalPaidAmount = 0;
                $totalBalance = 0;

                for ($i = 0; $i < count($results); $i++) {
                  $result = $results[$i];
                  $id = $result["id"];
                  $debtorCode = $result["debtor_code"];
                  $debtorName = $result["debtor_name"];
                  $invoiceAmount = $result["amount"];
                  $DRCRAmount = $result["dr_cr_amount"];
                  $paidAmount = $result["paid_amount"];
                  $balance = $result["balance"];

                  $totalInvAmount += $invoiceAmount;
                  $totalDRCRAmount += $DRCRAmount;
                  $totalPaidAmount += $paidAmount;
                  $totalBalance += $balance;

                  echo "
                    <tr>
                      <td class=\"web-only\">
                        <input type=\"checkbox\" name=\"debtor_id[]\" data-debtor_code=\"$debtorCode\" value=\"$id\" />
                      </td>
                      <td title=\"$debtorCode\">$debtorCode</td>
                      <td title=\"$debtorName\">
                        <a href=\"" . AR_REPORT_CLIENT_STATEMENT_PRINTOUT_URL . "?id[]=$id\">$debtorName</a>
                      </td>
                      <td class=\"number\" title=\"$invoiceAmount\">". number_format($invoiceAmount, 2) . "</td>
                      <td class=\"number\" title=\"$DRCRAmount\">". number_format($DRCRAmount, 2) . "</td>
                      <td class=\"number\" title=\"$paidAmount\">". number_format($paidAmount, 2) . "</td>
                      <td class=\"number\" title=\"$balance\">". number_format($balance, 2) . "</td>
                    </tr>
                  ";
                }
              ?>
            </tbody>
            <tbody>
              <tr>
                <th class="web-only"></th>
                <th></th>
                <th class="number">Total:</th>
                <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
                <th class="number"><?php echo number_format($totalDRCRAmount, 2); ?></th>
                <th class="number"><?php echo number_format($totalPaidAmount, 2); ?></th>
                <th class="number"><?php echo number_format($totalBalance, 2); ?></th>
              </tr>
            </tbody>
          </table>
        </form>
      <?php else : ?>
        <div class="invoice-client-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
