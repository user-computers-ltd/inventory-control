<?php
  /* URL configurations. */
  define("REPORT_URL", MENU_URL . "report/");
  define("REPORT_TRANSACTION_LOG_URL", REPORT_URL . "transaction-log/");


  /* Title configurations. */
  define("REPORT_TITLE", "(G) Management Report");
  define("REPORT_TRANSACTION_LOG_TITLE", "(G1) Transaction Log");

  $REPORT_MODULE = array(
    REPORT_TRANSACTION_LOG_TITLE => REPORT_TRANSACTION_LOG_URL
  );
?>
