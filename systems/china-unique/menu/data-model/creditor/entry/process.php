<?php
  $id = $_GET["id"];
  $creditorCode = $_POST["code"];
  $englishName = $_POST["english_name"];
  $chineseName = $_POST["chinese_name"];
  $billingAddress = $_POST["billing_address"];
  $factoryAddress = $_POST["factory_address"];
  $contact = $_POST["contact"];
  $tel = $_POST["tel"];
  $fax = $_POST["fax"];
  $email = $_POST["email"];
  $creditTerm = $_POST["credit_term"];
  $creditLimit = $_POST["credit_limit"];
  $profile = $_POST["profile"];
  $remarks = $_POST["remarks"];

  /* If a form is submitted, update or insert the creditor. */
  if (assigned($creditorCode)) {
    $queries = array();

    /* If an id is given, update the previous creditor. */
    if (assigned($id)) {
      query("
        UPDATE
          `cu_ap`.`creditor`
        SET
          creditor_name_eng=\"$englishName\",
          creditor_name_chi=\"$chineseName\",
          bill_address=\"$billingAddress\",
          factory_address=\"$factoryAddress\",
          contact_name_l1=\"$contact\",
          tel=\"$tel\",
          fax=\"$fax\",
          email=\"$email\",
          credit_term=\"$creditTerm\",
          credit_limit=\"$creditLimit\",
          company_profile_l1=\"$profile\",
          remarks_l1=\"$remarks\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `cu_ap`.`creditor`
          (
            creditor_code,
            creditor_name_eng,
            creditor_name_chi,
            bill_address,
            factory_address,
            contact_name_l1,
            tel,
            fax,
            email,
            credit_term,
            credit_limit,
            company_profile_l1,
            remarks_l1
          )
        VALUES
          (
            \"$creditorCode\",
            \"$englishName\",
            \"$chineseName\",
            \"$billingAddress\",
            \"$factoryAddress\",
            \"$contact\",
            \"$tel\",
            \"$fax\",
            \"$email\",
            \"$creditTerm\",
            \"$creditLimit\",
            \"$profile\",
            \"$remarks\"
          )
      ");

      $id = query("SELECT id FROM `cu_ap`.`creditor` WHERE creditor_code=\"$creditorCode\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_CREDITOR_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);

  /* If an id is given, attempt to retrieve an existing creditor. */
  if (assigned($id)) {
    $headline = DATA_MODEL_CREDITOR_EDIT_TITLE;

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
        credit_limit          AS `credit_limit`,
        company_profile_l1               AS `profile`,
        remarks_l1               AS `remarks`
      FROM
        `cu_ap`.`creditor`
      WHERE id=\"$id\"
    ")[0];

    if (isset($creditor)) {
      $creditorCode = $creditor["code"];
      $englishName = $creditor["english_name"];
      $chineseName = $creditor["chinese_name"];
      $billingAddress = $creditor["billing_address"];
      $factoryAddress = $creditor["factory_address"];
      $contact = $creditor["contact"];
      $tel = $creditor["tel"];
      $fax = $creditor["fax"];
      $email = $creditor["email"];
      $creditTerm = $creditor["credit_term"];
      $creditLimit = $creditor["credit_limit"];
      $profile = $creditor["profile"];
      $remarks = $creditor["remarks"];
    }
  }

  /* Else, initialize values for a new creditor. */
  else {
    $headline = DATA_MODEL_CREDITOR_CREATE_TITLE;
  }
?>
