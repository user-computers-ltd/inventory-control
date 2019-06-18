<?php
  $id = $_GET["id"];
  $brandCode = $_POST["brand_code"];
  $brandName = $_POST["brand_name"];

  /* If a form is submitted, update or insert the brand. */
  if (
    assigned($brandCode) &&
    assigned($brandName)
  ) {
    $queries = array();

    /* If an id is given, update the previous brand. */
    if (assigned($id)) {
      query("
        UPDATE
          `brand`
        SET
          code=\"$brandCode\",
          name=\"$brandName\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `brand`
          (code, name)
        VALUES
          (\"$brandCode\", \"$brandName\")
      ");

      $id = query("SELECT id FROM `brand` WHERE code=\"$brandCode\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_BRAND_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);


  /* If an id is given, attempt to retrieve an existing brand. */
  if (assigned($id)) {
    $headline = DATA_MODEL_BRAND_EDIT_TITLE;

    $brand = query("
      SELECT
        code AS `code`,
        name AS `name`
      FROM
        `brand`
      WHERE id=\"$id\"
    ")[0];

    if (isset($brand)) {
      $brandCode = $brand["code"];
      $brandName = $brand["name"];
    }
  }

  /* Else, initialize values for a new brand. */
  else {
    $headline = DATA_MODEL_BRAND_CREATE_TITLE;
  }
?>
