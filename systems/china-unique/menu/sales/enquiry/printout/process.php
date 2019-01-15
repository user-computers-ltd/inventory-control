<?php
  $debtorCode = $_POST["debtor_code"];
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty_requested"];
  $prices = $_POST["price"];
  $qtysAllotted = $_POST["qty"];
  $inCharge = $_POST["in_charge"];
  $remarks = $_POST["remarks"];

  $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"$debtorCode\"")[0];
  $client = $debtorCode . " - " . $debtor["name"];
  $currency = $currencyCode . " @ " . $exchangeRate;

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
      "qty_allotted"      => $qtysAllotted[$i],
      "price"      => $prices[$i]
    ));
  }
?>
