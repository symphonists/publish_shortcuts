<?php
 
  require_once(TOOLKIT . '/class.sectionmanager.php');
  
  Class extension_publish_shortcuts extends Extension
  {
    private $_sectionManager;
    
    public function __construct($args)
    {
      $this->_Parent =& $args['parent'];
      $this->_sectionManager = new SectionManager($this->_Parent);
    }
    
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
          `label` varchar(255) NOT NULL,
          `link` varchar(255) NOT NULL,
          `section_id` int(11) NOT NULL,
          `order` int(11) NOT NULL,
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
          ),
        array(
          'page'      => '/system/preferences/',
          'delegate'  => 'Save',
          'callback'  => 'save'
        )
      );
    }
    
    # Build the Preferences page
    public function addCustomPreferenceFieldsets(&$context)
    {
      $sections = $this->getSections();
      // Build section options array
      foreach ($sections as $section) {
        $options[] = array($section['id'], false, $section['name']);
      }
      
      $fieldset = new XMLElement('fieldset');
      $fieldset->setAttribute('class', 'settings');
      $fieldset->appendChild(new XMLElement('legend', __('Publish Shortcuts')));
       
      $p = new XMLElement('p', __('Shortcut links for Section index pages'));
      $p->setAttribute('class', 'help');
      $fieldset->appendChild($p);
      
      $group = new XMLElement('div');
      $group->setAttribute('class', 'subsection');
      $group->appendChild(new XMLElement('h3', __('Shortcuts Links')));
       
      $ol = new XMLElement('ol');
      $ol->setAttribute('id', 'publish_shortcuts');
      
      # Create the duplicator template
      $li = new XMLElement('li');
      $li->setAttribute('class', 'template');
      $li->appendChild(new XMLElement('h4', __("Shortcut")));
      
      $divcontent = new XMLElement('div');
      $divcontent->setAttribute('class', 'content');
      
      $divgroup = new XMLElement('div');
      $divgroup->setAttribute('class', 'group');
      
      $label = Widget::Label(__('Label'));
      $label->appendChild(Widget::Input("settings[publish_shortcuts][][label]", __("Label")));
      $divgroup->appendChild($label);
      
      $label = Widget::Label(__('Link'));
      $label->appendChild(Widget::Input("settings[publish_shortcuts][][link]", __("Link")));
      $divgroup->appendChild($label);
      $divcontent->appendChild($divgroup);
      
      $divgroup = new XMLElement('div');
      $divgroup->setAttribute('class', 'group');
      
      $label = Widget::Label(__('Section'));
      $label->appendChild(Widget::Select("settings[publish_shortcuts][][section_id]", $options));
      $divgroup->appendChild($label);
      $divcontent->appendChild($divgroup);
      
      $li->appendChild($divcontent);
      $ol->appendChild($li);
      
      # Build up existing shortcuts
      if($shortcuts = $this->getShortcuts()) {
        if(is_array($shortcuts)) {
          foreach($shortcuts as $shortcut) {
            $li = new XMLElement('li');
            $li->appendChild(new XMLElement('h4', __("Shortcut")));

            $divcontent = new XMLElement('div');
            $divcontent->setAttribute('class', 'content');

            $divgroup = new XMLElement('div');
            $divgroup->setAttribute('class', 'group');

            $label = Widget::Label(__('Label'));
            $label->appendChild(Widget::Input("settings[publish_shortcuts][][label]", General::sanitize($shortcut['label'])));
            $divgroup->appendChild($label);

            $label = Widget::Label(__('Link'));
            $label->appendChild(Widget::Input("settings[publish_shortcuts][][link]", General::sanitize($shortcut['link'])));
            $divgroup->appendChild($label);
            $divcontent->appendChild($divgroup);

            $divgroup = new XMLElement('div');
            $divgroup->setAttribute('class', 'group');
            
            # Set selected
            foreach ($options as &$option)
            {
              if ($option[0] == $shortcut['section_id']) $option[1] = true;
            }
            
            $label = Widget::Label(__('Section'));
            $label->appendChild(Widget::Select("settings[publish_shortcuts][][section_id]", $options));
            $divgroup->appendChild($label);
            $divcontent->appendChild($divgroup);

            $li->appendChild($divcontent);
            $ol->appendChild($li);
          }
        }
      }
      $group->appendChild($ol);
      $fieldset->appendChild($group);
      $context['wrapper']->appendChild($fieldset);
    }
    
    # Add shortcut resources to publish indexs pages
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
    public function getShortcuts($section_id = null)
    {
      $query = "SELECT * FROM tbl_publish_shortcuts";
      if (isset($section_id)) $query .= " WHERE section_id = $section_id";
      $shortcuts = $this->_Parent->Database->fetch($query);
      return $shortcuts;
    }
    
    private function getSections()
    {
      $raw = $this->_sectionManager->fetch();
      foreach ($raw as $section)
      {
        $sections[] = array(
          'id' => $section->_data['id'],
          'name' => $section->_data['name']
        );
      }
      return $sections;
    }
    
    # Save the shortcuts
    public function save(&$context) {
      # Setup
      $shortcuts = array();
      $shortcut = array();
      $count = 0;
      
      # Munge data into cleaner structure
      if (isset($context['settings']['publish_shortcuts'])) {
        foreach($context['settings']['publish_shortcuts'] as $item)
        {
          if (isset($item['label'])) {
            $shortcut['label'] = $item['label'];
          } else if(isset($item['link'])) {
            $shortcut['link'] = $item['link'];
          } else if(isset($item['section_id'])) {
            $shortcut['section_id'] = $item['section_id'];
            $shortcut['order'] = $count;
            $shortcuts[] = $shortcut;
            $count++;
            $shortcut = array();
          }
        }
      }
      
      # Clean up
      $this->_Parent->Database->query("DELETE FROM tbl_publish_shortcuts");
      if (isset($context['settings']['publish_shortcuts'])) $this->_Parent->Database->insert($shortcuts, "tbl_publish_shortcuts");
      unset($context['settings']['publish_shortcuts']);
    }
  }
