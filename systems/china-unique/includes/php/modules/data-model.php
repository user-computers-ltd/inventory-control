<?php
  /* URL configurations. */
  define("DATA_MODEL_URL", INVENTORY_URL . "data-model/");

  define("DATA_MODEL_MODEL_URL", DATA_MODEL_URL . "model/");
  define("DATA_MODEL_MODEL_DETAIL_URL", DATA_MODEL_MODEL_URL . "detail/");
  define("DATA_MODEL_MODEL_ENTRY_URL", DATA_MODEL_MODEL_URL . "entry/");

  define("DATA_MODEL_BRAND_URL", DATA_MODEL_URL . "brand/");
  define("DATA_MODEL_BRAND_DETAIL_URL", DATA_MODEL_BRAND_URL . "detail/");
  define("DATA_MODEL_BRAND_ENTRY_URL", DATA_MODEL_BRAND_URL . "entry/");

  define("DATA_MODEL_WAREHOUSE_URL", DATA_MODEL_URL . "warehouse/");
  define("DATA_MODEL_WAREHOUSE_DETAIL_URL", DATA_MODEL_WAREHOUSE_URL . "detail/");
  define("DATA_MODEL_WAREHOUSE_ENTRY_URL", DATA_MODEL_WAREHOUSE_URL . "entry/");

  define("DATA_MODEL_CURRENCY_URL", DATA_MODEL_URL . "currency/");
  define("DATA_MODEL_CURRENCY_DETAIL_URL", DATA_MODEL_CURRENCY_URL . "detail/");
  define("DATA_MODEL_CURRENCY_ENTRY_URL", DATA_MODEL_CURRENCY_URL . "entry/");

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
  define("DATA_MODEL_BRAND_CREATE_TITLE", "(B2a) Create Brand");
  define("DATA_MODEL_BRAND_EDIT_TITLE", "(B2b) Edit Brand");
  define("DATA_MODEL_BRAND_DETAIL_TITLE", "(B2c) Brand Detail");

  define("DATA_MODEL_WAREHOUSE_TITLE", "(B3) Warehouses");
  define("DATA_MODEL_WAREHOUSE_CREATE_TITLE", "(B3a) Create Warehouse");
  define("DATA_MODEL_WAREHOUSE_EDIT_TITLE", "(B3b) Edit Warehouse");
  define("DATA_MODEL_WAREHOUSE_DETAIL_TITLE", "(B3c) Warehouse Detail");

  define("DATA_MODEL_CURRENCY_TITLE", "(B4) Currencies");
  define("DATA_MODEL_CURRENCY_CREATE_TITLE", "(B4a) Create Currency");
  define("DATA_MODEL_CURRENCY_EDIT_TITLE", "(B4b) Edit Currency");
  define("DATA_MODEL_CURRENCY_DETAIL_TITLE", "(B4c) Currency Detail");

  define("DATA_MODEL_DEBTOR_TITLE", "(B5) Debtors");
  define("DATA_MODEL_DEBTOR_CREATE_TITLE", "(B5a) Create Debtor");
  define("DATA_MODEL_DEBTOR_EDIT_TITLE", "(B5b) Edit Debtor");
  define("DATA_MODEL_DEBTOR_DETAIL_TITLE", "(B5c) Debtor Detail");

  define("DATA_MODEL_CREDITOR_TITLE", "(B6) Creditors");
  define("DATA_MODEL_CREDITOR_CREATE_TITLE", "(B6a) Create Creditor");
  define("DATA_MODEL_CREDITOR_EDIT_TITLE", "(B6b) Edit Creditor");
  define("DATA_MODEL_CREDITOR_DETAIL_TITLE", "(B6c) Creditor Detail");

  define("DATA_MODEL_PRICE_CATEGORY_TITLE", "(B7) Price Category Revision");
  define("DATA_MODEL_PRICE_CATEGORY_CHANGE_TITLE", "(B7a) Price Change");
  define("DATA_MODEL_PRICE_CATEGORY_REPORT_TITLE", "(B7b) Price Revision Report");


  $DATA_MODEL_MODULE = array(
    DATA_MODEL_MODEL_TITLE                      => DATA_MODEL_MODEL_URL,
    DATA_MODEL_BRAND_TITLE                      => DATA_MODEL_BRAND_URL,
    DATA_MODEL_WAREHOUSE_TITLE                  => DATA_MODEL_WAREHOUSE_URL,
    DATA_MODEL_CURRENCY_TITLE                   => DATA_MODEL_CURRENCY_URL,
    DATA_MODEL_DEBTOR_TITLE                     => DATA_MODEL_DEBTOR_URL,
    DATA_MODEL_CREDITOR_TITLE                   => DATA_MODEL_CREDITOR_URL,
    DATA_MODEL_PRICE_CATEGORY_TITLE => array(
      DATA_MODEL_PRICE_CATEGORY_CHANGE_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/j_model_revision_d.php",
      DATA_MODEL_PRICE_CATEGORY_REPORT_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/j_model_cost_report.php",
    )
  );
?>
