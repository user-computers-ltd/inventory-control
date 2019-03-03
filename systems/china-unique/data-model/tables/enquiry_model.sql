CREATE TABLE `enquiry_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `enquiry_no`        VARCHAR(30)     NOT NULL,
  `enquiry_index`     INT(12)         NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `price`             DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  `qty_allotted`      INT(12)         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`enquiry_no`, `brand_code`,`model_no`)
);
