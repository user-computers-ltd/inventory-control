CREATE TABLE `ar_payment` (
  `id`                INT(12)       NOT NULL AUTO_INCREMENT,
  `payment_no`        VARCHAR(30)   NOT NULL,
  `payment_date`      DATETIME      NOT NULL,
  `debtor_code`       VARCHAR(30)   NOT NULL,
  `currency_code`     VARCHAR(30)   NOT NULL,
  `exchange_rate`     DECIMAL(16,8) NOT NULL,
  `amount`            DECIMAL(16,6) NOT NULL,
  `remarks`           TEXT,
  `status`            VARCHAR(30)   DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`payment_no`)
);
