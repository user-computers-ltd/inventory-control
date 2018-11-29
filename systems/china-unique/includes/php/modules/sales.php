<?php
  /* URL configurations. */
  define("SALES_URL", MENU_URL . "sales/");
  define("SALES_ORDER_URL", SALES_URL . "sales-order/");
  define("SALES_ORDER_PRINTOUT_URL", SALES_ORDER_URL . "printout/");
  define("SALES_ORDER_SAVED_URL", SALES_ORDER_URL . "saved/");
  define("SALES_ORDER_POSTED_URL", SALES_ORDER_URL . "posted/");

  define("SALES_REPORT_URL", SALES_URL . "sales-report/");
  define("SALES_REPORT_BRAND_SUMMARY_URL", SALES_REPORT_URL . "brand/summary/");
  define("SALES_REPORT_BRAND_DETAIL_URL", SALES_REPORT_URL . "brand/detail/");
  define("SALES_REPORT_MODEL_SUMMARY_URL", SALES_REPORT_URL . "model/summary/");
  define("SALES_REPORT_MODEL_DETAIL_URL", SALES_REPORT_URL . "model/detail/");
  define("SALES_REPORT_CUSTOMER_SUMMARY_URL", SALES_REPORT_URL . "customer/summary/");
  define("SALES_REPORT_CUSTOMER_DETAIL_URL", SALES_REPORT_URL . "customer/detail/");
  define("SALES_REPORT_PL_SUMMARY_URL", SALES_REPORT_URL . "pl/summary/");
  define("SALES_REPORT_PL_DETAIL_URL", SALES_REPORT_URL . "pl/detail/");

  define("ALLOTMENT_URL", SALES_URL . "allotment/");
  define("ALLOTMENT_INCOMING_URL", ALLOTMENT_URL . "incoming/");
  define("ALLOTMENT_STOCK_SALES_ORDER_URL", ALLOTMENT_URL . "stock/sales-order/");
  define("ALLOTMENT_STOCK_MODEL_URL", ALLOTMENT_URL . "stock/model/");

  define("ALLOTMENT_REPORT_URL", SALES_URL . "allotment-report/");
  define("ALLOTMENT_REPORT_CUSTOMER_URL", ALLOTMENT_REPORT_URL . "customer/");

  define("PACKING_LIST_URL", SALES_URL . "packing-list/");
  define("PACKING_LIST_PRINTOUT_URL", PACKING_LIST_URL . "printout/");
  define("PACKING_LIST_INVOICE_URL", PACKING_LIST_URL . "invoice/");
  define("PACKING_LIST_SAVED_URL", PACKING_LIST_URL . "saved/");
  define("PACKING_LIST_POSTED_URL", PACKING_LIST_URL . "posted/");


  /* Title configurations. */
  define("SALES_TITLE", "(F) Sales");
  define("SALES_ORDER_PRINTOUT_TITLE", "Sales Order");
  define("SALES_ORDER_TITLE", "(F1) Sales Order");
  define("SALES_ORDER_CREATE_TITLE", "(F1a) Create Sales Order");
  define("SALES_ORDER_SAVED_TITLE", "(F1b) Saved Sales Orders");
  define("SALES_ORDER_POSTED_TITLE", "(F1c) Posted Sales Orders");

  define("SALES_REPORT_TITLE", "(F2) Sales Report");
  define("SALES_REPORT_BRAND_SUMMARY_TITLE", "(F2a) Summary By Brand");
  define("SALES_REPORT_BRAND_DETAIL_TITLE", "(F2b) Detail By Brand");
  define("SALES_REPORT_MODEL_SUMMARY_TITLE", "(F2c) Summary By Model");
  define("SALES_REPORT_MODEL_DETAIL_TITLE", "(F2d) Detail By Model");
  define("SALES_REPORT_CUSTOMER_SUMMARY_TITLE", "(F2e) Summary By Customer");
  define("SALES_REPORT_CUSTOMER_DETAIL_TITLE", "(F2f) Detail By Customer");
  define("SALES_REPORT_PL_SUMMARY_TITLE", "(F2g) Summary P/L Analysis");
  define("SALES_REPORT_PL_DETAIL_TITLE", "(F2h) Detail P/L Analysis");

  define("ALLOTMENT_TITLE", "(F3) Allotment");
  define("ALLOTMENT_INCOMING_TITLE", "(F3a) Incoming Allotment");
  define("ALLOTMENT_STOCK_SALES_ORDER_TITLE", "(F3b) Stock Allotment By Sales Order");
  define("ALLOTMENT_STOCK_MODEL_TITLE", "(F3c) Stock Allotment By Model");

  define("ALLOTMENT_REPORT_TITLE", "(F4) Allotment Report");
  define("ALLOTMENT_REPORT_CUSTOMER_TITLE", "(F4a) Allotment Report By Customer");

  define("PACKING_LIST_TITLE", "(F5) Packing List");
  define("PACKING_LIST_PRINTOUT_TITLE", "Packing List");
  define("PACKING_LIST_SAVED_TITLE", "(F5a) Saved Packing Lists");
  define("PACKING_LIST_POSTED_TITLE", "(F5b) Posted Packing Lists");

  $SALES_MODULE = array(
    SALES_ORDER_TITLE => array(
      SALES_ORDER_CREATE_TITLE              => SALES_ORDER_URL,
      SALES_ORDER_SAVED_TITLE               => SALES_ORDER_SAVED_URL,
      SALES_ORDER_POSTED_TITLE              => SALES_ORDER_POSTED_URL
    ),
    SALES_REPORT_TITLE => array(
      SALES_REPORT_BRAND_SUMMARY_TITLE      => SALES_REPORT_BRAND_SUMMARY_URL,
      SALES_REPORT_BRAND_DETAIL_TITLE       => SALES_REPORT_BRAND_DETAIL_URL,
      SALES_REPORT_MODEL_SUMMARY_TITLE      => SALES_REPORT_MODEL_SUMMARY_URL,
      SALES_REPORT_MODEL_DETAIL_TITLE       => SALES_REPORT_MODEL_DETAIL_URL,
      SALES_REPORT_CUSTOMER_SUMMARY_TITLE   => SALES_REPORT_CUSTOMER_SUMMARY_URL,
      SALES_REPORT_CUSTOMER_DETAIL_TITLE    => SALES_REPORT_CUSTOMER_DETAIL_URL,
      SALES_REPORT_PL_SUMMARY_TITLE         => SALES_REPORT_PL_SUMMARY_URL,
      SALES_REPORT_PL_DETAIL_TITLE          => SALES_REPORT_PL_DETAIL_URL,
    ),
    ALLOTMENT_TITLE => array(
      ALLOTMENT_INCOMING_TITLE              => ALLOTMENT_INCOMING_URL,
      ALLOTMENT_STOCK_SALES_ORDER_TITLE     => ALLOTMENT_STOCK_SALES_ORDER_URL,
      ALLOTMENT_STOCK_MODEL_TITLE           => ALLOTMENT_STOCK_MODEL_URL,
    ),
    ALLOTMENT_REPORT_TITLE => array(
      ALLOTMENT_REPORT_CUSTOMER_TITLE       => ALLOTMENT_REPORT_CUSTOMER_URL,
    ),
    PACKING_LIST_TITLE => array(
      PACKING_LIST_SAVED_TITLE              => PACKING_LIST_SAVED_URL,
      PACKING_LIST_POSTED_TITLE             => PACKING_LIST_POSTED_URL
    )
  );
?>
