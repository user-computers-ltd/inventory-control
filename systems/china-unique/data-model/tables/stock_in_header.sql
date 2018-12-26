CREATE TABLE `stock_in_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `stock_in_no`       VARCHAR(30)     NOT NULL,
  `stock_in_date`     DATETIME        NOT NULL,
  `transaction_code`  VARCHAR(30)     NOT NULL,
  `warehouse_code`    VARCHAR(30)     NOT NULL,
  `creditor_code`     VARCHAR(30)     DEFAULT "",
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `net_amount`        DECIMAL(16,6)   NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `tax`               DECIMAL(5,2)    NOT NULL,
  `invoice_no`        VARCHAR(30)     DEFAULT "",
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_in_no` (`stock_in_no`)
);
