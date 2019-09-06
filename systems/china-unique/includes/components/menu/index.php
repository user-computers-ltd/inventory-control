<?php
  if (!defined("SYSTEM_PATH")) {
    define("SYSTEM_PATH", "../../../");
  }

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once SYSTEM_PATH . "includes/php/authentication.php";

  $backURL = $_SESSION["back_url"];
  $username = $_SESSION["user"];

  unset($_SESSION["back_url"]);

  function generateSitemap($sitemap, $prefix = "") {
    $currentURL = (strpos(CURRENT_URL, "?") === false) ? CURRENT_URL : substr(CURRENT_URL, 0, strpos(CURRENT_URL, "?"));
    $menu = "";

    foreach ($sitemap as $name => $site) {
      $initial = substr($name, strpos($name, "(") + 1, strpos($name, ")") - 1);
      $label = substr($name, strpos($name, ")") + 1);
      $menuLabel = "<span class=\"initial\">$initial</span><span class=\"label\">$label</span>";
      if (is_array($site)) {
        $subMenu = "<div class=\"sub-menu\">" . generateSitemap($site, "$prefix-$name") . "</div>";
        $expanded = strpos($subMenu, "active") ? "expanded" : "";
        $menu = "$menu<div class=\"menu-item toggle $expanded\" data-name=\"$prefix-$name\" onclick=\"toggleMenu('$prefix-$name')\">$menuLabel</div>$subMenu";
      } else {
        $active = $currentURL == $site ? "active" : "";
        $menu = "$menu<a class=\"menu-item link $active\" href=\"$site\">$menuLabel</a>";
      }
    }

    return $menu;
  }

  $urlPath = str_replace(SYSTEM_URL, "", CURRENT_URL);

  if (strpos($urlPath, "inventory") === 0) {
    $sitemap = $SITEMAP["inventory"][getAccessLevel()];
  } else if (strpos($urlPath, "ar") === 0) {
    $sitemap = $SITEMAP["ar"][getAccessLevel()];
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo SYSTEM_URL; ?>includes/components/menu/style.css">
  </head>
  <body>
    <?php if (isset($sitemap)) : ?>
      <div id="menu-sidebar" class="web-only">
        <a class="menu-item link" href="<?php echo SYSTEM_URL; ?>">
          <span class="initial">←</span><span class="label">Main Menu</span>
        </a>
        <?php echo generateSitemap($sitemap); ?>
        <?php if (isset($username)) : ?>
          <a class="menu-item link logout" href="<?php echo SYSTEM_URL; ?>logout.php">
            <span class="initial"></span><span class="label logout"><?php echo $username; ?> - Log out</span>
          </a>
        <?php endif ?>
      </div>
      <?php if (isset($backURL)) : ?>
        <form id="menu-back" class="web-only" action="<?php echo $backURL; ?>">
          <button type="submit">Back</button>
        </form>
      <?php endif ?>
      <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
      <script>
        var toggles = document.querySelectorAll(".toggle");

        function toggleMenu(menuName) {
          for (var i = 0; i < toggles.length; i++) {
            var element = toggles[i];
            var name = element.dataset.name;

            if (menuName === name) {
              toggleClass(element, "expanded");
            } else {
              toggleClass(element, "expanded", menuName.startsWith(name));
            }
          }
        }
      </script>
    <?php endif ?>
    <?php include_once ROOT_PATH . "includes/components/message-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/confirm-dialog/index.php"; ?>
    <?php include_once ROOT_PATH . "includes/components/loading-screen/index.php"; ?>
  </body>
</html>
