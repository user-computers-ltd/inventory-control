CREATE TABLE `ia_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `ia_no`             VARCHAR(30)     NOT NULL,
  `ia_index`          INT(12)         NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `qty`               INT(12)         NOT NULL,
  PRIMARY KEY (`id`)
);
