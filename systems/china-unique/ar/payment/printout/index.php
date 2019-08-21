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
      <?php if (count($paymentHeaders) > 0) : ?>
        <?php foreach($paymentHeaders as &$paymentHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo AR_PAYMENT_PRINTOUT_TITLE ?></div>
            <table class="payment-header">
              <tr>
                <td>Payment No.:</td>
                <td><?php echo $paymentHeader["payment_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $paymentHeader["payment_date"]; ?></td>
              </tr>
              <tr>
                <td>Client:</td>
                <td><?php echo $paymentHeader["debtor"]; ?></td>
                <td>Currency:</td>
                <td><?php echo $paymentHeader["currency"]; ?></td>
              </tr>
              <tr>
                <td>Amount:</td>
                <td><?php echo $paymentHeader["amount"]; ?></td>
              </tr>
            </table>
            <table class="payment-footer">
              <?php if (assigned($paymentHeader["remarks"])) : ?>
              <tr>
                <td>Remarks:</td>
                <td><?php echo $paymentHeader["remarks"]; ?></td>
              </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="payment-not-found">Payment not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
