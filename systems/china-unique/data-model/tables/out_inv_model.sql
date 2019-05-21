CREATE TABLE `out_inv_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `invoice_no`        VARCHAR(30)     NOT NULL,
  `invoice_index`     INT(12)         NOT NULL,
  `stock_out_no`      VARCHAR(30)     NOT NULL,
  `do_no`             VARCHAR(30)     NOT NULL,
  `amount`            DECIMAL(16,6)   NOT NULL,
  `offset`            DECIMAL(16,6)   NOT NULL,
  `offset_remarks`    TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`invoice_no`, `stock_out_no`, `do_no`)
);
