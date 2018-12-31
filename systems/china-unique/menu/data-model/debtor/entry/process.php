<?php
  $id = $_GET["id"];
  $debtorCode = $_POST["code"];
  $englishName = $_POST["english_name"];
  $chineseName = $_POST["chinese_name"];
  $billingAddress = $_POST["billing_address"];
  $factoryAddress = $_POST["factory_address"];
  $contact = $_POST["contact"];
  $tel = $_POST["tel"];
  $fax = $_POST["fax"];
  $email = $_POST["email"];
  $creditTerm = $_POST["credit_term"];
  $profile = $_POST["profile"];
  $remarks = $_POST["remarks"];

  /* If a form is submitted, update or insert the debtor. */
  if (assigned($debtorCode)) {
    $queries = array();

    /* If an id is given, update the previous debtor. */
    if (assigned($id)) {
      query("
        UPDATE
          `debtor`
        SET
          code=\"$debtorCode\",
          english_name=\"$englishName\",
          chinese_name=\"$chineseName\",
          billing_address=\"$billingAddress\",
          factory_address=\"$factoryAddress\",
          contact=\"$contact\",
          tel=\"$tel\",
          fax=\"$fax\",
          email=\"$email\",
          credit_term=\"$creditTerm\",
          profile=\"$profile\"
          remarks=\"$remarks\"
        WHERE
          id=\"$id\"
      ");
    } else {
      query("
        INSERT INTO
          `debtor`
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
            profile,
            remarks
          )
        VALUES
          (
            \"$debtorCode\",
            \"$englishName\",
            \"$chineseName\",
            \"$billingAddress\",
            \"$factoryAddress\",
            \"$contact\",
            \"$tel\",
            \"$fax\",
            \"$email\",
            \"$creditTerm\",
            \"$profile\",
            \"$remarks\"
          )
      ");

      $id = query("SELECT id FROM `debtor` WHERE code=\"$debtorCode\"")[0]["id"];
    }

    header("Location: " . DATA_MODEL_DEBTOR_DETAIL_URL . "?id=$id");
  }

  $editMode = assigned($id);

  /* If an id is given, attempt to retrieve an existing debtor. */
  if (assigned($id)) {
    $headline = DATA_MODEL_DEBTOR_EDIT_TITLE;

    $debtor = query("
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
        `debtor`
      WHERE id=\"$id\"
    ")[0];

    if (isset($debtor)) {
      $debtorCode = $debtor["code"];
      $englishName = $debtor["english_name"];
      $chineseName = $debtor["chinese_name"];
      $billingAddress = $debtor["billing_address"];
      $factoryAddress = $debtor["factory_address"];
      $contact = $debtor["contact"];
      $tel = $debtor["tel"];
      $fax = $debtor["fax"];
      $email = $debtor["email"];
      $creditTerm = $debtor["credit_term"];
      $profile = $debtor["profile"];
      $remarks = $debtor["remarks"];
    }
  }

  /* Else, initialize values for a new debtor. */
  else {
    $headline = DATA_MODEL_DEBTOR_CREATE_TITLE;
  }
?>
