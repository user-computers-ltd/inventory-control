<?php
  define("SYSTEM_PATH", "../../../");

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
      <?php if (count($invoiceHeaders) > 0) : ?>
        <?php foreach($invoiceHeaders as &$invoiceHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo OUT_INVOICE_PRINTOUT_TITLE ?></div>
            <table class="invoice-header sortable">
              <tr>
                <td>Invoice No.:</td>
                <td><?php echo $invoiceHeader["invoice_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $invoiceHeader["date"]; ?></td>
              </tr>
              <tr>
                <td>Client:</td>
                <td><?php echo $invoiceHeader["debtor"]; ?></td>
                <td>Currency:</td>
                <td><?php echo $invoiceHeader["currency"]; ?></td>
              </tr>
              <tr>
                <td>Status:</td>
                <td><?php echo $invoiceHeader["status"]; ?></td>
                <td>Maturity Date:</td>
                <td><?php echo $invoiceHeader["maturity_date"]; ?></td>
              </tr>
            </table>
            <?php if (count($invoiceModels[$invoiceHeader["invoice_no"]]) > 0) : ?>
              <table class="invoice-models">
                <thead>
                  <tr></tr>
                  <tr>
                    <th>Description</th>
                    <th class="number">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    $totalAmount = 0;
                    $vouchers = $invoiceModels[$invoiceHeader["invoice_no"]];

                    for ($i = 0; $i < count($vouchers); $i++) {
                      $voucher = $vouchers[$i];
                      $doNo = $voucher["do_no"];
                      $stockOutNo = $voucher["stock_out_no"];
                      $stockInNo = $voucher["stock_in_no"];
                      $settleRemarks = $voucher["settle_remarks"];
                      $amount = $voucher["amount"];
                      $description = assigned($stockInNo) ? $stockInNo :
                        (assigned($stockOutNo) ? $stockOutNo :
                        (assigned($doNo) ? $doNo : $settleRemarks));

                      $totalAmount += $amount;

                      echo "
                        <tr>
                          <td>$description</td>
                          <td class=\"number\">" . number_format($amount, 2) . "</td>
                        </tr>
                      ";
                    }
                  ?>
                </tbody>
                <tbody>
                  <tr>
                    <th class="number">Total:</th>
                    <th class="number"><?php echo number_format($totalAmount, 2); ?></th>
                  </tr>
                </tbody>
              </table>
            <?php else : ?>
              <div class="invoice-models-no-results">No vouchers</div>
            <?php endif ?>
            <table class="invoice-footer">
              <?php if (assigned($invoiceHeader["remarks"])) : ?>
              <tr>
                <td>Remarks:</td>
                <td><?php echo $invoiceHeader["remarks"]; ?></td>
              </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="invoice-not-found">Invoice not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
