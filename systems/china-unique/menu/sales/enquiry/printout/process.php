<?php
  $debtorCode = $_POST["debtor_code"];
  $debtorName = $_POST["debtor_name"];
  $inCharge = $_POST["in_charge"];
  $showPrice = $_POST["show_price"] == "on" ? true : false;
  $currencyCode = $_POST["currency_code"];
  $exchangeRate = $_POST["exchange_rate"];
  $discount = $_POST["discount"];
  $brandCodes = $_POST["brand_code"];
  $modelNos = $_POST["model_no"];
  $qtys = $_POST["qty_requested"];
  $prices = $_POST["price"];
  $qtysAllotted = $_POST["qty"];
  $remarks = $_POST["remarks"];

  $debtor = query("SELECT english_name AS name FROM `debtor` WHERE code=\"$debtorCode\"")[0];
  $debtorName = assigned($debtorName) ? $debtorName : $debtor["name"];
  $client = $debtorName;
  $currency = "$currencyCode @ $exchangeRate";

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
      "price"             => $prices[$i]
    ));
  }
?>
