<?php
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once SYSTEM_PATH . "includes/php/authentication.php";

  function generateSitemap($sitemap, $prefix) {
    $currentURL = (strpos(CURRENT_URL, "?") === false) ? CURRENT_URL : substr(CURRENT_URL, 0, strpos(CURRENT_URL, "?"));
    $menu = "";

    foreach ($sitemap as $name => $site) {
      $initial = substr($name, strpos($name, "(") + 1, strrpos($name, ")") - 1);
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
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo SYSTEM_URL; ?>includes/components/menu/style.css">
  </head>
  <body>
    <?php if (isset($SITEMAP)) : ?>
      <div id="menu-sidebar" class="web-only">
        <button class="toggle-button" onclick="toggleNav()"></button>
        <?php echo generateSitemap($SITEMAP[getAccessLevel()], ""); ?>
        <?php if (isset($_SESSION["user"])) : ?>
          <a class="menu-item link logout" href="<?php echo SYSTEM_URL; ?>logout.php">
            <span class="initial">Z</span><span class="label">Logout</span>
          </a>
        <?php endif ?>
      </div>
      <script src="<?php echo BASE_URL; ?>includes/js/utils.js"></script>
      <script>
        var sidebar = document.getElementById("menu-sidebar");
        var toggles = document.querySelectorAll(".toggle");

        function toggleNav() {
          if (sidebar.className.indexOf(" show") !== -1) {
            sidebar.className = sidebar.className.replace(" show", "");
          } else {
            sidebar.className += " show";
          }
        }

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
  </body>
</html>
