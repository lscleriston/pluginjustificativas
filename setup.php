<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

require_once __DIR__ . '/inc/class.justificativas.php';
require_once __DIR__ . '/inc/menu.class.php';
require_once __DIR__ . '/inc/profile.class.php';

/**
 * Plugin version information
 *
 * @return array
 */
function plugin_version_justificativas() {
   return [
      'name'           => 'Justificativas de Chamados',
      'version'        => '1.0.0',
      'author'         => 'Sua Empresa / Seu Nome',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/seuusuario/glpi-plugin-justificativas',
      'min_glpi'       => '10.0.0',
      'max_glpi'       => '10.9.9',
      'has_admin'      => true,
      'has_config'     => true,
      'has_units'      => false,
      'requires'       => [],
   ];
}

/**
 * Init hook function (compatibility for old/new callbacks)
 */
function plugin_init_justificativas() {
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['justificativas'] = true;
   $PLUGIN_HOOKS['config_page']['justificativas']   = 'front/config.php';

   $PLUGIN_HOOKS['menu_toadd']['justificativas'] = [
      'tools' => 'PluginJustificativasMenu',
   ];

   Plugin::registerClass('PluginJustificativas', ['addtabon' => 'Plugin']);
   Plugin::registerClass('PluginJustificativasProfile', ['addtabon' => ['Profile']]);

   PluginJustificativasProfile::initProfile();
}

function plugin_justificativas_init() {
   return plugin_init_justificativas();
}

/**
 * Check if plugin is installed (compatibility wrapper)
 *
 * @return bool
 */
function plugin_is_installed_justificativas() {
   return true;
}
function plugin_justificativas_is_installed() {
   return plugin_is_installed_justificativas();
}

/**
 * Installation routine creates required database table.
*/
function plugin_install_justificativas() {
   global $DB;

   if (!$DB->tableExists('glpi_plugin_justificativas_operations')) {
      $query = "CREATE TABLE `glpi_plugin_justificativas_operations` ("
         . "`id` INT(11) NOT NULL AUTO_INCREMENT,"
         . "`name` VARCHAR(255) NOT NULL COMMENT 'Nome da operação',"
         . "`description` TEXT NULL COMMENT 'Descrição',"
         . "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
         . "`updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,"
         . "PRIMARY KEY (`id`),"
         . "UNIQUE KEY (`name`)"
         . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
      $DB->query($query);
   }

   $tables = [
      'glpi_plugin_justificativas_tickets' => 'ticket_id',
      'glpi_plugin_justificativas_ligacoes' => 'ligacao_id',
      'glpi_plugin_justificativas_zabbix' => 'evento_id'
   ];

   foreach ($tables as $tableName => $foreignKey) {
      if (!$DB->tableExists($tableName)) {
         if ($tableName === 'glpi_plugin_justificativas_tickets' && $DB->tableExists('glpi_plugin_justificativas_entries')) {
            $DB->query("RENAME TABLE `glpi_plugin_justificativas_entries` TO `$tableName`");
         } else {
            $query = "CREATE TABLE `$tableName` ("
               . "`id` INT(11) NOT NULL AUTO_INCREMENT,"
               . "`$foreignKey` INT(11) NOT NULL COMMENT 'Referência de $foreignKey',"
               . "`closing_date` DATE NOT NULL COMMENT 'Data de fechamento',"
               . "`justification` TEXT NOT NULL COMMENT 'Justificativa',"
               . "`operation_id` INT(11) NULL DEFAULT NULL COMMENT 'Operação associada',"
               . "`operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada',"
               . "`user_id` INT(11) NULL DEFAULT NULL COMMENT 'Usuário que importou',"
               . "`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,"
               . "`updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,"
               . "PRIMARY KEY (`id`),"
               . "KEY (`$foreignKey`),"
               . "KEY (`operation_id`)"
               . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $DB->query($query);
         }
      } elseif (!$DB->fieldExists($tableName, 'operation_name')) {
         $DB->query("ALTER TABLE `$tableName` ADD COLUMN `operation_name` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Nome da operação associada' AFTER `operation_id`");
      }
   }

   PluginJustificativasProfile::initProfile();

   return true;
}
function plugin_justificativas_install() {
   return plugin_install_justificativas();
}

/**
 * Uninstall routine drops plugin table.
 */
function plugin_uninstall_justificativas() {
   global $DB;

   $tables = [
      'glpi_plugin_justificativas_tickets',
      'glpi_plugin_justificativas_ligacoes',
      'glpi_plugin_justificativas_zabbix'
   ];

   foreach ($tables as $tableName) {
      if ($DB->tableExists($tableName)) {
         $DB->query("DROP TABLE `$tableName`");
      }
   }

   if ($DB->tableExists('glpi_plugin_justificativas_operations')) {
      $DB->query("DROP TABLE `glpi_plugin_justificativas_operations`");
   }

   return true;
}
function plugin_justificativas_uninstall() {
   return plugin_uninstall_justificativas();
}
