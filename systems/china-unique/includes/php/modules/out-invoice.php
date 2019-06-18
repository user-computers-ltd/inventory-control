<?php
  /* URL configurations. */
  define("OUT_INVOICE_URL", AR_URL . "invoice/");
  define("OUT_INVOICE_PRINTOUT_URL", OUT_INVOICE_URL . "printout/");
  define("OUT_INVOICE_SAVED_URL", OUT_INVOICE_URL . "saved/");
  define("OUT_INVOICE_SETTLED_URL", OUT_INVOICE_URL . "settled/");
  define("OUT_INVOICE_VOIDED_URL", OUT_INVOICE_URL . "voided/");

  /* Title configurations. */
  define("OUT_INVOICE_TITLE", "(B) Invoice");
  define("OUT_INVOICE_PRINTOUT_TITLE", "Invoice");
  define("OUT_INVOICE_CREATE_TITLE", "(B1) Create Invoice");
  define("OUT_INVOICE_SAVED_TITLE", "(B2) Saved Invoices");
  define("OUT_INVOICE_SETTLED_TITLE", "(B3) Settled Invoices");
  define("OUT_INVOICE_VOIDED_TITLE", "(B4) Voided Invoices");

  $OUT_INVOICE_MODULE = array(
    OUT_INVOICE_CREATE_TITLE    => OUT_INVOICE_URL,
    OUT_INVOICE_SAVED_TITLE     => OUT_INVOICE_SAVED_URL,
    OUT_INVOICE_SETTLED_TITLE   => OUT_INVOICE_SETTLED_URL,
    OUT_INVOICE_VOIDED_TITLE    => OUT_INVOICE_VOIDED_URL
  );
?>
