CREATE TABLE `ar_settlement` (
  `id`                INT(12)       NOT NULL AUTO_INCREMENT,
  `invoice_no`        VARCHAR(30)   NOT NULL,
  `settlement_index`  INT(12)       NOT NULL,
  `payment_no`        VARCHAR(30)   NOT NULL,
  `credit_note_no`    VARCHAR(30)   NOT NULL,
  `amount`            DECIMAL(16,6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`invoice_no`, `payment_no`, `credit_note_no`)
);
