<?php
  define("ROOT_PATH", "");

  session_start();
  $error = $_SESSION["error"];
  $title = isset($error["title"]) ? $error["title"] : "Unknown Error";
  $content = isset($error["content"]) ? $error["content"] : "
    An error has occurred with an unknown cause. Please contact your administrator to resolve this.
  ";
  $previousURL = $error["url"];
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Inventory Control | Error</title>
    <?php include_once ROOT_PATH . "includes/php/head.php"; ?>
    <style>
      body {
        background-color: #eeeeee;
        background-image: url("data:image/svg+xml,%3Csvg width='84' height='48' viewBox='0 0 84 48' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h12v6H0V0zm28 8h12v6H28V8zm14-8h12v6H42V0zm14 0h12v6H56V0zm0 8h12v6H56V8zM42 8h12v6H42V8zm0 16h12v6H42v-6zm14-8h12v6H56v-6zm14 0h12v6H70v-6zm0-16h12v6H70V0zM28 32h12v6H28v-6zM14 16h12v6H14v-6zM0 24h12v6H0v-6zm0 8h12v6H0v-6zm14 0h12v6H14v-6zm14 8h12v6H28v-6zm-14 0h12v6H14v-6zm28 0h12v6H42v-6zm14-8h12v6H56v-6zm0-8h12v6H56v-6zm14 8h12v6H70v-6zm0 8h12v6H70v-6zM14 24h12v6H14v-6zm14-8h12v6H28v-6zM14 8h12v6H14V8zM0 8h12v6H0V8z' fill='%23000000' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
      }

      .page-wrapper {
        position: absolute;
        top: 50%;
        left: 50%;
        width: 500px;
        max-width: 100%;
        max-height: 100%;
        transform: translate(-50%, -50%);
        padding: 20px;
        background-color: white;
        border-radius: 5px;
        border-bottom: 2px solid darkgray;
        box-sizing: border-box;
        overflow: auto;
      }

      .page-wrapper #title {
        text-align: center;
      }

      .page-wrapper #content {
        line-height: 24px;
      }

      .page-wrapper #retry-link {
        display: block;
        margin-top: 20px;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <div class="page-wrapper">
      <h2 id="title"><?php echo $title; ?></h2>
      <div id="content">
        <?php echo $content; ?>
      </div>
      <a id="retry-link" href="<?php echo $previousURL; ?>">Retry</a>
    </div>
  </body>
</html>
