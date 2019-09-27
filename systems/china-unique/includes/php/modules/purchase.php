<?php
  /* URL configurations. */
  define("PURCHASE_URL", INVENTORY_URL . "purchase/");

  // define("PURCHASE_ORDER_URL", PURCHASE_URL . "purchase-order/");
  // define("PURCHASE_ORDER_SAVED_URL", PURCHASE_ORDER_URL . "saved/");
  // define("PURCHASE_ORDER_POSTED_URL", PURCHASE_ORDER_URL . "posted/");
  // define("PURCHASE_ORDER_PRINTOUT_URL", PURCHASE_ORDER_URL . "printout/");
  // define("PURCHASE_ORDER_INTERNAL_PRINTOUT_URL", PURCHASE_ORDER_URL . "internal-printout/");
  //
  // define("INCOMING_ADVICE_URL", PURCHASE_URL . "incoming-advice/");
  // define("INCOMING_ADVICE_SAVED_URL", INCOMING_ADVICE_URL . "saved/");
  // define("INCOMING_ADVICE_CONFIRMED_URL", INCOMING_ADVICE_URL . "confirmed/");
  // define("INCOMING_ADVICE_POSTED_URL", INCOMING_ADVICE_URL . "posted/");
  // define("INCOMING_ADVICE_PRINTOUT_URL", INCOMING_ADVICE_URL . "printout/");
  //
  // define("PURCHASE_ALLOTMENT_URL", PURCHASE_URL . "allotment/");
  //
  // define("PURCHASE_DELIVERY_ORDER_URL", PURCHASE_URL . "delivery-order/");
  // define("PURCHASE_DELIVERY_ORDER_POSTED_URL", PURCHASE_DELIVERY_ORDER_URL . "posted/");


  /* Title configurations. */
  define("PURCHASE_TITLE", "(E) Purchase");

  define("PURCHASE_REPLENISHMENT_TITLE", "(E1) Replenishment");
  define("PURCHASE_REPLENISHMENT_CREATE_TITLE", "(E1a) Create Replenishment Form");
  define("PURCHASE_REPLENISHMENT_SAVED_TITLE", "(E1b) Replenishment Forms Maintenance/ Generate PO");

  define("PURCHASE_ORDER_TITLE", "(E2) Purchase Order");
  define("PURCHASE_ORDER_POSTED_TITLE", "(E2a) All/Outstanding P.O.s");
  define("PURCHASE_HISTORY_LOG_TITLE", "(E2b) P.O. Receipt History");
  define("PURCHASE_MAINTENANCE_TITLE", "(E2c) P.O. Maintenance");
  define("PURCHASE_MAINTENANCE_REPORT_TITLE", "(E2d) P.O. Maintenance Report");

  define("PURCHASE_REPORT_TITLE", "(E3) Outstanding P.O. Analysis Report");
  define("PURCHASE_REPORT_BRAND_TITLE", "(E3a) Outstanding PO. Brand Summary");
  define("PURCHASE_REPORT_BRAND_SUMMARY_TITLE", "(E3b) Outstanding PO By Brand By Model");
  define("PURCHASE_REPORT_BRAND_DETAIL_TITLE", "(E3c) Outstanding PO By Brand By Model (Detail)");
  define("PURCHASE_REPORT_BRAND_SELECTIVE_TITLE", "(E3d) By Selective PO# By Model Range");
  define("PURCHASE_REPORT_BRAND_SUBCLASS_TITLE", "(E3e) Outstanding PO By Model (Inc Sub Class)");
  define("PURCHASE_SHORTAGE_RPORT_TITLE", "(E3f) Shortage Report");

  define("PURCHASE_INCOMING_ADVICE_TITLE", "(E4) Incoming Advice");
  define("PURCHASE_INCOMING_ADVICE_CREATE_TITLE", "(E4a) Create Incoming Advice");
  define("PURCHASE_INCOMING_ADVICE_SAVED_TITLE", "(E4b) Saved Incoming Advices");
  define("PURCHASE_INCOMING_ADVICE_DO_TITLE", "(E4c) DO Incoming Advices");
  define("PURCHASE_INCOMING_ADVICE_POSTED_TITLE", "(E4d) Posted Incoming Advices");

  define("PURCHASE_MONTHLY_REPORT_TITLE", "(E5) Monthly Purchase Report");

  // define("PURCHASE_ORDER_TITLE", "(E1) Purchase Order");
  // define("PURCHASE_ORDER_CREATE_TITLE", "(E1a) Create Purchase Order");
  // define("PURCHASE_ORDER_SAVED_TITLE", "(E3b) Saved Purchase Orders");
  // define("PURCHASE_ORDER_POSTED_TITLE", "(E3c) Posted Purchase Orders");
  // define("PURCHASE_ORDER_PRINTOUT_TITLE", "Purchase Order");
  // define("PURCHASE_ORDER_INTERNAL_PRINTOUT_TITLE", "Purchase Order (Internal)");
  //
  // define("INCOMING_ADVICE_TITLE", "(E2) Incoming Advice");
  // define("INCOMING_ADVICE_CREATE_TITLE", "(E2a) Create Incoming Advice");
  // define("INCOMING_ADVICE_SAVED_TITLE", "(E2b) Saved Incoming Advices");
  // define("INCOMING_ADVICE_CONFIRMED_TITLE", "(E2c) Confirmed Incoming Advices");
  // define("INCOMING_ADVICE_POSTED_TITLE", "(E2d) Posted Incoming Advices");
  // define("INCOMING_ADVICE_PRINTOUT_TITLE", "Incoming Advice");
  //
  // define("PURCHASE_ALLOTMENT_TITLE", "(E3) Allotment");
  //
  // define("PURCHASE_DELIVERY_ORDER_TITLE", "(E4) Delivery Order");
  // define("PURCHASE_DELIVERY_ORDER_POSTED_TITLE", "(E4a) Posted Delivery Orders");


  $PURCHASE_MODULE = array(
    PURCHASE_REPLENISHMENT_TITLE => array(
      PURCHASE_REPLENISHMENT_CREATE_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/rep_po/add.php",
      PURCHASE_REPLENISHMENT_SAVED_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/rep_po/list_saved.php"
    ),
    PURCHASE_ORDER_TITLE => array(
      PURCHASE_ORDER_POSTED_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/print_list_saved.php",
      PURCHASE_HISTORY_LOG_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/po_log.htm",
      PURCHASE_MAINTENANCE_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/po_amendment.htm",
      PURCHASE_MAINTENANCE_REPORT_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/report_po_dummy_log.php"
    ),
    PURCHASE_REPORT_TITLE => array(
      PURCHASE_REPORT_BRAND_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/report_brand.php",
      PURCHASE_REPORT_BRAND_SUMMARY_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/report_brandmodelsummary.php",
      PURCHASE_REPORT_BRAND_DETAIL_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/report_brandmodeldetails.php",
      PURCHASE_REPORT_BRAND_SELECTIVE_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/report_brandmodelselective.php",
      PURCHASE_REPORT_BRAND_SUBCLASS_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/po/report_brandmodelsubclass.php",
      PURCHASE_SHORTAGE_RPORT_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/enquiry/j_shortage.php"
    ),
    PURCHASE_INCOMING_ADVICE_TITLE => array(
      PURCHASE_INCOMING_ADVICE_CREATE_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/ia/add.php",
      PURCHASE_INCOMING_ADVICE_SAVED_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/ia/list_saved_ia.php",
      PURCHASE_INCOMING_ADVICE_DO_TITLE =>  "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/ia/list_saved_ia_3.php",
      PURCHASE_INCOMING_ADVICE_POSTED_TITLE =>  "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/ia/list_posted_ia.php"
    ),
    PURCHASE_MONTHLY_REPORT_TITLE => "http://www.lsmbv.com.hk:8000/idb/cu_inventory/s_i/ia/pending_report.htm"

    // PURCHASE_ORDER_TITLE => array(
    //   PURCHASE_ORDER_CREATE_TITLE => PURCHASE_ORDER_URL,
    //   PURCHASE_ORDER_SAVED_TITLE => PURCHASE_ORDER_SAVED_URL,
    //   PURCHASE_ORDER_POSTED_TITLE => PURCHASE_ORDER_POSTED_URL
    // ),
    // INCOMING_ADVICE_TITLE => array(
    //   INCOMING_ADVICE_CREATE_TITLE => INCOMING_ADVICE_URL,
    //   INCOMING_ADVICE_SAVED_TITLE => INCOMING_ADVICE_SAVED_URL,
    //   INCOMING_ADVICE_CONFIRMED_TITLE => INCOMING_ADVICE_CONFIRMED_URL,
    //   INCOMING_ADVICE_POSTED_TITLE => INCOMING_ADVICE_POSTED_URL
    // ),
    // PURCHASE_ALLOTMENT_TITLE => PURCHASE_ALLOTMENT_URL,
    // PURCHASE_DELIVERY_ORDER_TITLE => array(
    //   PURCHASE_DELIVERY_ORDER_POSTED_TITLE => PURCHASE_DELIVERY_ORDER_POSTED_URL
    // )
  );
?>
