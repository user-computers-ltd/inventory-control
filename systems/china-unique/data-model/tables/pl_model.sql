CREATE TABLE `pl_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `pl_no`             VARCHAR(30)     NOT NULL,
  `pl_index`          INT(12)         NOT NULL,
  `so_no`             VARCHAR(30)     NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  PRIMARY KEY (`id`)
);
