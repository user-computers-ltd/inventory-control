<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterCurrencyCodes = $_GET["filter_currency_code"];

  $whereClause = "";

  if (assigned($filterCurrencyCodes) && count($filterCurrencyCodes) > 0) {
    $whereClause = "
      AND (" . join(" OR ", array_map(function ($i) { return "code=\"$i\""; }, $filterCurrencyCodes)) . ")";
  }

  $results = query("
    SELECT
      id   AS `id`,
      code AS `code`,
      name AS `name`,
      rate AS `rate`
    FROM
      `currency`
    WHERE
      code IS NOT NULL
      $whereClause
    ORDER BY
      code ASC
  ");

  $currencys = query("
    SELECT DISTINCT
      code AS `code`,
      name AS `name`
    FROM
      `currency`
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
      <div class="headline"><?php echo DATA_MODEL_CURRENCY_TITLE; ?></div>
      <form>
        <table id="currency-input" class="web-only">
          <tr>
            <th>Currency:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_currency_code[]" multiple>
                <?php
                  foreach ($currencys as $currency) {
                    $code = $currency["code"];
                    $name = $currency["name"];
                    $selected = assigned($filterCurrencyCodes) && in_array($code, $filterCurrencyCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <form action="<?php echo DATA_MODEL_CURRENCY_ENTRY_URL; ?>" class="web-only">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="currency-results">
          <thead>
            <tr></tr>
            <tr>
              <th>Currency Code</th>
              <th>Currency Name</th>
              <th class="number">Exchange Rate</th>
            </tr>
          </thead>
          <tbody>
            <?php
              foreach ($results as $currency) {
                $id = $currency["id"];
                $currencyCode = $currency["code"];
                $currencyName = $currency["name"];
                $exchangeRate = $currency["rate"];

                echo "
                  <tr>
                    <td title=\"$currencyCode\"><a href=\"" . DATA_MODEL_CURRENCY_DETAIL_URL . "?id=$id\">$currencyCode</a></td>
                    <td title=\"$currencyName\">$currencyName</td>
                    <td title=\"$exchangeRate\" class=\"number\">$exchangeRate</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
        </table>
      <?php else : ?>
        <div class="currency-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
