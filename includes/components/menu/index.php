<?php
  include_once ROOT_PATH . "includes/php/utils.php";

  $menuItems = listDirectory("system");
?>

<html>
  <head>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/components/menu/style.css">
  </head>
  <body>
    <div id="menu-wrapper" class="web-only">
      <span id="menu-button" onclick="openNav()">☰ Menu</span>
      <div id="sidebar">
        <button class="close-button" onclick="closeNav()">&times;</button>
        <a class="menu-item" href="<?php echo $URL_BASE; ?>">Main Menu</a>
        <?php
          foreach ($menuItems as $menuItem) {
            if (is_array($menuItem)) {
              $name = $menuItem["name"];

              echo "<a href=\"#\" class=\"menu-item toggle\" onclick=\"toggleMenu(event)\">$name</a>";
              echo "<div class=\"sub-menu\">";
              foreach ($menuItem["sub"] as $subMenuItem) {
                echo "<a class=\"menu-item sub\" href=\"" . BASE_URL . "system/menu/$name/$subMenuItem\">$subMenuItem</a>";
              }
              echo "</div>";
            } else {
              echo "<a class=\"menu-item\" href=\"" . BASE_URL . "system/menu/$menuItem\">$menuItem</a>";
            }
          }
        ?>
      </div>
    </div>
    <script>
      var sidebar = document.getElementById("sidebar");

      function openNav() {
        sidebar.className += " show";
      }

      function closeNav() {
        sidebar.className = sidebar.className.replace(" show", "");
      }

      function toggleMenu(event) {
        var expanded = event.target.className.indexOf("expanded") !== -1;
        var toggles = document.querySelectorAll(".toggle");

        for (var i = 0; i < toggles.length; i++) {
          var element = toggles[i];
          element.className = element.className.replace(/\bexpanded\b/g, "");
        }

        if (!expanded) {
          event.target.className += " expanded";
        }
      }
    </script>
  </body>
</html>
