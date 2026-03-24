<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginJustificativasProfile extends CommonDBTM {

   static $rightname = 'profile';

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() === 'Profile' && $item->getField('interface') !== 'helpdesk') {
         return __('Justificativas de Chamados');
      }

      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() === 'Profile') {
         $profile = new self();
         $profile->showForm($item->getID());
      }

      return true;
   }

   function showForm($ID, $options = []) {
      $profile = new Profile();
      $profile->getFromDB($ID);

      if (!$profile->canView()) {
         return false;
      }

      $canedit = Session::haveRight('profile', UPDATE);

      echo "<div class='spaced'>";

      if ($canedit) {
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $rights = self::getAllRights();
      $profile->displayRightsChoiceMatrix($rights, [
         'canedit' => $canedit,
         'default_class' => 'tab_bg_2',
         'title' => __('Justificativas de Chamados')
      ]);

      if ($canedit) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
         echo "</div>";
         Html::closeForm();
      }

      echo "</div>";

      return true;
   }

   static function getAllRights($all = false) {
      return [
         [
            'itemtype' => self::class,
            'label' => __('Justificativas de Chamados'),
            'field' => 'justificativas',
            'rights' => [
               READ => __('Read'),
               UPDATE => __('Write')
            ]
         ]
      ];
   }

   static function initProfile() {
      foreach (self::getAllRights() as $data) {
         if (countElementsInTable('glpi_profilerights', ['name' => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }
   }
}