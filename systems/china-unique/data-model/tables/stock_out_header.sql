CREATE TABLE `stock_out_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `stock_out_no`      VARCHAR(30)     NOT NULL,
  `stock_out_date`    DATETIME        NOT NULL,
  `transaction_code`  VARCHAR(30)     NOT NULL,
  `debtor_code`       VARCHAR(30)     DEFAULT "",
  `net_amount`        DECIMAL(16,6)   NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `tax`               DECIMAL(5,2)    NOT NULL,
  `product_type`      VARCHAR(30)     NOT NULL,
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_out_no` (`stock_out_no`)
);
