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
      <div class="headline"><?php echo DATA_MODEL_DEBTOR_DETAIL_TITLE; ?></div>
      <?php if (isset($debtor)) : ?>
        <form class="web-only" action="<?php echo DATA_MODEL_DEBTOR_ENTRY_URL; ?>">
          <input type="hidden" name="id" value="<?php echo $id; ?>" />
          <button type="submit">Edit</button>
        </form>
        <table id="debtor-header">
          <tr>
            <th>Debtor Code:</th>
            <td><?php echo $debtor["code"]; ?></td>
          </tr>
          <tr>
            <th>English Name:</th>
            <td><?php echo $debtor["english_name"]; ?></td>
          </tr>
          <tr>
            <th>Chinese Name:</th>
            <td><?php echo $debtor["chinese_name"]; ?></td>
          </tr>
          <tr>
            <th>Billing Address:</th>
            <td class="number multi-line"><?php echo $debtor["billing_address"]; ?></td>
          </tr>
          <tr>
            <th>Factory Address:</th>
            <td class="number multi-line"><?php echo $debtor["factory_address"]; ?></td>
          </tr>
          <tr>
            <th>Contact:</th>
            <td><?php echo $debtor["contact"]; ?></td>
          </tr>
          <tr>
            <th>Telephone:</th>
            <td><?php echo $debtor["tel"]; ?></td>
          </tr>
          <tr>
            <th>Fax:</th>
            <td><?php echo $debtor["fax"]; ?></td>
          </tr>
          <tr>
            <th>Email:</th>
            <td><?php echo $debtor["email"]; ?></td>
          </tr>
          <tr>
            <th>Credit Term:</th>
            <td><?php echo $debtor["credit_term"]; ?></td>
          </tr>
          <tr>
            <th>Profile:</th>
            <td class="number multi-line"><?php echo $debtor["profile"]; ?></td>
          </tr>
          <tr>
            <th>Remarks:</th>
            <td class="number multi-line"><?php echo $debtor["remarks"]; ?></td>
          </tr>
        </table>
      <?php else : ?>
        <div class="debtor-no-result">Debtor not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
