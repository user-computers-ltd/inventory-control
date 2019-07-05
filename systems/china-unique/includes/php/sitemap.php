<?php
  include_once "modules/data-model.php";
  include_once "modules/stock-in.php";
  include_once "modules/stock-out.php";
  include_once "modules/purchase.php";
  include_once "modules/sales.php";
  include_once "modules/report.php";
  include_once "modules/ar-invoice.php";
  include_once "modules/ar-payment.php";
  include_once "modules/ar-credit-note.php";
  include_once "modules/ar-report.php";

  /* Sitemap configuration in terms of access level. */
  $SITEMAP = array(
    "inventory" => array(
      "admin" => array(
        DATA_MODEL_TITLE      => $DATA_MODEL_MODULE,
        STOCK_IN_TITLE        => $STOCK_IN_MODULE,
        STOCK_OUT_TITLE       => $STOCK_OUT_MODULE,
        PURCHASE_TITLE        => $PURCHASE_MODULE,
        SALES_TITLE           => $SALES_MODULE,
        REPORT_TITLE          => $REPORT_MODULE
      ),
      "manager" => array(
        DATA_MODEL_TITLE      => $DATA_MODEL_MODULE,
        STOCK_IN_TITLE        => $STOCK_IN_MODULE,
        STOCK_OUT_TITLE       => $STOCK_OUT_MODULE,
        PURCHASE_TITLE        => $PURCHASE_MODULE,
        SALES_TITLE           => $SALES_MODULE,
        REPORT_TITLE          => $REPORT_MODULE
      ),
      "supervisor" => array(
        DATA_MODEL_TITLE      => $DATA_MODEL_MODULE,
        STOCK_IN_TITLE        => $STOCK_IN_MODULE,
        STOCK_OUT_TITLE       => $STOCK_OUT_MODULE,
        PURCHASE_TITLE        => $PURCHASE_MODULE,
        SALES_TITLE           => $SALES_MODULE,
        REPORT_TITLE          => $REPORT_MODULE
      ),
      "operator" => array(
        SALES_TITLE => array(
          SALES_ENQUIRY_TITLE => array(
            SALES_ENQUIRY_CREATE_TITLE            => SALES_ENQUIRY_URL,
            SALES_ENQUIRY_SAVED_TITLE             => SALES_ENQUIRY_SAVED_URL
          )
        )
      )
    ),

    "ar" => array(
      "admin" => array(
        AR_INVOICE_TITLE      => $AR_INVOICE_MODULE,
        AR_PAYMENT_TITLE      => $AR_PAYMENT_MODULE,
        AR_CREDIT_NOTE_TITLE  => $AR_CREDIT_NOTE_MODULE,
        AR_REPORT_TITLE       => $AR_REPORT_MODULE
      ),
      "manager" => array(
        AR_INVOICE_TITLE      => $AR_INVOICE_MODULE,
        AR_PAYMENT_TITLE      => $AR_PAYMENT_MODULE,
        AR_CREDIT_NOTE_TITLE  => $AR_CREDIT_NOTE_MODULE,
        AR_REPORT_TITLE       => $AR_REPORT_MODULE
      ),
      "supervisor" => array(
        AR_INVOICE_TITLE      => $AR_INVOICE_MODULE,
        AR_PAYMENT_TITLE      => $AR_PAYMENT_MODULE,
        AR_CREDIT_NOTE_TITLE  => $AR_CREDIT_NOTE_MODULE,
        AR_REPORT_TITLE       => $AR_REPORT_MODULE
      ),
      "operator" => array(

      )
    )
  );
?>
