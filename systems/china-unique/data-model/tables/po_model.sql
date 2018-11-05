CREATE TABLE `po_model` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `po_no`             VARCHAR(30)     NOT NULL,
  `po_index`          INT(12)         NOT NULL,
  `brand_code`        VARCHAR(30)     NOT NULL,
  `model_no`          VARCHAR(30)     NOT NULL,
  `cost`              DECIMAL(16,6)   NOT NULL,
  `qty`               INT(12)         NOT NULL,
  `qty_outstanding`   INT(12)         NOT NULL,
  PRIMARY KEY (`id`)
);
