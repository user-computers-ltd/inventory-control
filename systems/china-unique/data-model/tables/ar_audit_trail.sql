CREATE TABLE `ar_audit_trail` (
  `action`            VARCHAR(30)   NOT NULL,
  `datetime`          DATETIME      NOT NULL,
  `invoice_no`        VARCHAR(30)   NOT NULL,
  `invoice_date`      VARCHAR(30)   NOT NULL,
  `debtor_code`       VARCHAR(30)   NOT NULL,
  `currency_code`     VARCHAR(30)   NOT NULL,
  `exchange_rate`     VARCHAR(30)   NOT NULL,
  `maturity_date`     VARCHAR(30)   NOT NULL,
  `remarks`           TEXT,
  `amount`            VARCHAR(30),
  `balance`           VARCHAR(30),
  `username`          VARCHAR(30)   NOT NULL
);
