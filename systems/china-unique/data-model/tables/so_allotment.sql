CREATE TABLE `so_allotment` (
  `id`                INT(12)       NOT NULL AUTO_INCREMENT,
  `ia_no`             VARCHAR(30)   NOT NULL,
  `warehouse_code`    VARCHAR(30)   NOT NULL,
  `so_no`             VARCHAR(30)   NOT NULL,
  `brand_code`        VARCHAR(30)   NOT NULL,
  `model_no`          VARCHAR(30)   NOT NULL,
  `qty`               INT(12)       NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`ia_no`, `warehouse_code`, `so_no`, `brand_code`,`model_no`)
);
