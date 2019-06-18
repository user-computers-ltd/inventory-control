<?php
  $id = $_GET["id"];
  $modelNo = $_POST["model_no"];
  $brandCode = $_POST["brand_code"];
  $productType = $_POST["product_type"];
  $description = $_POST["description"];
  $costPri = $_POST["cost_pri"];
  $costPriCurrencyCode = $_POST["cost_pri_currency_code"];
  $costSec = $_POST["cost_sec"];
  $costSecCurrencyCode = $_POST["cost_sec_currency_code"];
  $averageCost = $_POST["cost_average"];
  $retailNormal = $_POST["retail_normal"];
  $retailSpecial = $_POST["retail_special"];
  $wholesaleNormal = $_POST["wholesale_normal"];
  $wholesaleSpecial = $_POST["wholesale_special"];

  /* If a form is submitted, update or insert the model. */
  if (
    assigned($modelNo) &&
    assigned($brandCode) &&
    assigned($productType) &&
    assigned($costPri) &&
    assigned($costPriCurrencyCode) &&
    assigned($costSec) &&
    assigned($costSecCurrencyCode) &&
    assigned($averageCost) &&
    assigned($retailNormal) &&
    assigned($retailSpecial) &&
    assigned($wholesaleNormal) &&
    assigned($wholesaleSpecial)
  ) {
    $queries = array();

    /* If an id is given, update the previous model. */
    if (assigned($id)) {
      query("
        UPDATE
          `model`
        SET
          model_no=\"$modelNo\",
          brand_code=\"$brandCode\",
          product_type=\"$productType\",
          description=\"$description\",
          cost_pri=\"$costPri\",
          cost_pri_currency_code=\"$costPriCurrencyCode\",
          cost_sec=\"$costSec\",
          cost_sec_currency_code=\"$costSecCurrencyCode\",
          cost_average=\"$averageCost\",
          retail_normal=\"$retailNormal\",
          retail_special=\"$retailSpecial\",
          wholesale_normal=\"$wholesaleNormal\",
          wholesale_special=\"$wholesaleSpecial\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `model`
          (
            model_no,
            brand_code,
            product_type,
            description,
            cost_pri,
            cost_pri_currency_code,
            cost_sec,
            cost_sec_currency_code,
            cost_average,
            retail_normal,
            retail_special,
            wholesale_normal,
            wholesale_special
          )
        VALUES
          (
            \"$modelNo\",
            \"$brandCode\",
            \"$productType\",
            \"$description\",
            \"$costPri\",
            \"$costPriCurrencyCode\",
            \"$costSec\",
            \"$costSecCurrencyCode\",
            \"$averageCost\",
            \"$retailNormal\",
            \"$retailSpecial\",
            \"$wholesaleNormal\",
            \"$wholesaleSpecial\"
          )
      ");

      $id = query("SELECT id FROM `model` WHERE brand_code=\"$brandCode\" AND model_no=\"$modelNo\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_MODEL_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);
  $brands = query("SELECT code, name FROM `brand`");
  $results = query("SELECT code, rate FROM `currency`");
  $currencies = array();
  foreach ($results as $currency) {
    $currencies[$currency["code"]] = $currency["rate"];
  }

  /* If an id is given, attempt to retrieve an existing model. */
  if (assigned($id)) {
    $headline = DATA_MODEL_MODEL_EDIT_TITLE;

    $model = query("
      SELECT
        model_no                  AS `model_no`,
        brand_code                AS `brand_code`,
        product_type              AS `product_type`,
        description               AS `description`,
        cost_pri                  AS `cost_pri`,
        cost_pri_currency_code    AS `cost_pri_currency_code`,
        cost_sec                  AS `cost_sec`,
        cost_sec_currency_code    AS `cost_sec_currency_code`,
        cost_average              AS `cost_average`,
        retail_normal             AS `retail_normal`,
        retail_special            AS `retail_special`,
        wholesale_normal          AS `wholesale_normal`,
        wholesale_special         AS `wholesale_special`
      FROM
        `model`
      WHERE id=\"$id\"
    ")[0];

    if (isset($model)) {
      $modelNo = $model["model_no"];
      $brandCode = $model["brand_code"];
      $productType = $model["product_type"];
      $description = $model["description"];
      $costPri = $model["cost_pri"];
      $costPriCurrencyCode = $model["cost_pri_currency_code"];
      $costSec = $model["cost_sec"];
      $costSecCurrencyCode = $model["cost_sec_currency_code"];
      $averageCost = $model["cost_average"];
      $retailNormal = $model["retail_normal"];
      $retailSpecial = $model["retail_special"];
      $wholesaleNormal = $model["wholesale_normal"];
      $wholesaleSpecial = $model["wholesale_special"];
    }
  }

  /* Else, initialize values for a new model. */
  else {
    $headline = DATA_MODEL_MODEL_CREATE_TITLE;

    $costPri = 0;
    $costPriCurrencyCode = COMPANY_CURRENCY;
    $costSec = 0;
    $costSecCurrencyCode = COMPANY_CURRENCY;
    $averageCost = 0;
    $retailNormal = 0;
    $retailSpecial = 0;
    $wholesaleNormal = 0;
    $wholesaleSpecial = 0;
  }
?>
