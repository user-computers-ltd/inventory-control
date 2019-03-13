<?php
  /* URL configurations. */
  define("STOCK_IN_URL", MENU_URL . "stock-in/");
  define("STOCK_IN_PRINTOUT_URL", STOCK_IN_URL . "printout/");
  define("STOCK_IN_SAVED_URL", STOCK_IN_URL . "saved/");
  define("STOCK_IN_POSTED_URL", STOCK_IN_URL . "posted/");

  /* Title configurations. */
  define("STOCK_IN_TITLE", "(C) Stock In");
  define("STOCK_IN_PRINTOUT_TITLE", "Stock In Voucher");
  define("STOCK_IN_CREATE_TITLE", "(C1) Create Stock In Voucher");
  define("STOCK_IN_SAVED_TITLE", "(C2) Saved Stock In Vouchers");
  define("STOCK_IN_POSTED_TITLE", "(C3) Posted Stock In Vouchers");

  $STOCK_IN_MODULE = array(
    STOCK_IN_CREATE_TITLE    => STOCK_IN_URL,
    STOCK_IN_SAVED_TITLE     => STOCK_IN_SAVED_URL,
    STOCK_IN_POSTED_TITLE    => STOCK_IN_POSTED_URL
  );
?>
