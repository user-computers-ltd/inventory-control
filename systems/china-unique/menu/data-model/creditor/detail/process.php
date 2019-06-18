<?php
  $id = $_GET["id"];

  $creditor = query("
    SELECT
      creditor_code                  AS `code`,
      creditor_name_eng          AS `english_name`,
      creditor_name_chi          AS `chinese_name`,
      bill_address       AS `billing_address`,
      factory_address       AS `factory_address`,
      contact_name_l1               AS `contact`,
      tel                   AS `tel`,
      fax                   AS `fax`,
      email                 AS `email`,
      credit_term           AS `credit_term`,
      company_profile_l1               AS `profile`,
      remarks_l1               AS `remarks`
    FROM
      `cu_ap`.`creditor`
    WHERE
      id=\"$id\"
  ")[0];
?>
