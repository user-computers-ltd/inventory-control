<?php

  function getParentPath($path) {
    if ($path === "" || substr($path, -3) === "../") {
      return "../" . $path;
    } else {
      return substr($path, 0, -3);
    }
  }

  define("ROOT_PATH", getParentPath(getParentPath(SYSTEM_PATH)));

  include_once ROOT_PATH . "includes/php/config.php";

  /* System information configuration. */
  define("MYSQL_DATABASE", "china-unique");
  define("TITLE", "華裔針車（深圳）有限公司");
  define("COMPANY_NAME_ENG", "China Unique Sewing Machine (Shenzhen) Ltd.");
  define("COMPANY_NAME_CHI", "華裔針車（深圳）有限公司");
  define("COMPANY_ADDRESS", "
    廣東省深圳市福田區
    華富街道濱河路與彩田路交匯處
    聯合廣場 A棟塔樓
    A2210
  ");
  define("COMPANY_TEL", "0755 2360 4173");
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

  $PRODUCT_TYPES = array("M", "S", "O");

  /* URL configurations. */
  define("SYSTEM_URL", BASE_URL . "systems/china-unique/");
  define("MENU_URL", BASE_URL . "systems/china-unique/menu/");

  include_once "sitemap.php";
?>
