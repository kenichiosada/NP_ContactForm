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
		$optionNames = array('method','form');
		$oid = array();
		foreach ($optionNames as $name) {
			array_push($oid, $this->_getOID($context, $name));
		}
		
		// add JavaScript to hide some form elements on changing dorpmenu
		$data['options'][$oid[0]]['extra'] =  
			'<script src="plugins/contactform/contactform.js"></script>';

		// validate form code entered by a user
		$userform = trim($data['options'][$oid[1]]['value']);
		if ($userform != '') {
			$errormsg = $this->validate($userform);			
			$data['options'][$oid[1]]['extra'] = $errormsg;
		}
	
	}
	
	function doItemVar(&$item, $param)
	{	
		if (!isset($_POST['submit'])){
			
			// Show form
			// Use user code for a form, if a user has set it through plugin option page,
			// else use default code for a form, a code which defined in addForm().
			$form =  '<form name="contactform" id="contactform" method="post" action="' 
					 . createItemLink($item->itemid) . '" onsubmit="return showProcess();">' ."\r\n";
			$userform = trim($this->getOption("form"));
			if ($userform != '') {
				$form .= $userform;
				$postvar = implode(',', $this->grabNameAttr($userform));
				$form .= '<input type="hidden" value="' . $postvar . '" name="postvar" />';
			} else {
				$form .= $this->addForm();
			}
			$form .= '</form>' . "\r\n"
					 . '<div id="other" class="hidePane">Processing...</div>' . "\r\n";		
			
			echo $form . $this->addJs() . $this->addCss() . "\r\n";

		} else {

			// Create message to be sent.
			$message = "";
			if (isset($_POST['postvar'])) {
				$postvar = explode(',', $_POST['postvar']);
				foreach ($postvar as $var) {
					$message .= "$var: $_POST[$var]\r\n\r\n";
				}
			} else {
				$message = $_POST['message'];
			}

			echo $message;
			
		}
		
		
		

		
		 

		/*
		 * Send email message according to a method set on plugin option page. 
		 */
		/*	
		$method = $this->getOption("method");
		
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
			<div class="submit"><button type="submit" name="submit">Submit</button></div>
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
	
	// function to validate form code entered by a user
	// user HTMLPurifier in future for more advanced filtering
	// It takes a code entred by a user and returns error message. (empty if no error)
	function validate($userform)
	{	
		$errormsg = '';
		// match form elements
		preg_match_all('/(<)((input|textarea|select|button)).*?(>)/', $userform, $matches);
		$numfield = count($matches[0]); // number of allowed form elements
		
		// match submit buttons	
		/*
		preg_match_all('/(<)(input|button).*?type=("|\')submit("|\').*?(>)/', $userform, $matches);
		$numsubmit = count($matches[0]); // number of submit buttons
		if ($numsubmit>0){
			$numfield = $numfield - $numsubmit;
			$errormsg .= 
				'Do not include submit buttons.<br />' . "\r\n";
		}
		*/

		// match name attribute
		preg_match_all('/(name)(=)(("|\').*?("|\'))/', $userform, $matches);
		$numname = count($matches[0]); // number of name attribute
		if ($numfield != $numname ) {
			$errormsg .=  
				'Please make sure to add name attribute to each form element.<br />' . "\r\n";
		}
		// check if code include <form>
		preg_match_all('/(<)(form).*?(>)|(<\/form>)/', $userform, $matches);
		if (count($matches[0])>0){
			$errormsg .= 'Do not include &lt;form&gt; tag.<br />' . "\r\n"; 
		}
		// make error message colorful
		if (strlen($errormsg) > 0 ){
			$errormsg = '<div style="color:#FF0000;">' . $errormsg;
			$errormsg .= '</div>';
		}
		return $errormsg;
	}

	// Grab name attributes from a code entered by a user.
	// Returns an array of attributes to be used in $_POST. 
	function grabNameAttr($code)
	{
		preg_match_all('/(name)(=)(("|\').*?("|\'))/', $code, $matches);

		$attributes = array();
		$pattern = array('/("|\')/','/name=/');		
		
		foreach ($matches[0] as $match){
			$attr = preg_replace($pattern, '', $match);
			if ($attr != 'submit') {
				array_push($attributes, $attr);
			}
		}
		return $attributes;
	}
} 
