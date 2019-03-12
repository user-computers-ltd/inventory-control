<?php
  $id = $_GET["id"];
  $currencyCode = $_POST["currency_code"];
  $currencyName = $_POST["currency_name"];
  $exchangeRate = $_POST["exchange_rate"];

  /* If a form is submitted, update or insert the currency. */
  if (
    assigned($currencyCode) &&
    assigned($currencyName) &&
    assigned($exchangeRate)
  ) {
    $queries = array();

    /* If an id is given, update the previous currency. */
    if (assigned($id)) {
      query("
        UPDATE
          `currency`
        SET
          code=\"$currencyCode\",
          name=\"$currencyName\",
          rate=\"$exchangeRate\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `currency`
          (code, name, rate)
        VALUES
          (\"$currencyCode\", \"$currencyName\", \"$exchangeRate\")
      ");

      $id = query("SELECT id FROM `currency` WHERE code=\"$currencyCode\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_CURRENCY_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);


  /* If an id is given, attempt to retrieve an existing currency. */
  if (assigned($id)) {
    $headline = DATA_MODEL_CURRENCY_EDIT_TITLE;

    $currency = query("
      SELECT
        code AS `code`,
        name AS `name`,
        rate AS `rate`
      FROM
        `currency`
      WHERE id=\"$id\"
    ")[0];

    if (isset($currency)) {
      $currencyCode = $currency["code"];
      $currencyName = $currency["name"];
      $exchangeRate = $currency["rate"];
    }
  }

  /* Else, initialize values for a new currency. */
  else {
    $headline = DATA_MODEL_CURRENCY_CREATE_TITLE;
    $exchangeRate = 1;
  }
?>
