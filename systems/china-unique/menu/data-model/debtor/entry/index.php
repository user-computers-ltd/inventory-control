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
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (!assigned($id) || isset($debtor)) : ?>
        <form method="post">
          <table id="debtor-table">
            <tr>
              <td>Debtor Code:</td>
              <td><input type="text" name="code" value="<?php echo $debtorCode; ?>" <?php echo $editMode ? "readonly" : ""; ?> required /></td>
            </tr>
            <tr>
              <td>English Name:</td>
              <td><input type="text" name="english_name" value="<?php echo $englishName; ?>" /></td>
            </tr>
            <tr>
              <td>Chinese Name:</td>
              <td><input type="text" name="chinese_name" value="<?php echo $chineseName; ?>" /></td>
            </tr>
            <tr>
              <td>Billing Address:</td>
              <td><textarea name="billing_address"><?php echo $billingAddress; ?></textarea></td>
            </tr>
            <tr>
              <td>Factory Address:</td>
              <td><textarea name="factory_address"><?php echo $factoryAddress; ?></textarea></td>
            </tr>
            <tr>
              <td>Contact:</td>
              <td><input type="text" name="contact" value="<?php echo $contact; ?>" /></td>
            </tr>
            <tr>
              <td>Telephone:</td>
              <td><input type="text" name="tel" value="<?php echo $tel; ?>" /></td>
            </tr>
            <tr>
              <td>Fax:</td>
              <td><input type="text" name="fax" value="<?php echo $fax; ?>" /></td>
            </tr>
            <tr>
              <td>Email:</td>
              <td><input type="text" name="email" value="<?php echo $email; ?>" /></td>
            </tr>
            <tr>
              <td>Credit Term:</td>
              <td><input type="text" name="credit_term" value="<?php echo $creditTerm; ?>" /></td>
            </tr>
            <tr>
              <td>Profile:</td>
              <td><textarea name="profile"><?php echo $profile; ?></textarea></td>
            </tr>
            <tr>
              <td>Remarks:</td>
              <td><textarea name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <button type="submit"><?php echo $editMode ? "Edit" : "Create"; ?></button>
        </form>
      <?php else : ?>
        <div class="debtor-no-result">Model not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
