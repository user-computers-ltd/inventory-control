<?php
  /* URL configurations. */
  define("REPORT_URL", INVENTORY_URL . "report/");
  define("REPORT_HISTORY_LOG_URL", REPORT_URL . "history-log/");
  define("REPORT_STOCK_TAKE_URL", REPORT_URL . "stock-take/");
  define("REPORT_STOCK_TAKE_WAREHOUSE_URL", REPORT_STOCK_TAKE_URL . "warehouse/");
  define("REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL", REPORT_STOCK_TAKE_WAREHOUSE_URL . "detail/");
  define("REPORT_STOCK_TAKE_BRAND_URL", REPORT_STOCK_TAKE_URL . "brand/");
  define("REPORT_STOCK_TAKE_BRAND_DETAIL_URL", REPORT_STOCK_TAKE_BRAND_URL . "detail/");
  define("REPORT_STOCK_TAKE_MODEL_URL", REPORT_STOCK_TAKE_URL . "model/");
  define("REPORT_STOCK_TAKE_MODEL_DETAIL_URL", REPORT_STOCK_TAKE_MODEL_URL . "detail/");


  /* Title configurations. */
  define("REPORT_TITLE", "(G) Management Report");

  define("REPORT_HISTORY_LOG_TITLE", "(G1) History Log");

  define("REPORT_MONLTHLY_SALES_TITLE", "(G2) Monthly Sales Report");

  define("REPORT_MEMORANDUM_TITLE", "(G3) Memorandum");

  define("REPORT_PERFORMANCE_TITLE", "(G4) Performance");
  define("REPORT_PERFORMANCE_CLIENT_TITLE", "(G4a) Performance By Client");
  define("REPORT_PERFORMANCE_MODEL_TITLE", "(G4b) Performance By Model");

  define("REPORT_STOCK_TAKE_TITLE", "(G5) Stock Take");
  define("REPORT_STOCK_TAKE_WAREHOUSE_TITLE", "(G5a) Stock Take Summary By Location");
  define("REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_TITLE", "(G5b) Stock Take Detail By Location");
  define("REPORT_STOCK_TAKE_BRAND_TITLE", "(G5c) Stock Take Summary By Brand");
  define("REPORT_STOCK_TAKE_BRAND_DETAIL_TITLE", "(G5d) Stock Take Detail By Brand");
  define("REPORT_STOCK_TAKE_MODEL_TITLE", "(G5e) Stock Take Summary By Model");
  define("REPORT_STOCK_TAKE_MODEL_DETAIL_TITLE", "(G5f) Stock Take Detail By Model");

  $REPORT_MODULE = array(
    REPORT_HISTORY_LOG_TITLE => REPORT_HISTORY_LOG_URL,
    REPORT_MONLTHLY_SALES_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/monthly_sales.php",
    REPORT_MEMORANDUM_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/memorandum.php",
    REPORT_PERFORMANCE_TITLE => array(
      REPORT_PERFORMANCE_CLIENT_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/j_customer_performance.php",
      REPORT_PERFORMANCE_MODEL_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/j_model_performance.php",
    ),
    REPORT_STOCK_TAKE_TITLE => array(
      REPORT_STOCK_TAKE_WAREHOUSE_TITLE => REPORT_STOCK_TAKE_WAREHOUSE_URL,
      REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_TITLE => REPORT_STOCK_TAKE_WAREHOUSE_DETAIL_URL,
      REPORT_STOCK_TAKE_BRAND_TITLE => REPORT_STOCK_TAKE_BRAND_URL,
      REPORT_STOCK_TAKE_BRAND_DETAIL_TITLE => REPORT_STOCK_TAKE_BRAND_DETAIL_URL,
      // REPORT_STOCK_TAKE_MODEL_TITLE => REPORT_STOCK_TAKE_MODEL_URL,
      // REPORT_STOCK_TAKE_MODEL_DETAIL_TITLE => REPORT_STOCK_TAKE_MODEL_DETAIL_URL
    )
  );
?>
