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
		// SMTP only
		$this->createOption("host", "Host", "text", "");
		// SMTP & Gmail only
		$this->createOption("username", "Username", "text", "");
		$this->createOption("password", "Password", "password", "");
		$this->createOption("debug", "Debug Mode", "yesno");
		// Form
		$this->createOption("form", "Form HTML (Enter w/o <form></form>)", "textarea", "");
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
		$this->deleteOption("form");
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
	}
	
	function doItemVar(&$item, $param)
	{	
		// beginning of form code
		$form =  '<form name="contactform" id="contactform" method="post" action="' 
				 . createItemLink($item->itemid) . '" onsubmit="return showProcess();">' ."\r\n";

		// Use user code for a form, if a user has set it through plugin option page
		// else use default code for a form, a code which defined in addForm().
		$userform = trim($this->getOption("form"));
		if($userform != ''){

			// Validate user's code
			preg_match_all('/(<)((input|textarea)).*?(>)/', $userform, $matches);
			$numfield = count($matches[0]); // number of allowed form elements

			preg_match_all('/(name)(=)(".*?")/', $userform, $matches);
			$numname = count($matches[0]); // number of name attribute
			
			print_r($matches);
			
		} else {
			
		}


		$form .= $userform;
		$form .= $this->addForm();
		
		
		// end of form code
		$form .= '<div class="submit"><button type="submit">Submit</button></div>'
				 . '</form>' . "\r\n"
				 . '<div id="other" class="hidePane">Processing...</div>' . "\r\n";

		
		
		echo $form . $this->addJs() . $this->addCss() . "\r\n";

		


		$method = $this->getOption("method");
		
		/*
		switch ($method) {
			case 0:
				sendMail();
				break;
			case 1:
				sendSmtp();
				break;
			case 2:
				sendGmail();
				break;
			default:
				sendMail();
				break;
		}
		 */
		
		/*
		
		 */
	}

	// return code for displaying default form
	function addForm()
	{
		$formcode = <<<EOT
			<fieldset>
			<legend>Contact Us</legend>
			<div>
				<label for="name">Name</label> 
				<input name="name" id="name" size="30" type="text">
			</div>
			<div>
				<label for="email">Email</label>
				<input name="email" id="email" size="30" type="text">
			</div>			
			<div>
				<label for="message">Message</label>
				<textarea name="message" id="message" cols="30" rows="10"></textarea>
			</div>
			</fieldset>
EOT;
		return $formcode;
	}

	// returns JavaScript for displaying 'processing...' message
	function addJs()
	{
		$js = <<<EOT
			<script type="text/javascript">
				function showProcess()
				{
					document.getElementById("other").className = 
						document.getElementById("other").className.replace
							( /(?:^|\s)hidePane(?!\S)/ , '')
					document.getElementById("other").className += "showPane";
				}
			</script>
EOT;
		return $js;
	}
	
	// returns css code for displaying 'processing...' message
	function addCss()
	{	
		$css = <<<EOT
			<style type="text/css">
				#other {
					position: fixed;
					top: 0;
					left: 0;
				}
				.showPane {
					background-color: #000;
					color: #FFF;
					filter:alpha(opacity=40); /* For IE8 and earlier */
					font-size: 20px;
					height: 100%;
					opacity: 0.4;
					padding-top: 250px;
					text-align: center;
					text-decoration: blink;
					top: 50%;
					visibility: visible;
					width: 100%;
				}
				.hidePane {
					visibility: hidden;
				}
			</style>
EOT;
		return $css;
	}
	
} 
