<?php
  /* URL configurations. */
  define("DATA_MODEL_URL", MENU_URL . "data-model/");

  define("DATA_MODEL_MODEL_URL", DATA_MODEL_URL . "model/");
  define("DATA_MODEL_MODEL_DETAIL_URL", DATA_MODEL_MODEL_URL . "detail/");
  define("DATA_MODEL_MODEL_ENTRY_URL", DATA_MODEL_MODEL_URL . "entry/");

  define("DATA_MODEL_BRAND_URL", DATA_MODEL_URL . "brand/");
  define("DATA_MODEL_WAREHOUSE_URL", DATA_MODEL_URL . "warehouse/");
  define("DATA_MODEL_EXCHANGE_RATE_URL", DATA_MODEL_URL . "exchange-rate/");

  define("DATA_MODEL_DEBTOR_URL", DATA_MODEL_URL . "debtor/");
  define("DATA_MODEL_DEBTOR_DETAIL_URL", DATA_MODEL_DEBTOR_URL . "detail/");
  define("DATA_MODEL_DEBTOR_ENTRY_URL", DATA_MODEL_DEBTOR_URL . "entry/");

  define("DATA_MODEL_CREDITOR_URL", DATA_MODEL_URL . "creditor/");
  define("DATA_MODEL_CREDITOR_DETAIL_URL", DATA_MODEL_CREDITOR_URL . "detail/");
  define("DATA_MODEL_CREDITOR_ENTRY_URL", DATA_MODEL_CREDITOR_URL . "entry/");

  /* Title configurations. */
  define("DATA_MODEL_TITLE", "(B) Data Model");

  define("DATA_MODEL_MODEL_TITLE", "(B1) Models");
  define("DATA_MODEL_MODEL_CREATE_TITLE", "(B1a) Create Model");
  define("DATA_MODEL_MODEL_EDIT_TITLE", "(B1b) Edit Model");
  define("DATA_MODEL_MODEL_DETAIL_TITLE", "(B1c) Model Detail");

  define("DATA_MODEL_BRAND_TITLE", "(B2) Brands");
  define("DATA_MODEL_WAREHOUSE_TITLE", "(B3) Warehouses");
  define("DATA_MODEL_EXCHANGE_RATE_TITLE", "(B4) Exchange Rates");

  define("DATA_MODEL_DEBTOR_TITLE", "(B5) Debtors");
  define("DATA_MODEL_DEBTOR_CREATE_TITLE", "(B5a) Create Debtor");
  define("DATA_MODEL_DEBTOR_EDIT_TITLE", "(B5b) Edit Debtor");
  define("DATA_MODEL_DEBTOR_DETAIL_TITLE", "(B5c) Debtor Detail");

  define("DATA_MODEL_CREDITOR_TITLE", "(B6) Creditors");
  define("DATA_MODEL_CREDITOR_CREATE_TITLE", "(B6a) Create Creditor");
  define("DATA_MODEL_CREDITOR_EDIT_TITLE", "(B6b) Edit Creditor");
  define("DATA_MODEL_CREDITOR_DETAIL_TITLE", "(B6c) Creditor Detail");

  $DATA_MODEL_MODULE = array(
    DATA_MODEL_MODEL_TITLE                     => DATA_MODEL_MODEL_URL,
    // DATA_MODEL_BRAND_TITLE                  => DATA_MODEL_BRAND_URL,
    // DATA_MODEL_WAREHOUSE_TITLE              => DATA_MODEL_WAREHOUSE_URL,
    // DATA_MODEL_EXCHANGE_RATE_TITLE          => DATA_MODEL_EXCHANGE_RATE_URL,
    DATA_MODEL_DEBTOR_TITLE                    => DATA_MODEL_DEBTOR_URL,
    DATA_MODEL_CREDITOR_TITLE                  => DATA_MODEL_CREDITOR_URL
  );
?>
