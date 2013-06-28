<?php

require_once(TOOLKIT . '/class.sectionmanager.php');

Class extension_publish_shortcuts extends Extension {

	/*-------------------------------------------------------------------------
		Extension definition
	-------------------------------------------------------------------------*/

	public function about() {
		return array(
			'name' => 'Publish Shortcuts',
			'version' => '1.1',
			'release-date' => '2011-03-18',
			'author' => array(
				'name' => 'Max Wheeler',
				'email' => 'max@makenosound.com',
			),
			'description' => 'Lets you define shortcut buttons for the section/publish index pages.',
		);
	}

	/*-------------------------------------------------------------------------
	Un/installation
	-------------------------------------------------------------------------*/

	public function install() {
		Symphony::Database()->query("
			CREATE TABLE IF NOT EXISTS `tbl_publish_shortcuts` (
			`id` int(11) NOT NULL auto_increment,
			`label` varchar(255) NOT NULL,
			`link` varchar(255) NOT NULL,
			`section_id` int(11) NOT NULL,
			`sort_order` int(11) NOT NULL,
			PRIMARY KEY (`id`)
			)
		");
	}
	
	public function update($previousVersion) {
		// Pre v1.1 (v1.0.1) had a column named `order` which is a reserved keyword in MySQL
		// which could not be used in SQL queries
		if(version_compare($previousVersion, '1.1', '<')) {
			Administration::instance()->Database->query("ALTER TABLE `tbl_publish_shortcuts` CHANGE `order` `sort_order` int(11) NOT NULL");
		}
	}

	public function uninstall() {
		Symphony::Database()->query("DROP TABLE `tbl_publish_shortcuts`");
	}

	/*-------------------------------------------------------------------------
		Delegates and callbacks
	-------------------------------------------------------------------------*/

	public function getSubscribedDelegates() {
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

	/**
	 * Add duplicator to System Preferences page, lists existing
	 * shortcuts as well as templates for each section
	 */
	public function addCustomPreferenceFieldsets(&$context) {
		
		$sm = new SectionManager(Administration::instance());
		$sections = $sm->fetch();
		
		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', __('Publish Shortcuts')));

		$info = new XMLElement('p', __('Custom links for publish indexes'));
		$info->setAttribute('class', 'help');
		$fieldset->appendChild($info);
		
		$label = new XMLElement('p', __('Links') . '<i>' . __('Use <code>{$root}</code> for the URL and <code>{$filter}</code> for the current filter string.') . '</i>');
		$label->setAttribute('class', 'label');
		$fieldset->appendChild($label);
		
		$div = new XMLElement('div');
		$div->setAttribute('class', 'frame publishshortcuts');
		
		$ol = new XMLElement('ol');
		$shortcuts = $this->__getShortcuts();
		if(!is_array($shortcuts)) $shortcuts = array($shortcuts);
		
		if(is_array($sections)) {
			foreach($sections as $section){
				
				foreach($shortcuts as $shortcut) {
					if($shortcut['section_id'] != $section->get('id')) continue;
					$wrapper = $this->__buildDuplicatorItem($section, $shortcut);
					$ol->appendChild($wrapper);
				}
				
				$wrapper = $this->__buildDuplicatorItem($section, NULL);
				$ol->appendChild($wrapper);
				
			}
		}

		$div->appendChild($ol);
		$fieldset->appendChild($div);
		$context['wrapper']->appendChild($fieldset);
	}
	
	/**
	 * Returns a list item for the duplicator. When no shortcut is passed
	 * a section template is returned, rather than an existing duplicator item
	 */
	private function __buildDuplicatorItem($section, $shortcut=NULL) {
		
		// value of -1 signifies a duplicator "template"
		$index = ($shortcut == NULL) ? '-1' : $shortcut['id'];
		
		$wrapper = new XMLElement('li');
		$wrapper->setAttribute('class', ($shortcut == NULL) ? 'template' : '');
		
		// Header
		$header = new XMLElement('header');
		$headline = new XMLElement('h4', $section->get('name'));
		$header->appendChild($headline);
		$wrapper->appendChild($header);
		
		// Content
		$content = new XMLElement('div');
		$content->setAttribute('class', 'two columns');

		$label = Widget::Label(__('Label'));
		$label->setAttribute('class', 'column');
		$input = Widget::Input('settings[publish_shortcuts][' . $index . '][label]', General::sanitize($shortcut['label']));
		$label->appendChild($input);
		$content->appendChild($label);

		$label = Widget::Label(__('Link'));
		$label->setAttribute('class', 'column');
		$input = Widget::Input('settings[publish_shortcuts][' . $index . '][link]', General::sanitize($shortcut['link']));
		$label->appendChild($input);
		$content->appendChild($label);
		
		$wrapper->appendChild(new XMLElement('input', NULL, array(
			'type' => 'hidden',
			'name' => 'settings[publish_shortcuts][' . $index . '][section_id]',
			'value' => $section->get('id')
		)));
		
		$wrapper->appendChild($content);
		
		return $wrapper;
	}

	/**
	 * Add shortcuts to page DOM
	 */
	public function initaliseAdminPageHead($context) {
		$page = Administration::instance()->Page;
		
		// Publish area
		if($page instanceof ContentPublish and $page->_context['page'] == 'index') {
			$sm = new SectionManager(Administration::instance());
			$section_id = $sm->fetchIDFromHandle($page->_context['section_handle']);
			$shortcuts = $this->__getShortcuts($section_id);
			
			$page->addElementToHead(new XMLElement(
				'script',
				"Symphony.Context.add('publish_shortcuts', " . json_encode($shortcuts) . ")",
				array('type' => 'text/javascript')
			), 902011);
			
			$page->addScriptToHead(URL . '/extensions/publish_shortcuts/assets/publish_shortcuts.publish.js', 90211);
		}

		// Preferences
		if($page instanceof ContentSystemPreferences) {
			$page->addScriptToHead(URL . '/extensions/publish_shortcuts/assets/publish_shortcuts.preferences.js', 90211);
		}
	}
	
	/**
	 * Save shortcuts when System Preferences page is submitted
	 */
	public function save(&$context) {
		
		Symphony::Database()->query("DELETE FROM tbl_publish_shortcuts");
		
		$shortcuts = $context['settings']['publish_shortcuts'];
		unset($context['settings']['publish_shortcuts']);
		
		if (!isset($shortcuts)) return;
		
		foreach($shortcuts as $i => $shortcut) {
			if($shortcut['label'] == '' || $shortcut['link'] == '') continue;
			$shortcut['sort_order'] = $i;
			Symphony::Database()->insert($shortcut, "tbl_publish_shortcuts");
		}

	}
	
	/*-------------------------------------------------------------------------
		Helpers
	-------------------------------------------------------------------------*/

	/**
	 * Helper: returns all shortcuts, optionally filtered by a section ID
	 */
	private function __getShortcuts($section_id=NULL) {
		return Symphony::Database()->fetch(sprintf(
			"SELECT * FROM tbl_publish_shortcuts %s ORDER BY `sort_order` ASC",
			(is_null($section_id)) ? '' : "WHERE section_id = $section_id"
		));
	}
	
}
