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
      <?php if (count($creditNoteHeaders) > 0) : ?>
        <?php foreach($creditNoteHeaders as &$creditNoteHeader) : ?>
          <div class="page">
            <?php include SYSTEM_PATH . "includes/components/header/index.php"; ?>
            <div class="headline"><?php echo AR_PAYMENT_PRINTOUT_TITLE ?></div>
            <table class="credit-note-header">
              <tr>
                <td>Credit Note No.:</td>
                <td><?php echo $creditNoteHeader["credit_note_no"]; ?></td>
                <td>Date:</td>
                <td><?php echo $creditNoteHeader["credit_note_date"]; ?></td>
              </tr>
              <tr>
                <td>Client:</td>
                <td><?php echo $creditNoteHeader["debtor"]; ?></td>
                <td>Currency:</td>
                <td><?php echo $creditNoteHeader["currency"]; ?></td>
              </tr>
              <tr>
                <td>Amount:</td>
                <td><?php echo $creditNoteHeader["amount"]; ?></td>
              </tr>
            </table>
            <table class="credit-note-footer">
              <?php if (assigned($creditNoteHeader["remarks"])) : ?>
              <tr>
                <td>Remarks:</td>
                <td><?php echo $creditNoteHeader["remarks"]; ?></td>
              </tr>
              <?php endif ?>
            </table>
          </div>
        <?php endforeach; ?>
      <?php else : ?>
        <div id="credit-note-not-found">Credit Note not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
