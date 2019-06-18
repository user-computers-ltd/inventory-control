CREATE TABLE `out_inv_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `invoice_no`        VARCHAR(30)     NOT NULL,
  `invoice_index`     INT(12)         NOT NULL,
  `do_no`             VARCHAR(30)     NOT NULL,
  `stock_out_no`      VARCHAR(30)     NOT NULL,
  `stock_in_no`       VARCHAR(30)     NOT NULL,
  `amount`            DECIMAL(16,6)   NOT NULL,
  `settlement`        VARCHAR(30)     NOT NULL,
  `settle_remarks`    TEXT,
  PRIMARY KEY (`id`)
);
