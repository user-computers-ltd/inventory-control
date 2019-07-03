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
        <?php if (!assigned($id) || isset($model)) : ?>
          <form method="post">
            <table id="model-table">
              <tr>
                <td>Model No.:</td>
                <td><input type="text" name="model_no" value="<?php echo $modelNo; ?>" <?php echo $editMode ? "readonly" : ""; ?> required /></td>
              </tr>
              <tr>
                <td>Brand Code:</td>
                <td>
                  <?php if ($editMode) : ?>
                    <input type="text" name="brand_code" value="<?php echo $brandCode; ?>" <?php echo $editMode ? "readonly" : ""; ?> required />
                  <?php else : ?>
                    <select name="brand_code" required>
                      <?php
                        foreach ($brands as $brand) {
                          $code = $brand["code"];
                          $name = $brand["name"];
                          $selected = $brandCode == $code ? "selected" : "";
                          echo "<option value=\"$code\" $selected>$code - $name</option>";
                        }
                      ?>
                    </select>
                  <?php endif ?>
                </td>
              </tr>
              <tr>
                <td>Product Type:</td>
                <td>
                  <select name="product_type">
                    <?php
                      foreach ($PRODUCT_TYPES as $type) {
                        $selected = $productType == $type ? "selected" : "";
                        echo "<option value=\"$type\" $selected>$type</option>";
                      }
                    ?>
                  </select>
                </td>
              </tr>
              <tr>
                <td>Description:</td>
                <td><textarea name="description"><?php echo $description; ?></textarea></td>
              </tr>
              <tr>
                <td>Cost Price List (正價):</td>
                <td><input type="number" name="cost_pri" step="0.00000001" min="0" value="<?php echo $costPri; ?>" required /></td>
                <td>
                  <select name="cost_pri_currency_code" required>
                    <?php
                      foreach ($currencies as $code => $rate) {
                        $selected = $costPriCurrencyCode == $code ? "selected" : "";
                        echo "<option value=\"$code\" $selected>$code</option>";
                      }
                    ?>
                  </select>
                </td>
                <td>
                  <input type="number" name="cost_pri_original" step="0.00000001" min="0" value="<?php echo $costPriOriginal; ?>" required />
                </td>
              </tr>
            </tr>
            <tr>
              <td>Cost Price Special (特價):</td>
              <td><input type="number" name="cost_sec" step="0.00000001" min="0" value="<?php echo $costSec; ?>" required /></td>
              <td>
                <select name="cost_sec_currency_code" required>
                  <?php
                    foreach ($currencies as $code => $rate) {
                      $selected = $costSecCurrencyCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code</option>";
                    }
                  ?>
                </select>
              </td>
              <td>
                <input type="number" name="cost_sec_original" step="0.00000001" min="0" value="<?php echo $costSecOriginal; ?>" required />
              </td>
            </tr>
            <tr>
              <td>Sales Price List (正價):</td>
              <td><input type="number" name="retail_normal" step="0.00000001" min="0" value="<?php echo $retailNormal; ?>" required /></td>
            </tr>
            <tr>
              <td>Sales Price Special (特價):</td>
              <td><input type="number" name="retail_special" step="0.00000001" min="0" value="<?php echo $retailSpecial; ?>" required /></td>
            </tr>
            <?php if ($editMode) : ?>
              <tr>
                <td>Sales Price End User (廠價):</td>
                <td><input type="number" name="wholesale_special" step="0.00000001" min="0" value="<?php echo $wholesaleSpecial; ?>" required /></td>
              </tr>
              <tr>
                <td>Average Cost:</td>
                <td><input type="number" name="cost_average" step="0.00000001" min="0" value="<?php echo $averageCost; ?>" required /></td>
              </tr>
            <?php endif ?>
          </table>
          <button type="submit"><?php echo $editMode ? "Edit" : "Create"; ?></button>
        </form>
      <?php else : ?>
        <div class="model-no-result">Model not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
