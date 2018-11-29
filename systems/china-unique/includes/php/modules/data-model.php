<?php
  /* URL configurations. */
  define("DATA_MODEL_URL", MENU_URL . "data-model/");
  define("DATA_MODEL_MODEL_URL", DATA_MODEL_URL . "model/");
  define("DATA_MODEL_BRAND_URL", DATA_MODEL_URL . "brand/");
  define("DATA_MODEL_WAREHOUSE_URL", DATA_MODEL_URL . "warehouse/");
  define("DATA_MODEL_EXCHANGE_RATE_URL", DATA_MODEL_URL . "exchange-rate/");


  /* Title configurations. */
  define("DATA_MODEL_TITLE", "(B) Data Model");
  define("DATA_MODEL_MODEL_TITLE", "(B1) Model");
  define("DATA_MODEL_BRAND_TITLE", "(B2) Brand");
  define("DATA_MODEL_WAREHOUSE_TITLE", "(B3) Warehouse");
  define("DATA_MODEL_EXCHANGE_RATE_TITLE", "(B4) Exchange Rate");

  $DATA_MODEL_MODULE = array(
    DATA_MODEL_MODEL_TITLE                  => DATA_MODEL_MODEL_URL,
    DATA_MODEL_BRAND_TITLE                  => DATA_MODEL_BRAND_URL,
    DATA_MODEL_WAREHOUSE_TITLE              => DATA_MODEL_WAREHOUSE_URL,
    DATA_MODEL_EXCHANGE_RATE_TITLE          => DATA_MODEL_EXCHANGE_RATE_URL
  );
?>
