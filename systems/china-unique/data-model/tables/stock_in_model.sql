CREATE TABLE `stock_in_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `stock_in_no`       VARCHAR(30)     NOT NULL,
  `stock_in_index`    INT(12)         NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  PRIMARY KEY (`id`)
);
