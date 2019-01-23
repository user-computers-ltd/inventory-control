<?php
  /* URL configurations. */
  define("OUT_INVOICING_URL", MENU_URL . "outbound-invoicing/");

  define("OUT_INVOICE_URL", OUT_INVOICING_URL . "invoice/");
  define("OUT_INVOICE_PRINTOUT_URL", OUT_INVOICE_URL . "printout/");
  define("OUT_INVOICE_SAVED_URL", OUT_INVOICE_URL . "saved/");
  define("OUT_INVOICE_PAID_URL", OUT_INVOICE_URL . "paid/");

  define("OUT_INVOICE_REPORT_URL", OUT_INVOICING_URL . "invoice-report/");


  /* Title configurations. */
  define("OUT_INVOICING_TITLE", "(G) Outbound Invoicing");

  define("OUT_INVOICE_TITLE", "(G1) Outbound Invoice");
  define("OUT_INVOICE_PRINTOUT_TITLE", "Outbound Invoice");
  define("OUT_INVOICE_CREATE_TITLE", "(G1a) Create Outbound Invoice");
  define("OUT_INVOICE_SAVED_TITLE", "(G1b) Saved Outbound Invoices");
  define("OUT_INVOICE_PAID_TITLE", "(G1c) Paid Outbound Invoices");

  define("OUT_INVOICE_REPORT_TITLE", "(G2) Outbound Invoicing Report");

  $OUT_INVOICING_MODULE = array(
    OUT_INVOICE_TITLE => array(
      OUT_INVOICE_CREATE_TITLE      => OUT_INVOICE_URL,
      OUT_INVOICE_SAVED_TITLE      => OUT_INVOICE_SAVED_URL,
      OUT_INVOICE_PAID_TITLE        => OUT_INVOICE_PAID_URL
    ),
    OUT_INVOICE_REPORT_TITLE     => OUT_INVOICE_REPORT_URL
  );
?>
