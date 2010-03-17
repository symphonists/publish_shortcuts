<?php

  require_once(TOOLKIT . '/class.administrationpage.php');
  require_once(TOOLKIT . '/class.sectionmanager.php');
  
  class ContentExtensionPublish_shortcutsShortcuts extends AdministrationPage {
    protected $_driver = null;
    
    public function __construct(&$parent){
      parent::__construct($parent);
      
      $this->_driver = $this->_Parent->ExtensionManager->create('publish_shortcuts');
    }
    
    public function __viewIndex() {
      header('content-type: text/javascript');
      
      $sm = new SectionManager($this->_Parent);
      $section_handle = $_GET["section"];
      $section_id = $sm->fetchIDFromHandle($section_handle);
      $shortcuts = $this->_driver->getShortcuts($section_id);
      echo "var shortcuts_section_id = $section_id;\n";
      echo "var shortcuts_section_handle = \"$section_handle\";\n";
      echo "var shortcuts_links = [";        
      foreach ($shortcuts as $key => $shortcut)
      {
        $label = $shortcut['label'];
        $link = $shortcut['link'];
        echo "
        {
          label:  '$label',
          link:   '$link'
        }";
        if ($key != count($shortcuts) - 1) echo ",\n";
      };
      echo "];";
      
      exit();
    }
  }