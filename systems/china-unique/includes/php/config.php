<?php
  define("ROOT_PATH", "../../" . SYSTEM_PATH);

  include_once ROOT_PATH . "includes/php/config.php";

  /* System information configuration. */
  define("MYSQL_DATABASE", "china-unique");
  define("TITLE", "華裔針車（深圳）有限公司");
  define("COMPANY_NAME_ENG", "China Unique Sewing Machine (Shenzhen) Ltd.");
  define("COMPANY_NAME_CHI", "華裔針車（深圳）有限公司");
  define("COMPANY_TAX", 16);
  define("COMPANY_CURRENCY", "RMB");
  define("MENU_DIRECTORY", ROOT_PATH . "systems/china-unique/menu");
  date_default_timezone_set("Asia/Taipei");

  $TRANSACTION_CODES = array(
    "R1" => "Stock in",
    "R2" => "Purchase",
    "R3" => "Return of goods sold",
    "R6" => "Transfer of location (Inbound)",
    "R7" => "Goods borrowed",
    "R8" => "Return of goods loaned",
    "R9" => "Cycle check (Gain)",
    "S1" => "Stock out",
    "S2" => "Sales",
    "S3" => "Return of goods purchase",
    "S6" => "Transfer of location (Outbound)",
    "S7" => "Goods loaned",
    "S8" => "Return of goods borrowed",
    "S9" => "Cycle check (Loss)"
  );

  /* URL configurations. */
  define("SYSTEM_URL", BASE_URL . "systems/china-unique/");
  define("MENU_URL", BASE_URL . "systems/china-unique/menu/");

  include_once "sitemap.php";
?>
