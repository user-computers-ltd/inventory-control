<?php
  /* URL configurations. */
  define("AR_INVOICE_URL", AR_URL . "invoice/");
  define("AR_INVOICE_PRINTOUT_URL", AR_INVOICE_URL . "printout/");
  define("AR_INVOICE_ISSUED_URL", AR_INVOICE_URL . "issued/");
  define("AR_INVOICE_CANCELLED_URL", AR_INVOICE_URL . "cancelled/");
  define("AR_INVOICE_SETTLED_URL", AR_INVOICE_URL . "settled/");
  define("AR_INVOICE_SETTLEMENT_URL", AR_INVOICE_URL . "settlement/");
  define("AR_INVOICE_SETTLEMENT_PRINTOUT_URL", AR_INVOICE_SETTLEMENT_URL . "printout/");

  /* Title configurations. */
  define("AR_INVOICE_TITLE", "(B) Invoice");
  define("AR_INVOICE_PRINTOUT_TITLE", "Invoice");
  define("AR_INVOICE_SETTLEMENT_TITLE", "Invoice Settlement");
  define("AR_INVOICE_CREATE_TITLE", "(B1) Create Invoice");
  define("AR_INVOICE_ISSUED_TITLE", "(B2) Issued Invoices");
  define("AR_INVOICE_SETTLED_TITLE", "(B3) Settled Invoices");
  define("AR_INVOICE_CANCELLED_TITLE", "(B4) Cancelled Invoices");

  $AR_INVOICE_MODULE = array(
    AR_INVOICE_CREATE_TITLE       => AR_INVOICE_URL,
    AR_INVOICE_ISSUED_TITLE       => AR_INVOICE_ISSUED_URL,
    AR_INVOICE_SETTLED_TITLE      => AR_INVOICE_SETTLED_URL,
    AR_INVOICE_CANCELLED_TITLE    => AR_INVOICE_CANCELLED_URL
  );
?>
