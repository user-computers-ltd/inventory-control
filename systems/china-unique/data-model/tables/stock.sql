CREATE TABLE `stock` (
  `id`              INT(12)         NOT NULL AUTO_INCREMENT,
  `brand_code`      VARCHAR(30)     NOT NULL,
  `model_no`        VARCHAR(30)     NOT NULL,
  `warehouse_code`  VARCHAR(30)     NOT NULL,
  `qty`             INT(12)         DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock` (`brand_code`, `model_no`, `warehouse_code`)
);
