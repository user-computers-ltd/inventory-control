<?php
  include_once "utils.php";
  include_once "config.php";

  if (!defined("MYSQL_HOST") || !defined("MYSQL_USER") || !defined("MYSQL_PASSWORD")) {
    sendErrorPage(array(
      title => "Invalid MySQL connection configuration",
      content => "
        The configuration for connecting the database is either missing or incomplete.
        Please contact your administrator to resolve this in following path:
        <pre>includes/php/config.php</pre>
        Note: Please make sure the followings are set:
        <ul>
          <li><code>MYSQL_HOST</code></li>
          <li><code>MYSQL_USER</code></li>
          <li><code>MYSQL_PASSWORD</code></li>
        </ul>
      "
    ));
  }

  if (defined("MYSQL_DATABASE")) {
    $connection = @mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
  } else {
    $connection = @mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD);
  }

  if (!$connection) {
    sendErrorPage(array(
      title => "Failed to connect to MySQL Server",
      content => "
        The connection to the database has failed to establish.
        Please contact your administrator to resolve this by making sure the
        server is running and the configuration is correct in following path:
        <pre>includes/php/config.php</pre>
        Note: Please make sure the followings are set:
        <ul>
          <li><code>MYSQL_HOST</code></li>
          <li><code>MYSQL_USER</code></li>
          <li><code>MYSQL_PASSWORD</code></li>
        </ul>
      "
    ));
  }

  function selectDatabase($database) {
    if (!mysqli_select_db($GLOBALS["connection"], $database)) {
      sendErrorPage(array(
        title => "Database not found",
        content => "
          The database provided does not exist.
        "
      ));
    };
  }

  function query($sql, $failSafe = false) {
    $result = mysqli_query($GLOBALS["connection"], $sql);

    if (!$result) {
      $error = "Error in query: " . mysqli_error($GLOBALS["connection"]) . " - $sql";

      if ($failSafe == false) {
        throwError($error);
      } else {
        throw new Exception($error);
      }
    }

    if (is_bool($result)) {
      return $result;
    } else {
      $resultArray = array();

      while ($row = mysqli_fetch_array($result)) {
        array_push($resultArray, $row);
      }

      return array_map(function ($row) {
        return array_filter($row, function ($column) { return !is_numeric($column); }, ARRAY_FILTER_USE_KEY);
      }, $resultArray);
    }
  }

  function execute($queries, $failSafe = false) {
    $connection = $GLOBALS["connection"];
    mysqli_autocommit($connection, false);

    $error = "";

    foreach ($queries as $query) {
      $result = mysqli_query($connection, $query);

      if (!$result) {
        $error = "$error\nError in query: " . mysqli_error($connection) . " - $query";
        break;
      }
    }

    if (empty($error)) {
      mysqli_commit($connection);
    } else {
      mysqli_rollback($connection);

      if ($failSafe == false) {
        throwError($error);
      } else {
        throw new Exception($error);
      }
    }
  }
?>
