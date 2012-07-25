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

	function init() {
		
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

	function doItemVar($data, $param)
	{
		
	}

	function event_PrePluginOptionsEdit($data)
	{		
		$data['options'][48]['extra'] = 
			'<script type="text/javascript">' .
			'var rows = document.getElementsByTagName("tr");' .
			'var list = rows[1].getElementsByTagName("select");' .
			'var selection = list[0].options[list[0].selectedIndex].value;' .
			//'rows.item(1).style.display = "none";' .
			'</script>';	
		
		// For logging (delete later)
		include '/contactform/Logging.php';
		$log = new Logging();
		$log->lfile($DIR_NUCLEUS . '/log.txt');
		$contents = print_r($data, true);
		$log->lwrite($contents);
		//$log->lwrite($oid);
		$log->lclose();
		// End logging
	}
	
} 
