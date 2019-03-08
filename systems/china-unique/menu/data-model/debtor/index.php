<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterDebtorCodes = $_GET["filter_debtor_code"];

  $whereClause = "";

  if (assigned($filterDebtorCodes) && count($filterDebtorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "code=\"$i\""; }, $filterDebtorCodes)) . ")";
  }

  $results = query("
    SELECT
      id                          AS `id`,
      code                        AS `code`,
      english_name                AS `english_name`,
      chinese_name                AS `chinese_name`,
      contact                     AS `contact`
    FROM
      `debtor`
    WHERE
      code IS NOT NULL
      $whereClause
    ORDER BY
      code ASC
  ");

  $debtors = query("
    SELECT DISTINCT
      code          AS `code`,
      english_name  AS `name`
    FROM
      `debtor`
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
      <div class="headline"><?php echo DATA_MODEL_DEBTOR_TITLE; ?></div>
      <form>
        <table id="debtor-input" class="web-only">
          <tr>
            <th>Debtor:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_debtor_code[]" multiple>
                <?php
                  foreach ($debtors as $debtor) {
                    $code = $debtor["code"];
                    $name = $debtor["name"];
                    $selected = assigned($filterDebtorCodes) && in_array($code, $filterDebtorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <form action="<?php echo DATA_MODEL_DEBTOR_ENTRY_URL; ?>">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="debtor-results">
          <colgroup>
            <col>
            <col>
            <col>
            <col>
          </colgroup>
          <thead>
            <tr></tr>
            <tr>
              <th>Code</th>
              <th>English Name</th>
              <th>Chinese Name</th>
              <th>Contact</th>
            </tr>
          </thead>
          <tbody>
            <?php
              for ($i = 0; $i < count($results); $i++) {
                $debtor = $results[$i];
                $id = $debtor["id"];
                $code = $debtor["code"];
                $englishName = $debtor["english_name"];
                $chineseName = $debtor["chinese_name"];
                $contact = $debtor["contact"];

                echo "
                  <tr>
                    <td title=\"$code\"><a href=\"" . DATA_MODEL_DEBTOR_DETAIL_URL . "?id=$id\">$code</a></td>
                    <td title=\"$englishName\">$englishName</td>
                    <td title=\"$chineseName\">$chineseName</td>
                    <td title=\"$contact\">$contact</td>
                  </tr>
                ";
              }
            ?>
          </tbody>
        </table>
      <?php else : ?>
        <div class="debtor-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
