<?php
  $id = $_GET["id"];
  $warehouseCode = $_POST["warehouse_code"];
  $warehouseName = $_POST["warehouse_name"];

  /* If a form is submitted, update or insert the warehouse. */
  if (
    assigned($warehouseCode) &&
    assigned($warehouseName)
  ) {
    $queries = array();

    /* If an id is given, update the previous warehouse. */
    if (assigned($id)) {
      query("
        UPDATE
          `warehouse`
        SET
          code=\"$warehouseCode\",
          name=\"$warehouseName\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `warehouse`
          (code, name)
        VALUES
          (\"$warehouseCode\", \"$warehouseName\")
      ");

      $id = query("SELECT id FROM `warehouse` WHERE code=\"$warehouseCode\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_WAREHOUSE_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);


  /* If an id is given, attempt to retrieve an existing warehouse. */
  if (assigned($id)) {
    $headline = DATA_MODEL_WAREHOUSE_EDIT_TITLE;

    $warehouse = query("
      SELECT
        code AS `code`,
        name AS `name`
      FROM
        `warehouse`
      WHERE id=\"$id\"
    ")[0];

    if (isset($warehouse)) {
      $warehouseCode = $warehouse["code"];
      $warehouseName = $warehouse["name"];
    }
  }

  /* Else, initialize values for a new warehouse. */
  else {
    $headline = DATA_MODEL_WAREHOUSE_CREATE_TITLE;
  }
?>
