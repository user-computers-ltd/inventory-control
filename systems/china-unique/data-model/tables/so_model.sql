CREATE TABLE `so_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `so_no`             VARCHAR(30)     NOT NULL,
  `so_index`          INT(12)         NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  `qty_outstanding`   INT(12)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`so_no`, `brand_code`,`model_no`)
);
