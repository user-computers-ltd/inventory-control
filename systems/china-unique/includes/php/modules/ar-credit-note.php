<?php
  /* URL configurations. */
  define("AR_CREDIT_NOTE_URL", AR_URL . "credit-note/");
  define("AR_CREDIT_NOTE_PRINTOUT_URL", AR_CREDIT_NOTE_URL . "printout/");
  define("AR_CREDIT_NOTE_ISSUED_URL", AR_CREDIT_NOTE_URL . "issued/");

  /* Title configurations. */
  define("AR_CREDIT_NOTE_TITLE", "(D) Credit Note");
  define("AR_CREDIT_NOTE_PRINTOUT_TITLE", "Credit Note");
  define("AR_CREDIT_NOTE_CREATE_TITLE", "(D1) Create Credit Note");
  define("AR_CREDIT_NOTE_ISSUED_TITLE", "(D2) Issued Credit Notes");

  $AR_CREDIT_NOTE_MODULE = array(
    AR_CREDIT_NOTE_CREATE_TITLE    => AR_CREDIT_NOTE_URL,
    AR_CREDIT_NOTE_ISSUED_TITLE    => AR_CREDIT_NOTE_ISSUED_URL
  );
?>
