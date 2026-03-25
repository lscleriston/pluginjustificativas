CREATE TABLE IF NOT EXISTS `glpi_plugin_justificativas_operations` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `name` VARCHAR(255) NOT NULL COMMENT 'Nome da operação',
   `description` TEXT NULL COMMENT 'Descrição',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   UNIQUE KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_justificativas_tickets` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `ticket_id` INT(11) NOT NULL COMMENT 'Número do chamado',
   `closing_date` DATE NOT NULL COMMENT 'Data de fechamento',
   `justification` TEXT NOT NULL COMMENT 'Justificativa',
   `operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',
   `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',
   `user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY (`ticket_id`),
   KEY (`operation_id`),
   UNIQUE KEY `uniq_tickets_operation` (`ticket_id`, `operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_justificativas_ligacoes` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `ligacao_id` INT(11) NOT NULL COMMENT 'ID da ligacao',
   `closing_date` DATE NOT NULL COMMENT 'Data de fechamento',
   `justification` TEXT NOT NULL COMMENT 'Justificativa',
   `operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',
   `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',
   `user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY (`ligacao_id`),
   KEY (`operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_justificativas_zabbix` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `evento_id` INT(11) NOT NULL COMMENT 'ID do evento Zabbix',
   `closing_date` DATE NOT NULL COMMENT 'Data de fechamento',
   `justification` TEXT NOT NULL COMMENT 'Justificativa',
   `operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',
   `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',
   `user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY (`evento_id`),
   KEY (`operation_id`),
   UNIQUE KEY `uniq_zabbix_operation` (`evento_id`, `operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_justificativas_telefonia_atendida` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `telefonia_atendida_id` VARCHAR(255) NOT NULL COMMENT 'ID da ligação atendida',
   `closing_date` DATE NOT NULL COMMENT 'Data de fechamento',
   `justification` TEXT NOT NULL COMMENT 'Justificativa',
   `operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',
   `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',
   `user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY (`telefonia_atendida_id`),
   KEY (`operation_id`),
   UNIQUE KEY `uniq_telefonia_atendida_operation` (`telefonia_atendida_id`, `operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `glpi_plugin_justificativas_telefonia_perdida` (
   `id` INT(11) NOT NULL AUTO_INCREMENT,
   `telefonia_perdida_id` VARCHAR(255) NOT NULL COMMENT 'ID da ligação perdida',
   `closing_date` DATE NOT NULL COMMENT 'Data de fechamento',
   `justification` TEXT NOT NULL COMMENT 'Justificativa',
   `operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',
   `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',
   `user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',
   `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
   PRIMARY KEY (`id`),
   KEY (`telefonia_perdida_id`),
   KEY (`operation_id`),
   UNIQUE KEY `uniq_telefonia_perdida_operation` (`telefonia_perdida_id`, `operation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
