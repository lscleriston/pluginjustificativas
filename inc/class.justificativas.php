<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginJustificativas extends CommonDBTM {

   static function getTable($classname = null) {
      return 'glpi_plugin_justificativas_entries';
   }

   static function getTypeName($nb = 0) {
      return _n('Justificativa', 'Justificativas', $nb);
   }

   static function getMenuName($nb = 0) {
      return __('Importar justificativas');
   }

   static function getMenuContent() {
      $title = self::getMenuName(Session::getPluralNumber());
      $page  = '/plugins/justificativas/front/index.php';

      return [
         'title' => __('Justificativas de Chamados'),
         'page'  => $page,
         'icon'  => 'ti ti-file-import',
         'options' => [
            'import' => [
               'title' => $title,
               'page'  => $page,
               'links' => [
                  'search' => $page,
               ],
            ],
         ],
      ];
   }

}
