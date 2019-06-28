<?php
  /* URL configurations. */
  define("SALES_URL", INVENTORY_URL . "sales/");

  define("SALES_ENQUIRY_URL", SALES_URL . "enquiry/");
  define("SALES_ENQUIRY_SAVED_URL", SALES_ENQUIRY_URL . "saved/");
  define("SALES_ENQUIRY_PRINTOUT_URL", SALES_ENQUIRY_URL . "printout/");
  define("SALES_ENQUIRY_INTERNAL_PRINTOUT_URL", SALES_ENQUIRY_URL . "internal-printout/");

  define("SALES_ORDER_URL", SALES_URL . "sales-order/");
  define("SALES_ORDER_PRINTOUT_URL", SALES_ORDER_URL . "printout/");
  define("SALES_ORDER_INTERNAL_PRINTOUT_URL", SALES_ORDER_URL . "internal-printout/");
  define("SALES_ORDER_SAVED_URL", SALES_ORDER_URL . "saved/");
  define("SALES_ORDER_CONFIRMED_URL", SALES_ORDER_URL . "confirmed/");
  define("SALES_ORDER_PRIORITY_URL", SALES_ORDER_URL . "priority/");

  define("SALES_SO_REPORT_URL", SALES_URL . "sales-order-report/");
  define("SALES_REPORT_CUSTOMER_SUMMARY_URL", SALES_SO_REPORT_URL . "client/");
  define("SALES_REPORT_CUSTOMER_DETAIL_URL", SALES_REPORT_CUSTOMER_SUMMARY_URL . "detail/");
  define("SALES_REPORT_BRAND_SUMMARY_URL", SALES_SO_REPORT_URL . "brand/");
  define("SALES_REPORT_BRAND_DETAIL_URL", SALES_REPORT_BRAND_SUMMARY_URL . "detail/");
  define("SALES_REPORT_MODEL_SUMMARY_URL", SALES_SO_REPORT_URL . "model/");
  define("SALES_REPORT_MODEL_DETAIL_URL", SALES_REPORT_MODEL_SUMMARY_URL . "detail/");
  define("SALES_REPORT_OUTSTANDING_URL", SALES_SO_REPORT_URL . "outstanding/");

  define("SALES_PL_REPORT_URL", SALES_URL . "pl-report/");
  define("SALES_PL_REPORT_MODEL_SUMMARY_URL", SALES_PL_REPORT_URL . "model/");
  define("SALES_PL_REPORT_MODEL_DETAIL_URL", SALES_PL_REPORT_MODEL_SUMMARY_URL . "detail/");

  define("SALES_ALLOTMENT_URL", SALES_URL . "allotment/");
  define("SALES_ALLOTMENT_INCOMING_URL", SALES_ALLOTMENT_URL . "incoming/");
  define("SALES_ALLOTMENT_PROVISIONAL_INCOMING_URL", SALES_ALLOTMENT_URL . "provisional-incoming/");
  define("SALES_ALLOTMENT_STOCK_URL", SALES_ALLOTMENT_URL . "stock/");

  define("SALES_ALLOTMENT_REPORT_URL", SALES_URL . "allotment-report/");
  define("SALES_ALLOTMENT_REPORT_CUSTOMER_URL", SALES_ALLOTMENT_REPORT_URL . "client/");

  define("SALES_DELIVERY_ORDER_URL", SALES_URL . "delivery-order/");
  define("SALES_DELIVERY_ORDER_PRINTOUT_URL", SALES_DELIVERY_ORDER_URL . "printout/");
  define("SALES_DELIVERY_ORDER_INTERNAL_PRINTOUT_URL", SALES_DELIVERY_ORDER_URL . "internal-printout/");
  define("SALES_DELIVERY_ORDER_SAVED_URL", SALES_DELIVERY_ORDER_URL . "saved/");
  define("SALES_DELIVERY_ORDER_POSTED_URL", SALES_DELIVERY_ORDER_URL . "posted/");

  define("SALES_INVOICING_URL", SALES_URL . "invoicing/");

  define("SALES_INVOICE_URL", SALES_INVOICING_URL . "invoice/");
  define("SALES_INVOICE_PRINTOUT_URL", SALES_INVOICE_URL . "printout/");
  define("SALES_INVOICE_SAVED_URL", SALES_INVOICE_URL . "saved/");

  define("SALES_INVOICE_REPORT_URL", SALES_INVOICING_URL . "invoice-report/");
  define("SALES_INVOICE_REPORT_DATE_URL", SALES_INVOICE_REPORT_URL . "date/");
  define("SALES_INVOICE_REPORT_CUSTOMER_URL", SALES_INVOICE_REPORT_URL . "client/");
  define("SALES_ISSUE_INVOICE_REPORT_URL", SALES_INVOICE_REPORT_URL . "issue/");


  /* Title configurations. */
  define("SALES_TITLE", "(F) Sales");

  define("SALES_ENQUIRY_PRINTOUT_TITLE", "貨物查詢");
  define("SALES_ENQUIRY_INTERNAL_PRINTOUT_TITLE", "貨物查詢 (內部)");
  define("SALES_ENQUIRY_TITLE", "(F0) 貨物查詢");
  define("SALES_ENQUIRY_CREATE_TITLE", "(F0a) 新增貨物查詢");
  define("SALES_ENQUIRY_SAVED_TITLE", "(F0b) 已保存的貨物查詢");

  define("SALES_ORDER_PRINTOUT_TITLE", "Sales Order");
  define("SALES_ORDER_INTERNAL_PRINTOUT_TITLE", "Sales Order (Internal)");
  define("SALES_ORDER_TITLE", "(F1) Sales Order");
  define("SALES_ORDER_CREATE_TITLE", "(F1a) Create Sales Order");
  define("SALES_ORDER_SAVED_TITLE", "(F1b) Saved Sales Orders");
  define("SALES_ORDER_CONFIRMED_TITLE", "(F1c) Confirmed Sales Orders");
  define("SALES_ORDER_PRIORITY_TITLE", "(F1d) Sales Order Priorities");

  define("SALES_SO_REPORT_TITLE", "(F2) Sales Order Report");
  define("SALES_REPORT_CUSTOMER_SUMMARY_TITLE", "(F2a) Confirmed Sales Order Summary By Client");
  define("SALES_REPORT_CUSTOMER_DETAIL_TITLE", "(F2b) Confirmed Sales Order Detail By Client By Date");
  define("SALES_REPORT_BRAND_SUMMARY_TITLE", "(F2c) Confirmed Sales Order Summary By Brand");
  define("SALES_REPORT_BRAND_DETAIL_TITLE", "(F2d) Confirmed Sales Order Detail By Brand By Date");
  define("SALES_REPORT_MODEL_SUMMARY_TITLE", "(F2e) Confirmed Sales Order Summary By Model");
  define("SALES_REPORT_MODEL_DETAIL_TITLE", "(F2f) Confirmed Sales Order Detail By Model By Date");
  define("SALES_REPORT_OUTSTANDING_TITLE", "(F2g) Outstanding Order Report");

  define("SALES_PL_REPORT_TITLE", "(F3) P/L Analysis Report");
  define("SALES_PL_REPORT_MODEL_SUMMARY_TITLE", "(F3c) Sales P/L Summary By Model");
  define("SALES_PL_REPORT_MODEL_DETAIL_TITLE", "(F3d) Sales P/L Detail By Model");

  define("SALES_ALLOTMENT_TITLE", "(F4) Allotment");
  define("SALES_ALLOTMENT_PROVISIONAL_INCOMING_TITLE", "(F4a) Provisional Incoming Allotment");
  define("SALES_ALLOTMENT_INCOMING_TITLE", "(F4b) Incoming Allotment");
  define("SALES_ALLOTMENT_STOCK_TITLE", "(F4c) Stock Allotment");

  define("SALES_ALLOTMENT_REPORT_TITLE", "(F5) Allotment Report");
  define("SALES_ALLOTMENT_REPORT_MODEL_TITLE", "(F5c) Allotment Report By Model");
  define("SALES_ALLOTMENT_REPORT_CUSTOMER_TITLE", "(F5d) Allotment Report By Client");

  define("SALES_DELIVERY_ORDER_TITLE", "(F6) Sales Delivery Order");
  define("SALES_DELIVERY_ORDER_PRINTOUT_TITLE", "送貨單");
  define("SALES_DELIVERY_ORDER_INTERNAL_PRINTOUT_TITLE", "送貨單(內部)");
  define("SALES_DELIVERY_ORDER_SAVED_TITLE", "(F6b) Issued Sales Delivery Orders");
  define("SALES_DELIVERY_ORDER_POSTED_TITLE", "(F6c) Posted Sales Delivery Orders");

  define("SALES_INVOICE_TITLE", "(F7) Sales Invoice");
  define("SALES_INVOICE_PRINTOUT_TITLE", "Sales Invoice");
  define("SALES_INVOICE_CREATE_TITLE", "(F7a) Create Sales Invoice");
  define("SALES_INVOICE_SAVED_TITLE", "(F7b) Saved Sales Invoices");

  define("SALES_INVOICE_REPORT_TITLE", "(F8) Sales Invoice Report");
  define("SALES_INVOICE_REPORT_DATE_TITLE", "(F8a) Monthly Stock Out By Date");
  define("SALES_INVOICE_REPORT_CUSTOMER_TITLE", "(F8b) Monthly Stock Out By Client");
  define("SALES_ISSUE_INVOICE_REPORT_TITLE", "(F8c) Issue Invoice By Client");

  $SALES_MODULE = array(
    SALES_ENQUIRY_TITLE => array(
      SALES_ENQUIRY_CREATE_TITLE                          => SALES_ENQUIRY_URL,
      SALES_ENQUIRY_SAVED_TITLE                           => SALES_ENQUIRY_SAVED_URL
    ),
    SALES_ORDER_TITLE => array(
      SALES_ORDER_CREATE_TITLE                            => SALES_ORDER_URL,
      SALES_ORDER_SAVED_TITLE                             => SALES_ORDER_SAVED_URL,
      SALES_ORDER_CONFIRMED_TITLE                         => SALES_ORDER_CONFIRMED_URL,
      SALES_ORDER_PRIORITY_TITLE                          => SALES_ORDER_PRIORITY_URL
    ),
    SALES_SO_REPORT_TITLE => array(
      SALES_REPORT_CUSTOMER_SUMMARY_TITLE                 => SALES_REPORT_CUSTOMER_SUMMARY_URL,
      SALES_REPORT_CUSTOMER_DETAIL_TITLE                  => SALES_REPORT_CUSTOMER_DETAIL_URL,
      SALES_REPORT_BRAND_SUMMARY_TITLE                    => SALES_REPORT_BRAND_SUMMARY_URL,
      SALES_REPORT_BRAND_DETAIL_TITLE                     => SALES_REPORT_BRAND_DETAIL_URL,
      SALES_REPORT_MODEL_SUMMARY_TITLE                    => SALES_REPORT_MODEL_SUMMARY_URL,
      SALES_REPORT_MODEL_DETAIL_TITLE                     => SALES_REPORT_MODEL_DETAIL_URL,
      SALES_REPORT_OUTSTANDING_TITLE                      => SALES_REPORT_OUTSTANDING_URL
    ),
    SALES_PL_REPORT_TITLE => array(
      SALES_PL_REPORT_MODEL_SUMMARY_TITLE                 => SALES_PL_REPORT_MODEL_SUMMARY_URL,
      SALES_PL_REPORT_MODEL_DETAIL_TITLE                  => SALES_PL_REPORT_MODEL_DETAIL_URL
    ),
    SALES_ALLOTMENT_TITLE => array(
      SALES_ALLOTMENT_PROVISIONAL_INCOMING_TITLE          => SALES_ALLOTMENT_PROVISIONAL_INCOMING_URL,
      SALES_ALLOTMENT_INCOMING_TITLE                      => SALES_ALLOTMENT_INCOMING_URL,
      SALES_ALLOTMENT_STOCK_TITLE                         => SALES_ALLOTMENT_STOCK_URL
    ),
    SALES_ALLOTMENT_REPORT_TITLE => array(
      SALES_ALLOTMENT_REPORT_MODEL_TITLE                  => "",
      SALES_ALLOTMENT_REPORT_CUSTOMER_TITLE               => SALES_ALLOTMENT_REPORT_CUSTOMER_URL
    ),
    SALES_DELIVERY_ORDER_TITLE => array(
      SALES_DELIVERY_ORDER_SAVED_TITLE                    => SALES_DELIVERY_ORDER_SAVED_URL,
      SALES_DELIVERY_ORDER_POSTED_TITLE                   => SALES_DELIVERY_ORDER_POSTED_URL
    ),
    SALES_INVOICE_TITLE => array(
      SALES_INVOICE_CREATE_TITLE                          => SALES_INVOICE_URL,
      SALES_INVOICE_SAVED_TITLE                           => SALES_INVOICE_SAVED_URL
    ),
    SALES_INVOICE_REPORT_TITLE => array(
      SALES_INVOICE_REPORT_DATE_TITLE                     => SALES_INVOICE_REPORT_DATE_URL,
      SALES_INVOICE_REPORT_CUSTOMER_TITLE                 => SALES_INVOICE_REPORT_CUSTOMER_URL,
      SALES_ISSUE_INVOICE_REPORT_TITLE                    => SALES_ISSUE_INVOICE_REPORT_URL
    )
  );
?>
