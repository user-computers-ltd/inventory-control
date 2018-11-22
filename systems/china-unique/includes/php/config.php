<?php
  define("ROOT_PATH", "../../" . SYSTEM_PATH);

  include_once ROOT_PATH . "includes/php/config.php";


  /* System information configuration. */
  define("MYSQL_DATABASE", "china-unique");
  define("TITLE", "華裔針車（深圳）有限公司");
  define("COMPANY_NAME_ENG", "China Unique (Shenzhen) Co., Ltd.");
  define("COMPANY_NAME_CHI", "華裔針車（深圳）有限公司");
  define("COMPANY_TAX", 16);
  define("COMPANY_CURRENCY", "RMB");
  define("MENU_DIRECTORY", ROOT_PATH . "systems/china-unique/menu");
  date_default_timezone_set("Asia/Taipei");


  /* URL configurations. */
  define("SYSTEM_URL", BASE_URL . "systems/china-unique/");
  define("MENU_URL", BASE_URL . "systems/china-unique/menu/");

  define("SALES_URL", MENU_URL . "sales/");
  define("SALES_ORDER_URL", SALES_URL . "sales-order/");
  define("SALES_ORDER_PRINTOUT_URL", SALES_URL . "sales-order/printout/");
  define("SALES_ORDER_SAVED_URL", SALES_URL . "sales-order/saved/");
  define("SALES_ORDER_POSTED_URL", SALES_URL . "sales-order/posted/");
  define("SALES_ORDER_CUSTOMER_URL", SALES_URL . "sales-order/customer/");
  define("SALES_ORDER_MODEL_URL", SALES_URL . "sales-order/model/");
  define("SALES_ORDER_PL_URL", SALES_URL . "sales-order/pl/");

  define("ALLOTMENT_INCOMING_URL", SALES_URL . "allotment/incoming/");
  define("ALLOTMENT_STOCK_SALES_ORDER_URL", SALES_URL . "allotment/stock/sales-order/");
  define("ALLOTMENT_STOCK_MODEL_URL", SALES_URL . "allotment/stock/model/");
  define("ALLOTMENT_CUSTOMER_URL", SALES_URL . "allotment/customer/");

  define("PACKING_LIST_URL", SALES_URL . "packing-list/");
  define("PACKING_LIST_PRINTOUT_URL", SALES_URL . "packing-list/printout/");
  define("PACKING_LIST_INVOICE_URL", SALES_URL . "packing-list/invoice/");
  define("PACKING_LIST_CUSTOMER_URL", SALES_URL . "packing-list/customer/");


  /* Title configurations. */
  define("SALES_TITLE", "(F) Sales");
  define("SALES_ORDER_TITLE", "(F1) Sales Order");
  define("SALES_ORDER_CREATE_TITLE", "(F1a) Create Sales Order");
  define("SALES_ORDER_DETAIL_TITLE", "(F1a) Sales Order Detail");
  define("SALES_ORDER_SAVED_TITLE", "(F1b1) Saved Sales Orders");
  define("SALES_ORDER_POSTED_TITLE", "(F1b2) Posted Sales Orders");
  define("SALES_ORDER_CUSTOMER_TITLE", "(F1c1) Sales Order Summary By Customer");
  define("SALES_ORDER_MODEL_TITLE", "(F1c2) Sales Order Summary By Model");
  define("SALES_ORDER_PL_TITLE", "(F1d) Sales Order P/L Analysis Report");

  define("ALLOTMENT_TITLE", "(F2) Allotment");
  define("ALLOTMENT_INCOMING_TITLE", "(F2a1) Incoming Allotment");
  define("ALLOTMENT_STOCK_SALES_ORDER_TITLE", "(F2a2) Stock Allotment By Sales Order");
  define("ALLOTMENT_STOCK_MODEL_TITLE", "(F2a3) Stock Allotment By Model");
  define("ALLOTMENT_CUSTOMER_TITLE", "(F2b) Allotment Report By Customer");

  define("PACKING_LIST_TITLE", "(F3) Packing List");
  define("PACKING_LIST_DETAIL_TITLE", "(F1a) Packing List Detail");
  define("PACKING_LIST_CUSTOMER_TITLE", "(F3b) Packing List Summary By Customer");


  /* Sitemap configuration. */
  define("SITEMAP", array(
    SALES_TITLE => array(
      SALES_ORDER_TITLE => array(
        SALES_ORDER_CREATE_TITLE      => SALES_ORDER_URL,
        SALES_ORDER_SAVED_TITLE       => SALES_ORDER_SAVED_URL,
        SALES_ORDER_POSTED_TITLE      => SALES_ORDER_POSTED_URL,
        SALES_ORDER_CUSTOMER_TITLE    => SALES_ORDER_CUSTOMER_URL,
        SALES_ORDER_MODEL_TITLE       => SALES_ORDER_MODEL_URL,
        SALES_ORDER_PL_TITLE          => SALES_ORDER_PL_URL
      ),
      ALLOTMENT_TITLE => array(
        ALLOTMENT_INCOMING_TITLE               => ALLOTMENT_INCOMING_URL,
        ALLOTMENT_STOCK_SALES_ORDER_TITLE      => ALLOTMENT_STOCK_SALES_ORDER_URL,
        ALLOTMENT_STOCK_MODEL_TITLE            => ALLOTMENT_STOCK_MODEL_URL,
        ALLOTMENT_CUSTOMER_TITLE               => ALLOTMENT_CUSTOMER_URL
      ),
      PACKING_LIST_TITLE => array(
        PACKING_LIST_CUSTOMER_TITLE  => PACKING_LIST_CUSTOMER_URL
      )
    )
  ));
?>
