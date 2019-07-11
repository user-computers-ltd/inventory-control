<?php
  define("SYSTEM_PATH", "");
  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";

  session_start();
  $previousURL = $_SESSION["previous_url"];

  $username = $_POST["username"];
  $password = $_POST["password"];
  $confirmPassword = $_POST["confirm_password"];
  $errorMessage = "";

  if (assigned($username) && assigned($password) && assigned($confirmPassword)) {
    $username = sanitize($username);
    $hashedPassword = hash("sha256", sanitize($password));

    if ($password !== $confirmPassword) {
      $errorMessage = "Password and confirm password not matched";
    } else {
      $result = query("SELECT id FROM `user` WHERE username=\"$username\"");

      if (count($result) === 1) {
        $errorMessage = "Username already exists";
      } else {
        $result = query("INSERT INTO `user` (username, password) VALUES (\"$username\", \"$hashedPassword\")");
        $_SESSION["user"] = $username;

        header("location: index.php");
        exit();
      }
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
      <div class="headline"><?php echo "Sign up"; ?></div>
      <form method="post">
        <input type="text" name="username" placeholder="username" value="<?php echo $username; ?>" required autofocus />
        <input type="password" name="password" placeholder="password" value="<?php echo $password; ?>" required />
        <input type="password" name="confirm_password" placeholder="confirm password" value="<?php echo $confirmPassword; ?>" required />
        <div class="error"><?php echo $errorMessage; ?></div>
        <button type="submit">Sign up</button>
      </form>
    </div>
  </body>
</html>
