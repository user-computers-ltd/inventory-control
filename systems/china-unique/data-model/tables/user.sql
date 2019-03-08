CREATE TABLE `user` (
  `id`                INT(12)         NOT NULL AUTO_INCREMENT,
  `username`          VARCHAR(30)     NOT NULL,
  `password`          VARCHAR(255)    NOT NULL,
  `access_level`      VARCHAR(30)     DEFAULT "operator",
  `last_login`        DATETIME        DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`username`)
);
