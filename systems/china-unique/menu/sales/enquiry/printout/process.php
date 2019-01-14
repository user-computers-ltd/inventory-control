<?php
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty_requested"];
  $qtysAllotted = $_POST["qty"];

  $items = array();

  $brands = query("SELECT code, name FROM `brand`");
  foreach ($brands as $brand) {
    $brands[$brand["code"]] = $brand["name"];
  }

  $date = date("d-m-Y   H:i:s");

  for ($i = 0; $i < count($brandCodes); $i++) {
    array_push($items, array(
      "brand"             => $brands[$brandCodes[$i]],
      "model_no"          => $modelNos[$i],
      "qty"               => $qtys[$i],
      "qty_allotted"      => $qtysAllotted[$i]
    ));
  }
?>
