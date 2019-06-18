<?php
  define("SYSTEM_PATH", "../../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  $filterCreditorCodes = $_GET["filter_creditor_code"];

  $whereClause = "";

  if (assigned($filterCreditorCodes) && count($filterCreditorCodes) > 0) {
    $whereClause = $whereClause . "
      AND (" . join(" OR ", array_map(function ($i) { return "code=\"$i\""; }, $filterCreditorCodes)) . ")";
  }

  $results = query("
    SELECT
      id                          AS `id`,
      creditor_code                        AS `code`,
      creditor_name_eng                AS `english_name`,
      creditor_name_chi                AS `chinese_name`,
      contact_name_l1                     AS `contact`
    FROM
      `cu_ap`.`creditor`
    WHERE
      creditor_code IS NOT NULL
      $whereClause
    ORDER BY
      creditor_code ASC
  ");

  $creditors = query("
    SELECT DISTINCT
      creditor_code          AS `code`,
      creditor_name_eng  AS `name`
    FROM
      `cu_ap`.`creditor`
    ORDER BY
      creditor_code ASC
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
      <div class="headline"><?php echo DATA_MODEL_CREDITOR_TITLE; ?></div>
      <form>
        <table id="creditor-input" class="web-only">
          <tr>
            <th>Creditor:</th>
          </tr>
          <tr>
            <td>
              <select name="filter_creditor_code[]" multiple>
                <?php
                  foreach ($creditors as $creditor) {
                    $code = $creditor["code"];
                    $name = $creditor["name"];
                    $selected = assigned($filterCreditorCodes) && in_array($code, $filterCreditorCodes) ? "selected" : "";
                    echo "<option value=\"$code\" $selected>$code - $name</option>";
                  }
                ?>
              </select>
            </td>
            <td><button type="submit">Go</button></td>
          </tr>
        </table>
      </form>
      <form action="<?php echo DATA_MODEL_CREDITOR_ENTRY_URL; ?>">
        <button type="submit">Create</button>
      </form>
      <?php if (count($results) > 0) : ?>
        <table id="creditor-results">
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
                $creditor = $results[$i];
                $id = $creditor["id"];
                $code = $creditor["code"];
                $englishName = $creditor["english_name"];
                $chineseName = $creditor["chinese_name"];
                $contact = $creditor["contact"];

                echo "
                  <tr>
                    <td title=\"$code\"><a href=\"" . DATA_MODEL_CREDITOR_DETAIL_URL . "?id=$id\">$code</a></td>
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
        <div class="creditor-no-results">No results</div>
      <?php endif ?>
    </div>
  </body>
</html>
