<?php
  define("SYSTEM_PATH", "../../../../../");
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
    <div class="page-wrapper">
      <?php if (count($statementHeaders) > 0) : ?>
        <?php foreach($statementHeaders as &$statementHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo AR_REPORT_CLIENT_STATEMENT_PRINTOUT_TITLE; ?></div>
            <table class="statement-header">
              <colgroup>
                <col style="width: 200px">
                <col>
              </colgroup>
              <tbody>
                <tr>
                  <td>Client Name (客戶名稱):</td>
                  <td><?php echo $statementHeader["debtor_name"]; ?></td>
                  <td>Date: </td>
                  <td><?php echo date("d-m-Y"); ?></td>
                </tr>
                <tr>
                  <td>Address (地址):</td>
                  <td><?php echo $statementHeader["address"]; ?></td>
                </tr>
              </tbody>
            </table>
            <?php if (count($statementInvoices[$statementHeader["debtor_code"]]) > 0) : ?>
              <?php $grandTotal = 0; ?>
              <?php foreach ($statementInvoices[$statementHeader["debtor_code"]] as $dateCategory => $invoices) : ?>
                <h4 class="date-category"><?php echo $dateCategory; ?></h4>
                <table class="statement-invoices sortable">
                  <colgroup>
                    <col style="width: 80px">
                    <col>
                    <col style="width: 100px">
                    <col style="width: 100px">
                    <col style="width: 100px">
                    <col style="width: 100px">
                  </colgroup>
                  <thead>
                    <tr></tr>
                    <tr>
                      <th>Date<br/>(日期)</th>
                      <th>Invoice No.<br/>(發票編號)</th>
                      <th class="number">Amount<br/>(金額)</th>
                      <th class="number">DR/CR Amount<br/>(貸／借)</th>
                      <th class="number">Paid<br/>(繳付金額)</th>
                      <th class="number">Balance<br/>(餘額)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $totalInvAmount = 0;
                      $totalDRCRAmount = 0;
                      $totalPaidAmount = 0;
                      $totalBalance = 0;

                      for ($i = 0; $i < count($invoices); $i++) {
                        $invoice = $invoices[$i];
                        $date = $invoice["date"];
                        $statementNo = $invoice["invoice_no"];
                        $statementAmount = $invoice["amount"];
                        $DRCRAmount = $invoice["dr_cr_amount"];
                        $paidAmount = $invoice["paid_amount"];
                        $balance = $invoice["balance"];

                        $totalInvAmount += $statementAmount;
                        $totalDRCRAmount += $DRCRAmount;
                        $totalPaidAmount += $paidAmount;
                        $totalBalance += $balance;
                        $grandTotal += $balance;

                        echo "
                          <tr>
                            <td title=\"$date\">$date</td>
                            <td title=\"$statementNo\">$statementNo</td>
                            <td class=\"number\" title=\"$statementAmount\">". number_format($statementAmount, 2) . "</td>
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
                      <th></th>
                      <th class="number">Total (合計):</th>
                      <th class="number"><?php echo number_format($totalInvAmount, 2); ?></th>
                      <th class="number"><?php echo number_format($totalDRCRAmount, 2); ?></th>
                      <th class="number"><?php echo number_format($totalPaidAmount, 2); ?></th>
                      <th class="number"><?php echo number_format($totalBalance, 2); ?></th>
                    </tr>
                  </tbody>
                </table>
              <?php endforeach ?>
              <table class="statement-footer">
                <colgroup>
                  <col>
                  <col style="width: 200px">
                  <col style="width: 100px">
                </colgroup>
                <tbody>
                  <tr>
                    <td></td>
                    <th class="number">Grand total (總合計餘額):</th>
                    <th class="number"><?php echo number_format($grandTotal, 2); ?></th>
                  </tr>
                </tbody>
              </table>
            <?php else : ?>
              <div class="statement-invoices-no-results">No invoices</div>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      <?php else : ?>
        <div class="statement-invoices-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
