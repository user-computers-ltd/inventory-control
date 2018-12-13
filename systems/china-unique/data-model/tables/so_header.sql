CREATE TABLE `so_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `so_no`             VARCHAR(30)     NOT NULL,
  `so_date`           DATETIME        NOT NULL,
  `debtor_code`       VARCHAR(30)     NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `tax`               DECIMAL(5,2)    NOT NULL,
  `remarks`           TEXT,
  `priority`          INT(12)         DEFAULT 0,
  `status`            VARCHAR(30)     DEFAULT "SAVED",
  PRIMARY KEY (`id`),
  UNIQUE KEY `so_no` (`so_no`)
);
