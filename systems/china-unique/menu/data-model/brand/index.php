<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterBrandCodes = $_GET["filter_brand_code"];

  $whereClause = "";

  if (assigned($filterBrandCodes) && count($filterBrandCodes) > 0) {
    $whereClause = "
      (" . join(" OR ", array_map(function ($i) { return "code=\"$i\""; }, $filterBrandCodes)) . ")";
  }

  $results = query("
    SELECT
      id   AS `id`,
      code AS `code`,
      name AS `name`
    FROM
      `brand`
    WHERE
      $whereClause
    ORDER BY
      code ASC
  ");

  $brands = query("
    SELECT DISTINCT
      code AS `code`,
      name AS `name`
    FROM
      `brand`
    ORDER BY
      code ASC
  ");
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
      <div class="headline"><?php echo DATA_MODEL_BRAND_TITLE; ?></div>
      <form>
        <table id="brand-input" class="web-only">
          <tr>
            <th>Brand:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_brand_code[]" multiple>
                <?php
                  foreach ($brands as $brand) {
                    $code = $brand["code"];
                    $name = $brand["name"];
                    $selected = assigned($filterBrandCodes) && in_array($code, $filterBrandCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <form action="<?php echo DATA_MODEL_BRAND_ENTRY_URL; ?>" class="web-only">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="brand-results">
          <thead>
            <tr></tr>
            <tr>
              <th>Brand Code</th>
              <th>Brand Name</th>
            </tr>
          </thead>
          <tbody>
            <?php
              foreach ($results as $brand) {
                $id = $brand["id"];
                $brandCode = $brand["code"];
                $brandName = $brand["name"];

                echo "
                  <tr>
                    <td title=\"$brandCode\"><a href=\"" . DATA_MODEL_BRAND_DETAIL_URL . "?id=$id\">$brandCode</a></td>
                    <td title=\"$brandName\">$brandName</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
        </table>
      <?php else : ?>
        <div class="brand-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
