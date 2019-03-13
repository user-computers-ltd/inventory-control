<?php
  /* URL configurations. */
  define("STOCK_OUT_URL", MENU_URL . "stock-out/");
  define("STOCK_OUT_PRINTOUT_URL", STOCK_OUT_URL . "printout/");
  define("STOCK_OUT_SAVED_URL", STOCK_OUT_URL . "saved/");
  define("STOCK_OUT_POSTED_URL", STOCK_OUT_URL . "posted/");

  /* Title configurations. */
  define("STOCK_OUT_TITLE", "(D) Stock Out");
  define("STOCK_OUT_PRINTOUT_TITLE", "Stock Out Voucher");
  define("STOCK_OUT_CREATE_TITLE", "(D1) Create Stock Out Voucher");
  define("STOCK_OUT_SAVED_TITLE", "(D2) Saved Stock Out Vouchers");
  define("STOCK_OUT_POSTED_TITLE", "(D3) Posted Stock Out Vouchers");

  $STOCK_OUT_MODULE = array(
    STOCK_OUT_CREATE_TITLE   => STOCK_OUT_URL,
    STOCK_OUT_SAVED_TITLE    => STOCK_OUT_SAVED_URL,
    STOCK_OUT_POSTED_TITLE   => STOCK_OUT_POSTED_URL
  );
?>
