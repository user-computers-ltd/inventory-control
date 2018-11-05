CREATE TABLE `brand` (
  `id`                  INT(12)       NOT NULL AUTO_INCREMENT,
  `code`                VARCHAR(30)   NOT NULL,
  `name`                VARCHAR(50)   DEFAULT "",
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
);
