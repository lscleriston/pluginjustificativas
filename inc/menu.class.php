<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginJustificativasMenu extends CommonGLPI {

   public static function getMenuName() {
      return __('Justificativas');
   }

   public static function getMenuContent() {
      $menu = [
         'title' => self::getMenuName(),
         'page'  => Plugin::getWebDir('justificativas') . '/front/index.php',
         'icon'  => 'ti ti-file-import',
         'options' => [
            'import' => [
               'title' => __('Importar justificativas'),
               'page'  => Plugin::getWebDir('justificativas') . '/front/index.php',
               'links' => [
                  'search' => Plugin::getWebDir('justificativas') . '/front/index.php',
               ],
            ],
            'config' => [
               'title' => __('Configuração'),
               'page'  => Plugin::getWebDir('justificativas') . '/front/config.php',
               'links' => [
                  'search' => Plugin::getWebDir('justificativas') . '/front/config.php',
               ],
            ],
         ],
      ];

      return $menu;
   }
}