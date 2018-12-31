<?php
  $id = $_GET["id"];

  $creditor = query("
    SELECT
      code                  AS `code`,
      english_name          AS `english_name`,
      chinese_name          AS `chinese_name`,
      billing_address       AS `billing_address`,
      factory_address       AS `factory_address`,
      contact               AS `contact`,
      tel                   AS `tel`,
      fax                   AS `fax`,
      email                 AS `email`,
      credit_term           AS `credit_term`,
      profile               AS `profile`,
      remarks               AS `remarks`
    FROM
      `creditor`
    WHERE
      id=\"$id\"
  ")[0];
?>
