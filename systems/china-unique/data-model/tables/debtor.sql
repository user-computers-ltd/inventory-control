CREATE TABLE `debtor` (
  `id`               INT(12)       NOT NULL AUTO_INCREMENT,
  `code`             VARCHAR(30)   NOT NULL,
  `english_name`     VARCHAR(50)   DEFAULT "",
  `chinese_name`     VARCHAR(50)   DEFAULT "",
  `billing_address`  TEXT,
  `factory_address`  TEXT,
  `tel`              VARCHAR(50)   DEFAULT "",
  `fax`              VARCHAR(50)   DEFAULT "",
  `contact`          TEXT,
  `profile`          TEXT,
  `email`            VARCHAR(255)  DEFAULT "",
  `remarks`          TEXT,
  `credit_term`      INT(12)       DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
);
