CREATE TABLE `capapreta`.`objetivos` (
  `id` INT NOT NULL,
  `descricao` VARCHAR(45) NULL,
  `dataconclusao` DATE NULL,
  `usuarioid` INT NULL,
  `situacao` VARCHAR(45) NULL,
  `prioridade` VARCHAR(2) NULL,
  PRIMARY KEY (`id`));

ALTER TABLE `capapreta`.`objetivos` 
CHANGE COLUMN `id` `id` INT NOT NULL AUTO_INCREMENT ;
  
CREATE TABLE `capapreta`.`tarefas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `descricao` VARCHAR(45) NULL,
  `usuarioid` INT NULL,
  `situacao` VARCHAR(45) NULL,
  PRIMARY KEY (`id`));

ALTER TABLE `capapreta`.`tarefas` 
CHANGE COLUMN `id` `id` INT NOT NULL AUTO_INCREMENT ;

CREATE TABLE `capapreta`.`anotacoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `descricao` VARCHAR(400) NULL,
  `usuarioid` INT NULL,
  PRIMARY KEY (`id`));

ALTER TABLE `capapreta`.`anotacoes` 
CHANGE COLUMN `id` `id` INT NOT NULL AUTO_INCREMENT ;

CREATE TABLE `capapreta`.`usuarios` (
  `id` INT NOT NULL,
  `nome` VARCHAR(100) NULL,
  `email` VARCHAR(200) NULL,
  `senha` VARCHAR(45) NULL,
  `nivel` VARCHAR(45) NULL,
  `situacao` VARCHAR(45) NULL,
  PRIMARY KEY (`id`));
  
ALTER TABLE `capapreta`.`usuarios` 
CHANGE COLUMN `id` `id` INT NOT NULL AUTO_INCREMENT ;
  
insert into capapreta.usuarios (id, nome, email, senha, nivel, situacao) values (0, 'ADMINISTRADOR', 'admin@admin.com', '123', 'ADMINISTRADOR', 'ATIVO');