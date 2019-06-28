<?php
  define("SYSTEM_PATH", "../../");

  include_once SYSTEM_PATH . "includes/php/config.php";
  include_once ROOT_PATH . "includes/php/utils.php";
  include_once ROOT_PATH . "includes/php/database.php";
  include_once SYSTEM_PATH . "includes/php/actions.php";
  include "process.php";
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <?php include_once SYSTEM_PATH . "includes/php/head.php"; ?>
    <link rel="stylesheet" href="style.css">
  </head>
  <body>
    <?php include_once SYSTEM_PATH . "includes/components/menu/index.php"; ?>
    <div class="page-wrapper">
      <?php include_once SYSTEM_PATH . "includes/components/header/index.php"; ?>
      <div class="headline"><?php echo $headline; ?></div>
      <?php if (assigned($paymentNo)) : ?>
        <form id="payment-form" method="post">
          <table id="payment-header">
            <tr>
              <td>Payment No.:</td>
              <td><input type="text" name="payment_no" value="<?php echo $paymentNo; ?>" required /></td>
            </tr>
            <tr>
              <td>Date:</td>
              <td><input type="date" name="payment_date" value="<?php echo $paymentDate; ?>" max="<?php echo date("Y-m-d"); ?>" required /></td>
            </tr>
            <tr>
              <td>Client:</td>
              <td>
                <select id="debtor-code" name="debtor_code" required>
                  <?php
                    foreach ($debtors as $code => $debtor) {
                      $label = "$code - " . $debtor["name"];
                      $selected = $debtorCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$label</option>";
                    }
                  ?>
                </select>
              </td>
            </tr>
            <tr>
              <td>Currency:</td>
              <td>
                <select id="currency-code" name="currency_code" onchange="onCurrencyCodeChange()" required>
                  <?php
                    foreach ($currencies as $code => $rate) {
                      $selected = $currencyCode == $code ? "selected" : "";
                      echo "<option value=\"$code\" $selected>$code</option>";
                    }
                  ?>
                </select>
                <input
                  id="exchange-rate"
                  name="exchange_rate"
                  type="number"
                  step="0.00000001"
                  min="0.00000001"
                  value="<?php echo $exchangeRate; ?>"
                  required
                  <?php echo $currencyCode === COMPANY_CURRENCY ? "readonly" : ""; ?>
                />
              </td>
            </tr>
            <tr>
              <td>Payment Amount:</td>
              <td><input id="payment-amount" type="number" name="amount" step="0.01" min="0" ondragover=""value="<?php echo $amount; ?>" required /></td>
            </tr>
            <tr>
              <td>Remarks:</td>
              <td colspan="3"><textarea id="remarks" name="remarks"><?php echo $remarks; ?></textarea></td>
            </tr>
          </table>
          <?php if ($status === "DRAFT" || $status === "SAVED") : ?>
            <button name="action" type="submit" value="<?php echo $status === "DRAFT" ? "create" : "update"; ?>">Save</button>
          <?php endif ?>
          <?php if ($status === "SAVED") : ?>
            <button name="action" type="submit" value="delete">Delete</button>
          <?php endif ?>
        </form>
        <script>
          var currencies = <?php echo json_encode($currencies); ?>;
          var currencyCodeElement = document.querySelector("#currency-code");
          var exchangeRateElement = document.querySelector("#exchange-rate");

          function onCurrencyCodeChange() {
            var currencyCode = currencyCodeElement.value;

            exchangeRateElement.value = currencies[currencyCode];
            if (currencyCode === "<?php echo COMPANY_CURRENCY; ?>") {
              exchangeRateElement.setAttribute("readonly", true);
            } else {
              exchangeRateElement.removeAttribute("readonly");
            }
          }
        </script>
      <?php else : ?>
        <div id="payment-entry-not-found">Payment not found</div>
      <?php endif ?>
    </div>
  </body>
</html>
