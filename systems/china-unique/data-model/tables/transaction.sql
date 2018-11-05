CREATE TABLE `transaction` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `header_no`         VARCHAR(30)     NOT NULL,
  `transaction_code`  INT(12)         NOT NULL,
  `transaction_date`  DATETIME        NOT NULL,
  `client_code`       VARCHAR(30)     NOT NULL,
  `currency`          VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `tax`               DECIMAL(5,2)    NOT NULL,
  `warehouse_code`    VARCHAR(30)     NOT NULL,
  PRIMARY KEY (`id`)
);
