<?php
  define("SYSTEM_PATH", "../../../");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";

  define("HEADLINE", getURLParentLocation());
  define("SALES_PATH", "../");

  include SALES_PATH . "entry.php";
?>
