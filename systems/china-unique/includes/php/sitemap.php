<?php
  include_once "modules/data-model.php";
  include_once "modules/stock-in.php";
  include_once "modules/sales.php";
  include_once "modules/report.php";

  /* Sitemap configuration. */
  $SITEMAP = array(
    DATA_MODEL_TITLE  => $DATA_MODEL_MODULE,
    STOCK_IN_TITLE    => $STOCK_IN_MODULE,
    SALES_TITLE       => $SALES_MODULE,
    REPORT_TITLE      => $REPORT_MODULE
  );
?>
