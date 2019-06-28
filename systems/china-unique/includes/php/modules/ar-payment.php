<?php
  /* URL configurations. */
  define("AR_PAYMENT_URL", AR_URL . "payment/");
  define("AR_PAYMENT_PRINTOUT_URL", AR_PAYMENT_URL . "printout/");
  define("AR_PAYMENT_ISSUED_URL", AR_PAYMENT_URL . "issued/");

  /* Title configurations. */
  define("AR_PAYMENT_TITLE", "(C) Payment");
  define("AR_PAYMENT_PRINTOUT_TITLE", "Payment");
  define("AR_PAYMENT_CREATE_TITLE", "(C1) Create Payment");
  define("AR_PAYMENT_ISSUED_TITLE", "(C2) Issued Payments");

  $AR_PAYMENT_MODULE = array(
    AR_PAYMENT_CREATE_TITLE    => AR_PAYMENT_URL,
    AR_PAYMENT_ISSUED_TITLE    => AR_PAYMENT_ISSUED_URL
  );
?>
