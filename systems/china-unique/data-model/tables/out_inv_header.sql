CREATE TABLE `out_inv_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `invoice_no`        VARCHAR(30)     NOT NULL,
  `invoice_date`      DATETIME        NOT NULL,
  `debtor_code`       VARCHAR(30)     NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`invoice_no`)
);
