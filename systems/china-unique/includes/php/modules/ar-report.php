<?php
  /* URL configurations. */
  define("AR_REPORT_URL", AR_URL . "report/");
  define("AR_REPORT_MONTHLY_SALES_URL", AR_REPORT_URL . "monthly-sales/");
  define("AR_REPORT_CLIENT_URL", AR_REPORT_URL . "client/");
  define("AR_REPORT_CLIENT_STATISTICS_URL", AR_REPORT_CLIENT_URL . "statistics/");
  define("AR_REPORT_CLIENT_OUTSTANDING_URL", AR_REPORT_CLIENT_URL . "outstanding/");
  define("AR_REPORT_CLIENT_STATEMENT_URL", AR_REPORT_CLIENT_URL . "statement/");
  define("AR_REPORT_CLIENT_STATEMENT_PRINTOUT_URL", AR_REPORT_CLIENT_STATEMENT_URL . "printout/");

  /* Title configurations. */
  define("AR_REPORT_TITLE", "(E) Management Report");
  define("AR_REPORT_MONTHLY_SALES_TITLE", "(E1) Monthly Sales Report By Invoice Date ");
  define("AR_REPORT_CLIENT_STATISTICS_TITLE", "(E2) Customer Statistics");
  define("AR_REPORT_CLIENT_OUTSTANDING_TITLE", "(E3) Customer Outstanding By Maturity Date");
  define("AR_REPORT_CLIENT_STATEMENT_TITLE", "(E4) Customer Statements");
  define("AR_REPORT_CLIENT_STATEMENT_PRINTOUT_TITLE", "Customer Statement（月結單）");

  $AR_REPORT_MODULE = array(
    AR_REPORT_MONTHLY_SALES_TITLE       => AR_REPORT_MONTHLY_SALES_URL,
    AR_REPORT_CLIENT_STATISTICS_TITLE   => AR_REPORT_CLIENT_STATISTICS_URL,
    AR_REPORT_CLIENT_OUTSTANDING_TITLE  => AR_REPORT_CLIENT_OUTSTANDING_URL,
    AR_REPORT_CLIENT_STATEMENT_TITLE    => AR_REPORT_CLIENT_STATEMENT_URL
  );
?>
