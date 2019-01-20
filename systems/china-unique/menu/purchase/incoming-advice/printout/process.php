<?php
  $ids = $_GET["id"];
  $iaNo = $_POST["ia_no"];
  $iaDate = $_POST["ia_date"];
  $doNo = $_POST["do_no"];
  $warehouseCode = $_POST["warehouse_code"];
  $creditorCode = $_POST["creditor_code"];
  $remarks = $_POST["remarks"];
  $status = $_POST["status"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty"];

  $iaHeaders = array();
  $iaModelList = array();
  $iaModels = array();

  /* If an id is given, retrieve from an existing stock in voucher. */
  if (assigned($ids) && count($ids) > 0) {
    $headerWhereClause = join(" OR ", array_map(function ($i) { return "id=\"$i\""; }, $ids));
    $modelWhereClause = join(" OR ", array_map(function ($i) { return "c.id=\"$i\""; }, $ids));

    $iaHeaders = query("
      SELECT
        ia_no                                     AS `ia_no`,
        DATE_FORMAT(ia_date, '%d-%m-%Y')          AS `date`,
        do_no                                     AS `do_no`,
        warehouse_code                            AS `warehouse_code`,
        creditor_code                             AS `creditor_code`,
        remarks                                   AS `remarks`,
        status                                    AS `status`
      FROM
        `ia_header`
      WHERE
        $headerWhereClause
    ");

    $iaModelList = query("
      SELECT
        a.ia_no                                 AS `ia_no`,
        b.name                                  AS `brand`,
        a.model_no                              AS `model_no`,
        a.qty                                   AS `qty`
      FROM
        `ia_model` AS a
      LEFT JOIN
        `brand` AS b
      ON a.brand_code=b.code
      LEFT JOIN
        `ia_header` AS c
      ON a.ia_no=c.ia_no
      WHERE
        $modelWhereClause
      ORDER BY
        a.ia_no ASC,
        a.ia_index ASC
    ");
  }

  /* If a complete form is given, follow all the data to printout. */
  else if (
    assigned($iaNo) &&
    assigned($iaDate) &&
    assigned($warehouseCode) &&
    assigned($creditorCode) &&
    assigned($status)
  ) {
    $brands = query("SELECT code, name FROM `brand`");
    foreach ($brands as $brand) {
      $brands[$brand["code"]] = $brand["name"];
    }

    $iaDate = new DateTime($iaDate);
    $iaDate = $iaDate->format("d-m-Y");

    $iaHeaders = array(array(
      "ia_no"               => $iaNo,
      "date"                => $iaDate,
      "do_no"               => $doNo,
      "warehouse_code"      => $warehouseCode,
      "creditor_code"       => $creditorCode,
      "remarks"             => $remarks,
      "status"              => $status
    ));

    for ($i = 0; $i < count($brandCodes); $i++) {
      array_push($iaModelList, array(
        "ia_no"             => $iaNo,
        "brand"             => $brands[$brandCodes[$i]],
        "model_no"          => $modelNos[$i],
        "qty"               => $qtys[$i]
      ));
    }
  }

  if (count($iaHeaders) > 0) {
    foreach ($iaHeaders as &$iaHeader) {
      $creditor = query("SELECT english_name AS name FROM `creditor` WHERE code=\"" . $iaHeader["creditor_code"] . "\"")[0];
      $warehouse = query("SELECT name FROM `warehouse` WHERE code=\"" . $iaHeader["warehouse_code"] . "\"")[0];

      $iaHeader["warehouse"] = $iaHeader["warehouse_code"] . " - " . (isset($warehouse) ? $warehouse["name"] : "Unknown");
      $iaHeader["creditor"] = $iaHeader["creditor_code"] . " - " . (isset($creditor) ? $creditor["name"] : "Unknown");
    }
  }

  if (count($iaModelList) > 0) {
    foreach ($iaModelList as $iaModel) {
      $iaNo = $iaModel["ia_no"];

      $arrayPointer = &$iaModels;

      if (!isset($arrayPointer[$iaNo])) {
        $arrayPointer[$iaNo] = array();
      }
      $arrayPointer = &$arrayPointer[$iaNo];

      array_push($arrayPointer, $iaModel);
    }
  }
?>
