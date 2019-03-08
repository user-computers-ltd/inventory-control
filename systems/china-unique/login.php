<?php
  define("SYSTEM_PATH", "");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  session_start();
  $previousURL = assigned($_SESSION["previous_url"]) ? $_SESSION["previous_url"] : SYSTEM_URL . "index.php";

  $username = $_POST["username"];
  $password = $_POST["password"];
  $errorMessage = "";

  if (assigned($username) && assigned($password)) {
    $username = sanitize($username);
    $hashedPassword = hash("sha256", sanitize($password));

    $result = query("SELECT id FROM `user` WHERE username=\"$username\" AND password=\"$hashedPassword\"");

    if (count($result) === 1) {
      $userId = $result[0]["id"];
      $_SESSION["user"] = $username;
      $result = query("UPDATE `user` SET last_login=CURRENT_TIMESTAMP WHERE id=\"$userId\"");

      header("location: $previousURL");
    } else {
      $errorMessage = "Incorrect username or password";
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <div id="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo "Login"; ?></div>
      <form method="post">
        <a class="signup" href="<?php echo SYSTEM_PATH; ?>signup.php">Sign up</a>

        <input type="text" name="username" placeholder="username" value="<?php echo $username; ?>" required autofocus />
        <input type="password" name="password" placeholder="password" value="<?php echo $password; ?>" required />
        <div class="error"><?php echo $errorMessage; ?></div>
        <button type="submit">Login</button>
      </form>
    </div>
  </body>
</html>
