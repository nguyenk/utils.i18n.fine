<?php

namespace Mouf\Utils\I18n\Fine\Controllers; 

use Mouf\Html\Utils\WebLibraryManager\WebLibrary;

use Mouf\Html\Template\TemplateInterface;

use Mouf\Html\HtmlElement\HtmlBlock;

use Mouf\MoufUtils;

use Mouf\Reflection\MoufReflectionProxy;

use Mouf\MoufSearchable;

use Mouf\Mvc\Splash\Controllers\Controller;

use Exception;

/*
 * Copyright (c) 2012 David Negrier
 * 
 * See the file LICENSE.txt for copying permission.
 */

//require_once dirname(__FILE__).'/../FineMessageLanguage.php';
//require_once dirname(__FILE__).'/../views/editLabel.php';

/**
 * The controller to edit labels that are to be translated.
 *
 * @Component
 */
class EditLabelController extends Controller implements MoufSearchable {

	/**
	 * The default template to use for this controller (will be the mouf template)
	 *
	 * @Property
	 * @Compulsory 
	 * @var TemplateInterface
	 */
	public $template;

	/**
	 *
	 * @var HtmlBlock
	 */
	public $content;
	
	public $isMessageEditionMode = false;
	public $languages;
	public $msgs;
	public $selfedit;
	
	public $msgInstanceName;
	
	public $results;
	
	public $letters = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "X", "Y", "Z",
							"AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AX", "AY", "AZ",
							"BA", "BB", "BC", "BD", "BE", "BF", "BG", "BH", "BI", "BJ", "BK", "BL", "BM", "BN", "BO", "BP", "BQ", "BR", "BS", "BT", "BU", "BV", "BX", "BY", "BZ");
	
	/**
	 * Admin page used to enable or disable label edition.
	 *
	 * @Action
	 * @Logged
	 */
	public function editionMode() {
		if (isset($_SESSION["FINE_MESSAGE_EDITION_MODE"])) {
			$this->isMessageEditionMode = true;
		}
		$this->content->addFile('../utils.i18n.fine/src/views/enableDisableEdition.php', $this);
		$this->template->toHtml();
	}

	/**
	 * Action used to set the mode of label edition.
	 *
	 * @Action
	 * //@Admin
	 */
	public function setMode($mode) {
		$editMode = ($mode=="on")?true:false;
		//SessionUtils::setMessageEditionMode($editMode);
		if ($editMode) {
			$_SESSION["FINE_MESSAGE_EDITION_MODE"] = true;
			$this->isMessageEditionMode = true;
		} else {
			unset($_SESSION["FINE_MESSAGE_EDITION_MODE"]);
		}

		$this->content->addFile('../utils.i18n.fine/src/views/enableDisableEdition.php', $this);
		$this->template->toHtml();
	}

	/**
	 * @Action
	 */
	public function editLabel($key, $backto=null, $language = null, $msginstancename="defaultTranslationService" ,$selfedit = "false", $saved = false) {
		/*if (!SessionUtils::isMessageEditionMode() && !SessionUtils::isAdmin()) {
			throw new ApplicationException('editlabel.editlabel.messageeditionmoderequired.title','editlabel.editlabel.messageeditionmoderequired.text');
		}*/
		$this->msgInstanceName = $msginstancename;

		$messagesArray = $this->getAllTranslationsForMessageFromService(($selfedit == "true"), $msginstancename, $key);
		
		if (!$language) {
			$language = "default";
		}
		
		$msg = null;
		if (isset($messagesArray[$language])) {
			$msg = $messagesArray[$language];
		}

		unset($messagesArray[$language]);

		$this->saved = $saved;
		$this->backto = $backto;
		$this->key = $key;
		$this->language = $language;
		$this->msg = $msg;
		$this->messagesArray = $messagesArray;
		$this->msginstancename = $msginstancename;
		$this->selfedit = $selfedit;
		$this->content->addFile('../utils.i18n.fine/src/views/editLabel.php', $this);
		//$this->template->addContentFunction("editLabel", $key, $msg, $language, $messagesArray, false, $backto, $msginstancename, $selfedit, $saved);
		$this->template->toHtml();
	}

	/**
	 * @Action
	 * @Logged
	 */
	public function saveLabel($key, $label, $language = null, $delete = null, $save = null, $back = null, $backto = null, $msginstancename="defaultTranslationService" ,$selfedit = "false") {
		$this->msgInstanceName = $msginstancename;
		if ($back) {
			header("Location: ".$backto);	
		} else {
			$this->setTranslationForMessageFromService(($selfedit == "true"), $msginstancename, $key, $label, $language, $delete);

			$this->editLabel($key, $backto, $language, $msginstancename, $selfedit, true);
		}
	}

	/**
	 * @Action
	 * @Logged
	 * @param string $name The name of the Mouf instance representing the translator
	 */
	public function missinglabels($name = "defaultTranslationService", $selfedit = "false") {
		$this->msgInstanceName = $name;
		$this->selfedit =$selfedit;
		
		$array = $this->getAllMessagesFromService(($selfedit == "true"), $name);
		$this->languages = $array["languages"];
		$this->msgs = $array["msgs"];
		
		$webLibriary = new WebLibrary(array(), array('../utils.i18n.fine/src/views/css/style.css'));
		$this->template->getWebLibraryManager()->addLibrary($webLibriary);
		/*
		$this->template->addJsFile(ROOT_URL."plugins/javascript/jquery/jquery-fixedheadertable/1.3/js/jquery-fixedheadertable.min.js");
		*/
		//$this->template->addContentFile(dirname(__FILE__)."/../views/missingLabel.php", $this);
		$this->content->addFile('../utils.i18n.fine/src/views/missingLabel.php', $this);
		$this->template->toHtml();
	}

	/**
	 * Displays the page that will allow the user to add a new language. 
	 * 
	 * @Action
	 * @Logged
	 */
	public function supportedLanguages($name = "defaultTranslationService", $selfedit="false") {
		$this->msgInstanceName = $name;
		$this->selfedit =$selfedit;
		
		$array = $this->getAllMessagesFromService(($selfedit == "true"), $name);
		$this->languages = $array["languages"];
		
		
		$this->content->addFile('../utils.i18n.fine/src/views/supportedLanguages.php', $this);
		$this->template->toHtml();
	}

	/**
	 * Displays the page that will allow the user to add a new language.
	 *
	 * @Action
	 * @Logged
	 */
	public function createMessageFile($name = "defaultTranslationService", $selfedit="false", $language) {
	
		$this->addTranslationLanguageFromService(($selfedit == "true"), $name, $language);
		
		header('location:'.$_SERVER['HTTP_REFERER']);
	}
	
	/**
	 * Adds a language to the list of supported languages
	 *
	 * @Action
	 * @Logged
	 * @param string $language
	 */
	public function addSupportedLanguage($language, $name = "defaultTranslationService", $selfedit="false") {
		$this->addTranslationLanguageFromService(($selfedit == "true"), $name, $language);
		
		// Once more to reaload languages list
		$this->supportedLanguages($name, $selfedit);
	}

	
	/**
	 * Search All label with the value
	 *
	 * @Action
	 * @Logged
	 * @param string $name
	 * @param string $search
	 */
	public function searchLabel($msginstancename = "defaultTranslationService", $selfedit = "false", $search, $language_search = null) {
		$this->msgInstanceName = $msginstancename;
		$this->selfedit = $selfedit;
	
		$array = $this->getAllMessagesFromService(($selfedit == "true"), $msginstancename, $language_search);
		$this->languages = $array["languages"];
		$this->search = $search;
		$this->language_search = $language_search;
		if($search) {
			$this->msgs = $array["msgs"];
	
			
			$regex = $this->stripRegexMetaChars($this->stripAccents($search));
			$this->results = $this->regex_search_array($array["msgs"], $regex);
			
			$this->error = false;
		}
		else
			$this->error = true;

		$webLibriary = new WebLibrary(array(), array('../utils.i18n.fine/src/views/css/style.css'));
		$this->template->getWebLibraryManager()->addLibrary($webLibriary);

		$this->content->addFile('../utils.i18n.fine/src/views/searchLabel.php', $this);
		$this->template->toHtml();
	}
	
	/**
	 * Outputs HTML that will be displayed in the search result screen.
	 * If there are no results, this should not return anything.
	 * 
     * @Action
     * @Logged
	 * @param string $query The full-text search query performed.
	 * @param string $selfedit Whether we are in self-edit mode or not.
	 * @return string The HTML to be displayed.
	 */
	public function search($query, $selfedit = "false") {
		$instances = MoufReflectionProxy::getInstances("LanguageTranslationInterface", $selfedit == "true");

		$this->selfedit = $selfedit;
		
		if($query) {
			$regex = $this->stripRegexMetaChars($this->stripAccents($query));
			foreach ($instances as $instance) {
				$array = $this->getAllMessagesFromService(($selfedit == "true"), $instance, null);

				$this->results[$instance] = $this->regex_search_array($array["msgs"], $regex);
			}
			$this->error = false;
		}
		else
			$this->error = true;
		
		$this->loadFile(dirname(__FILE__)."/../views/searchGlobal.php");
	}
        
	/**
	 * Returns the name of the search module.
	 * This name in displayed when the search is pending.
	 * 
	 * @return string
	 */
	public function getSearchModuleName() {
		return "Fine 2.1 for translations";
	}
	
	/**
	 * Returns the list of all messages in all languages by making a CURL call. It is possible to set a language to retrieve only the associated message.
	 * 
	 * @param bool $selfEdit
	 * @param string $msgInstanceName
	 * @param string $language
	 * @return array
	 * @throws Exception
	 */
	protected static function getAllMessagesFromService($selfEdit, $msgInstanceName = "defaultTranslationService", $language = null) {

		$url = MoufReflectionProxy::getLocalUrlToProject()."../utils.i18n.fine/src/direct/get_all_messages.php?msginstancename=".urlencode($msgInstanceName)."&selfedit=".(($selfEdit)?"true":"false")."&language=".$language;
		
		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	/**
	 * Returns all the translation of one key by making a CURL call.
	 * 
	 * @param bool $selfEdit
	 * @param string $msgInstanceName
	 * @param string $key
	 * @return array
	 * @throws Exception
	 */
	protected static function getAllTranslationsForMessageFromService($selfEdit, $msgInstanceName, $key) {

		$url = MoufReflectionProxy::getLocalUrlToProject()."../utils.i18n.fine/src/direct/get_message_translations.php?msginstancename=".urlencode($msgInstanceName)."&selfedit=".(($selfEdit)?"true":"false")."&key=".urlencode($key);
		
		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	

	/**
	 * Saves many translations for one key and one language using CURL.
	 * 
	 * @param bool $selfEdit
	 * @param string $msgInstanceName
	 * @param string $language
	 * @param array $translations
	 * @return boolean
	 * @throws Exception
	 */
	protected static function setTranslationsForMessageFromService($selfEdit, $msgInstanceName, $language, $translations) {

		$url = MoufReflectionProxy::getLocalUrlToProject()."../utils.i18n.fine/src/direct/set_messages_translation.php";
		
		$post = array("msginstancename" => $msgInstanceName,
						"selfedit" => (($selfEdit)?"true":"false"),
						"language" => $language,
						"translations" => serialize($translations));
		$response = self::performRequest($url, $post);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	/**
	 * Saves the translation for one key and one language using CURL.
	 * 
	 * @param bool $selfEdit
	 * @param string $msgInstanceName
	 * @param string $key
	 * @param string $label
	 * @param string $language
	 * @param string $delete
	 * @return boolean
	 * @throws Exception
	 */
	protected static function setTranslationForMessageFromService($selfEdit, $msgInstanceName, $key, $label, $language, $delete) {

		$url = MoufReflectionProxy::getLocalUrlToProject()."../utils.i18n.fine/src/direct/set_message_translation.php?msginstancename=".urlencode($msgInstanceName)."&selfedit=".(($selfEdit)?"true":"false")."&key=".urlencode($key)."&language=".urlencode($language)."&delete=".urlencode($delete);
		 
		$response = self::performRequest($url, array("label" => $label));

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	/**
	 * Adds a new translation language using CURL.
	 * 
	 * @param bool $selfEdit
	 * @param string $msgInstanceName
	 * @param string $language
	 * @return boolean
	 * @throws Exception
	 */
	protected static function addTranslationLanguageFromService($selfEdit, $msgInstanceName, $language) {

		$url = MoufReflectionProxy::getLocalUrlToProject()."../utils.i18n.fine/src/direct/create_language_file.php?msginstancename=".urlencode($msgInstanceName)."&selfedit=".(($selfEdit)?"true":"false")."&language=".urlencode($language);
		 
		$response = self::performRequest($url);

		$obj = unserialize($response);
		
		if ($obj === false) {
			throw new Exception("Unable to unserialize message:\n".$response."\n<br/>URL in error: <a href='".plainstring_to_htmlprotected($url)."'>".plainstring_to_htmlprotected($url)."</a>");
		}
		
		return $obj;
	}
	
	private static function performRequest($url, $post = array()) {
		// preparation de l'envoi
		$ch = curl_init();
				
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if($post) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		} else
			curl_setopt($ch, CURLOPT_POST, false);
		$response = curl_exec($ch );
		
		if( curl_error($ch) ) { 
			throw new Exception("An error occured: ".curl_error($ch));
		}
		curl_close( $ch );
		
		return $response;
	}

	/**
	 * Search the regex in all the array. Return an array with all result
	 * 
	 * Enter description here ...
	 * @param array $array
	 * @param string $regex
	 * @return array
	 */
	private function regex_search_array($array, $regex) {
		$return = array();
		foreach ($array as $key => $item) {
	 		if(is_array($item)) {
	 			$tmp = $this->regex_search_array($item, $regex);
	 			if($tmp)
	 				$return[$key] = $tmp;
	 		} elseif(!is_object($item)) {
	 			//if (preg_match("/^.*".$regex.".*$/", $this->stripAccents($item))) {
	 			if (preg_match("/".$regex."/i", $this->stripAccents($item))) {
	                $return[$key] = $item;
	           	}
	 		}
		}
	 	
	    return $return;
	}
	
	/**
	 * Remove accent
	 * 
	 * @param string $string
	 * @return string
	 */
	private function stripAccents($string){
		$string = utf8_decode($string);
		$string = strtr($string,
						utf8_decode('Ã Ã¡Ã¢Ã£Ã¤Ã§Ã¨Ã©ÃªÃ«Ã¬Ã­Ã®Ã¯Ã±Ã²Ã³Ã´ÃµÃ¶Ã¹ÃºÃ»Ã¼Ã½Ã¿Ã€Ã�Ã‚ÃƒÃ„Ã‡ÃˆÃ‰ÃŠÃ‹ÃŒÃ�ÃŽÃ�Ã‘Ã’Ã“Ã”Ã•Ã–Ã™ÃšÃ›ÃœÃ�'),
						'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
		return utf8_encode($string);
	}
	
	/**
	 * Remove Meta characters of regex
	 * 
	 * @param string $string
	 * @return string
	 */
	private function stripRegexMetaChars($string) {
		return str_replace(array('\\', '!', '^', '$', '(', ')', '[', ']', '{', '}', '|', '?', '+', '*', '.', '/', '&#039;', '#'),
						array('\\\\', '\!', '\^', '\$', '\(', '\)', '\[', '\]', '\{', '\}', '\|', '\?', '\+', '\*', '\.', '\/', '\\\'', '\#"'),
						$string);
	}
}

?>
