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
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo DATA_MODEL_CREDITOR_DETAIL_TITLE; ?></div>
      <?php if (isset($creditor)) : ?>
        <form class="web-only" action="<?php echo DATA_MODEL_CREDITOR_ENTRY_URL; ?>">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button type="submit">Edit</button>
        </form>
        <table id="creditor-header">
          <tr>
            <th>Creditor Code:</th>
            <td><?php echo $creditor["code"]; ?></td>
          </tr>
          <tr>
            <th>English Name:</th>
            <td><?php echo $creditor["english_name"]; ?></td>
          </tr>
          <tr>
            <th>Chinese Name:</th>
            <td><?php echo $creditor["chinese_name"]; ?></td>
          </tr>
          <tr>
            <th>Billing Address:</th>
            <td class="multi-line"><?php echo $creditor["billing_address"]; ?></td>
          </tr>
          <tr>
            <th>Factory Address:</th>
            <td class="multi-line"><?php echo $creditor["factory_address"]; ?></td>
          </tr>
          <tr>
            <th>Contact:</th>
            <td><?php echo $creditor["contact"]; ?></td>
          </tr>
          <tr>
            <th>Telephone:</th>
            <td><?php echo $creditor["tel"]; ?></td>
          </tr>
          <tr>
            <th>Fax:</th>
            <td><?php echo $creditor["fax"]; ?></td>
          </tr>
          <tr>
            <th>Email:</th>
            <td><?php echo $creditor["email"]; ?></td>
          </tr>
          <tr>
            <th>Credit Term:</th>
            <td><?php echo $creditor["credit_term"]; ?></td>
          </tr>
          <tr>
            <th>Profile:</th>
            <td class="multi-line"><?php echo $creditor["profile"]; ?></td>
          </tr>
          <tr>
            <th>Remarks:</th>
            <td class="multi-line"><?php echo $creditor["remarks"]; ?></td>
          </tr>
        </table>
      <?php else : ?>
        <div class="creditor-no-result">Creditor not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
