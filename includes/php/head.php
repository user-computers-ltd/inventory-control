<?php include_once "config.php"; ?>

<meta charset="utf-8">
<meta http-equiv="cache-control" content="no-cache" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>includes/css/main.css">
<script>
  window.addEventListener("focus", function(event) {
    var target = event.target;

    if (target.matches && target.matches("input[type=\"number\"]")) {
      var wheelHandler = function(event) {
        event.preventDefault();
      };

      var blurHandler = function(event) {
        event.target.removeEventListener("wheel", wheelHandler);
        event.target.removeEventListener("blur", blurHandler);
      };

      target.addEventListener("wheel", wheelHandler);
      target.addEventListener("blur", blurHandler);
    }
  }, true);
</script>
