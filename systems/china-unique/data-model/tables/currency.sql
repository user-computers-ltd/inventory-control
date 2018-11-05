CREATE TABLE `currency` (
  `id`    INT(12)         NOT NULL AUTO_INCREMENT,
  `code`  VARCHAR(30)     NOT NULL,
  `name`  VARCHAR(50)     NOT NULL,
  `rate`  DECIMAL(16,8)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
);
