CREATE TABLE `po_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `po_no`             VARCHAR(30)     NOT NULL,
  `po_date`           DATETIME        NOT NULL,
  `creditor_code`     VARCHAR(30)     NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`po_no`)
);
