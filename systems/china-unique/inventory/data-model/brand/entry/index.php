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
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (!assigned($id) || isset($brand)) : ?>
        <form method="post">
          <table id="brand-table">
            <tr>
              <td>Brand Code:</td>
              <td><input type="text" name="brand_code" value="<?php echo $brandCode; ?>" <?php echo $editMode ? "readonly" : ""; ?> required /></td>
            </tr>
            <tr>
              <td>Brand Name:</td>
              <td><input type="text" name="brand_name" value="<?php echo $brandName; ?>" required /></td>
            </tr>
          </table>
          <button type="submit"><?php echo $editMode ? "Edit" : "Create"; ?></button>
        </form>
      <?php else : ?>
        <div class="brand-no-result">Brand not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
