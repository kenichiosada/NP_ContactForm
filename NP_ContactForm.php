<?php
/**
  *
  * NP_ContactForm.php
  *
  * Plugin for adding email contact form on an item page. 
  * It let you select mail sending method from sendmail, 3rd party SMTP, or Gmail. 
  *
  * @author     Osada
  * @version    0.1
  *
 */

class NP_ContactForm extends NucleusPlugin
{
	function getName() { return 'ContactForm';}
    function getAuthor() {return 'Osada';}
    function getURL() {return '';}
    function getVersion() {return '1.0';}
    function getDescription() 
    {
        return 'Adds email contact form. ';
    }
    function supportsFeature($w) { return ($w == 'SqlTablePrefix') ? 1 : 0; }

	function getEventList()
	{
		return array(
			'PrePluginOptionsEdit'
		);
	}		

    function install()
    {
		// Common option
		$this->createOption("method", "Sending method", "select", "0", "Sendmail|0|SMTP_AUTH|1|Gmail|2");
		$this->createOption("from", "FROM address", "text", "");
		$this->createOption("to", "TO address", "text", "");
		$this->createOption("reply", "Use user's email as REPLY TO", "yesno");
		$this->createOption("subject", "Email Subject", "text", "");
		//SMTP only
		$this->createOption("host", "Host", "text", "");
		//SMTP & Gmail only
		$this->createOption("username", "Username", "text", "");
		$this->createOption("password", "Password", "password", "");
		$this->createOption("debug", "Debug Mode", "yesno");
	}

	function uninstall()
	{
		$this->deleteOption("method");
		$this->deleteOption("from");
		$this->deleteOption("to");
		$this->deleteOption("reply");
		$this->deleteOption("subject");
		$this->deleteOption("host");
		$this->deleteOption("username");
		$this->deleteOption("password");
		$this->deleteOption("debug");
	}

	function event_PrePluginOptionsEdit($data)
	{		
		// get option IDs of selected form elements
		$context = $data['context'];
		$optionNames = array('method','host','username','password','debug');
		$oid = array();
		foreach ($optionNames as $name) {
			array_push($oid, $this->_getOID($context, $name));
		}
		
		// add JavaScript to hide some form elements on changing dorpmenu
		$data['options'][$oid[0]]['extra'] =  
			'<script src="plugins/contactform/contactform.js"></script>';	

		// For logging (delete later)
		include '/contactform/Logging.php';
		$log = new Logging();
		$log->lfile($DIR_NUCLEUS . 'plugins/contactform/log');
		//$log->lwrite($data);
		$log->lclose();
		// End logging
	}
	
} 
