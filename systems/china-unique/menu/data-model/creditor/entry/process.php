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
          `creditor`
        SET
          code=\"$creditorCode\",
          english_name=\"$englishName\",
          chinese_name=\"$chineseName\",
          billing_address=\"$billingAddress\",
          factory_address=\"$factoryAddress\",
          contact=\"$contact\",
          tel=\"$tel\",
          fax=\"$fax\",
          email=\"$email\",
          credit_term=\"$creditTerm\",
          credit_limit=\"$creditLimit\",
          profile=\"$profile\",
          remarks=\"$remarks\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `creditor`
          (
            code,
            english_name,
            chinese_name,
            billing_address,
            factory_address,
            contact,
            tel,
            fax,
            email,
            credit_term,
            credit_limit,
            profile,
            remarks
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

      $id = query("SELECT id FROM `creditor` WHERE code=\"$creditorCode\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_CREDITOR_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);

  /* If an id is given, attempt to retrieve an existing creditor. */
  if (assigned($id)) {
    $headline = DATA_MODEL_CREDITOR_EDIT_TITLE;

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
        credit_limit          AS `credit_limit`,
        profile               AS `profile`,
        remarks               AS `remarks`
      FROM
        `creditor`
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
