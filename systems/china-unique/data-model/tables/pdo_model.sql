CREATE TABLE `pdo_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `do_no`             VARCHAR(30)     NOT NULL,
  `do_index`          INT(12)         NOT NULL,
  `po_no`             VARCHAR(30)     NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `tax`               DECIMAL(5,2)    NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`do_no`, `po_no`, `brand_code`,`model_no`)
);
