CREATE TABLE `enquiry_header` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `enquiry_no`        VARCHAR(30)     NOT NULL,
  `enquiry_date`      DATETIME        NOT NULL,
  `in_charge`         VARCHAR(30)     NOT NULL,
  `debtor_code`       VARCHAR(30)     NOT NULL,
  `debtor_name`       VARCHAR(30)     NOT NULL,
  `currency_code`     VARCHAR(30)     NOT NULL,
  `exchange_rate`     DECIMAL(16,8)   NOT NULL,
  `show_price`        VARCHAR(30)     DEFAULT "FALSE",
  `price_standard`    VARCHAR(30)     NOT NULL,
  `discount`          DECIMAL(5,2)    DEFAULT 0,
  `remarks`           TEXT,
  PRIMARY KEY (`id`)
);
