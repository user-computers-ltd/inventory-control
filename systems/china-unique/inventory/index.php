<?php
  define("SYSTEM_PATH", "../");
  include_once SYSTEM_PATH . "includes/php/config.php";

  function generateIndexMenu($sitemap, $prefix = "") {
    $menu = "";

    foreach ($sitemap as $name => $site) {
      $initial = substr($name, strpos($name, "(") + 1, strrpos($name, ")") - 1);
      $label = substr($name, strpos($name, ")") + 1);
      $menuLabel = "<span class=\"initial\">$initial</span><span class=\"label\">$label</span>";
      if (is_array($site)) {
        $subMenu = "<div class=\"sub-menu\">" . generateSitemap($site, "$prefix-$name") . "</div>";
        $expanded = strpos($subMenu, "active") ? "expanded" : "";
        $menu = "$menu<div class=\"menu-item toggle $expanded\" data-name=\"$prefix-$name\">$menuLabel</div>$subMenu";
      } else {
        $menu = "$menu<a class=\"menu-item link $active\" href=\"$site\">$menuLabel</a>";
      }
    }

    return $menu;
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div id="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <?php
        if (isset($sitemap)) {
          echo generateIndexMenu($sitemap);
        }
      ?>
    </div>
  </body>
</html>
