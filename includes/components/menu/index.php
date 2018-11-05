<?php
  include_once ROOT_PATH . "includes/php/utils.php";

  if (defined("MENU_DIRECTORY")) {
    $menuDirectory = MENU_DIRECTORY;
    $menuItems = listDirectory($menuDirectory);
  }

  function generateMenu($menuItems, $subPath) {
    foreach ($menuItems as $menuItem) {
      if (is_array($menuItem)) {
        $name = $menuItem["name"];
        $initial = substr($name, strpos($name, "(") + 1, strrpos($name, ")") - 1);
        $label = substr($name, strpos($name, ")") + 1);
        $expanded = strpos(CURRENT_URL, MENU_URL . $subPath . $name) === 0 ? " expanded" : "";
        echo "<div class=\"menu-item toggle$expanded\" data-name=\"$subPath$name\" onclick=\"toggleMenu('$subPath$name')\"><span class=\"initial\">$initial</span><span class=\"label\">$label</span></div>";
        echo "<div class=\"sub-menu\">";
        generateMenu($menuItem["sub"], "$name/");
        echo "</div>";
      } else {
        $initial = substr($menuItem, strpos($menuItem, "(") + 1, strrpos($menuItem, ")") - 1);
        $label = substr($menuItem, strpos($menuItem, ")") + 1);
        $active = strpos(CURRENT_URL, MENU_URL . $subPath . $menuItem) === 0 ? " active" : "";
        echo "<a class=\"menu-item$active\" href=\"" . MENU_URL . "$subPath$menuItem\"><span class=\"initial\">$initial</span><span class=\"label\">$label</span></a>";
      }
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/menu/style.css">
  </head>
  <body>
    <?php if (defined("MENU_DIRECTORY")) : ?>
      <div id="menu-sidebar" class="web-only">
        <button class="toggle-button" onclick="toggleNav()"></button>
        <a class="menu-item" href="<?php echo $menuDirectory; ?>"><span class="initial">M</span><span class="label">Main Menu</span></a>
        <?php generateMenu($menuItems, ""); ?>
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
