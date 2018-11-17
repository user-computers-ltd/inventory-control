CREATE TABLE `ia_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `ia_no`             VARCHAR(30)     NOT NULL,
  `ia_date`           DATETIME        NOT NULL,
  `creditor_code`     VARCHAR(30)     NOT NULL,
  `warehouse_code`    VARCHAR(30)     NOT NULL,
  `ref_no`            VARCHAR(30)     DEFAULT "",
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`ia_no`)
);
