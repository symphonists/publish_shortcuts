<?php
 
  Class extension_publish_shortcuts extends Extension
  {    
    /*-------------------------------------------------------------------------
      Extension definition
    -------------------------------------------------------------------------*/
    
    public function about()
    {
      return array(
        'name' => 'Publish Shortcuts',
        'version' => '1.0',
        'release-date' => '2010-03-17',
        'author' => array(
          'name' => 'Max Wheeler',
          'email' => 'max@makenosound.com',
        )
      );
    }
    
    /*-------------------------------------------------------------------------
      Un/installation
    -------------------------------------------------------------------------*/
    
    public function install() {
      $this->_Parent->Database->query("
        CREATE TABLE IF NOT EXISTS `tbl_publish_shortcuts` (
          `id` int(11) NOT NULL auto_increment,
          `section_id` int(11) NOT NULL,
          `order` int(11) NOT NULL,
          `label` varchar(255) NOT NULL,
          `link` varchar(255) NOT NULL,
          PRIMARY KEY (`id`)
        )
      ");
    }
    
    public function uninstall() {
      $this->_Parent->Database->query("DROP TABLE `tbl_publish_shortcuts`");
    }
     
    /*-------------------------------------------------------------------------
      Delegates
    -------------------------------------------------------------------------*/
    
    public function getSubscribedDelegates()
    {
      return array(  
        array(
          'page'      => '/system/preferences/',
          'delegate'  => 'AddCustomPreferenceFieldsets',
          'callback'  => 'addCustomPreferenceFieldsets'
        ),
        array(
          'page'      => '/backend/',
          'delegate'  => 'InitaliseAdminPageHead',
          'callback'  => 'initaliseAdminPageHead'
          )
      );
    }
    
    
    public function addCustomPreferenceFieldsets(&$context)
    {
    }
    
    public function initaliseAdminPageHead($context) {
      $page = $context['parent']->Page;

      // Include shortcuts
      if ($page instanceof ContentPublish and $page->_context['page'] == 'index') {
        $page->addStylesheetToHead(URL . '/extensions/publish_shortcuts/assets/shortcuts.css', 'screen', 902010);
        $page->addScriptToHead(URL . '/symphony/extension/publish_shortcuts/shortcuts/?section=' . $page->_context['section_handle'], 90211);
        $page->addScriptToHead(URL . '/extensions/publish_shortcuts/assets/shortcuts.js', 90212);
      }
    }
    
    /*-------------------------------------------------------------------------
      Helpers
    -------------------------------------------------------------------------*/
    public function getShortcuts($section_id) {
      $shortcuts = $this->_Parent->Database->fetch("SELECT * FROM tbl_publish_shortcuts WHERE section_id = $section_id");
      return $shortcuts;
    }
  }
