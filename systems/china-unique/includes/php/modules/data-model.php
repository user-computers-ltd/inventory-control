<?php
  /* URL configurations. */
  define("DATA_MODEL_URL", MENU_URL . "data-model/");
  define("DATA_MODEL_MODEL_URL", DATA_MODEL_URL . "model/");
  define("DATA_MODEL_MODEL_DETAIL_URL", DATA_MODEL_MODEL_URL . "detail/");
  define("DATA_MODEL_MODEL_ENTRY_URL", DATA_MODEL_MODEL_URL . "entry/");

  define("DATA_MODEL_BRAND_URL", DATA_MODEL_URL . "brand/");
  define("DATA_MODEL_WAREHOUSE_URL", DATA_MODEL_URL . "warehouse/");
  define("DATA_MODEL_EXCHANGE_RATE_URL", DATA_MODEL_URL . "exchange-rate/");


  /* Title configurations. */
  define("DATA_MODEL_TITLE", "(B) Data Model");
  define("DATA_MODEL_MODEL_TITLE", "(B1) Models");
  define("DATA_MODEL_MODEL_CREATE_TITLE", "(B1a) Create Model");
  define("DATA_MODEL_MODEL_EDIT_TITLE", "(B1b) Edit Model");
  define("DATA_MODEL_MODEL_DETAIL_TITLE", "(B1c) Model Detail");
  define("DATA_MODEL_BRAND_TITLE", "(B2) Brands");
  define("DATA_MODEL_WAREHOUSE_TITLE", "(B3) Warehouses");
  define("DATA_MODEL_EXCHANGE_RATE_TITLE", "(B4) Exchange Rates");

  $DATA_MODEL_MODULE = array(
    DATA_MODEL_MODEL_TITLE                     => DATA_MODEL_MODEL_URL,
    // DATA_MODEL_BRAND_TITLE                  => DATA_MODEL_BRAND_URL,
    // DATA_MODEL_WAREHOUSE_TITLE              => DATA_MODEL_WAREHOUSE_URL,
    // DATA_MODEL_EXCHANGE_RATE_TITLE          => DATA_MODEL_EXCHANGE_RATE_URL
  );
?>
