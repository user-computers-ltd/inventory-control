CREATE TABLE `pdo_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `do_no`             VARCHAR(30)     NOT NULL,
  `do_date`           DATETIME        NOT NULL,
  `warehouse_code`    VARCHAR(30)     NOT NULL,
  `creditor_code`     VARCHAR(30)     NOT NULL,
  `invoice_no`        VARCHAR(30)     DEFAULT "",
  `remarks`           TEXT,
  `status`            VARCHAR(30)     DEFAULT "POSTED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`do_no`)
);
