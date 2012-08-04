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
  * Usage: 
  *
  * [%(text,name,1,)%]
  * [%(text,email,1,)%]
  * [%(textarea,message,1,Enter text here)%]
  * [%(radio,option1,0,option 1|0|option2|1|option3|2)%]
  * [%(checkbox,check1,0,Choice 1|0)%]
  * [%(checkbox,check2,0,Choice 2|1)%]
  * [%(select,selection1,0,Select 1|0|Select 2|1|Select3|2)%]
  * [%(submit,Submit)%]
  * 
  *
 */

require_once ('/contactform/class.phpmailer.php');


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
		// Form
		$this->createOption("form", "Form HTML (Enter w/o <form></form>)", "textarea", "");
		$this->createOption("debug", "Debug mode", "yesno");
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
		$this->deleteOption("form");
		$this->deleteOption("debug");
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
	}
	
	function doItemVar(&$item, $param)
	{	
		$settings = $this->readMarkup($this->getOption('form'));
		$error = array();
		if (!isset($_POST['submit'])){	
			$this->showForm($item->itemid, $settings, $extra);
		} else {
			// Validate user input
			$elementnum = 0;
			foreach ($settings as $setting) {
				$name = $setting['name'];	
				// Remove default value from $_POST
				if ($_POST[$name] == $setting['option']) {
					if ($name != 'submit' && $name != 'Submit') {
						$_POST[$name] = '';	
					}
				}
				// Grab POST values and sanitize them
				$value = ($setting['type'] == 'textarea') ? 
					htmlspecialchars($_POST[$name], ENT_QUOTES) : $this->clean_var($_POST[$name]);
				$settings[$elementnum]['value'] = $value;
				// Check for required field
				if ($setting['required'] == 1) {					
					if ($setting['type'] != 'select') {
						if (empty($value)) {
							$error['general']['status'] = 1;
							array_push($error['general']['errornum'], $elementnum);
						}
					}	
				}
				// Validate email address
				if ($setting['name'] == 'email') {
					$result = $this->validEmail($setting['value']);
					if ($result == false) {
						$error['email']['status'] = 1;
						array_push($error['email']['errornum'], $elementnum);
					}
				}
				// Switch form's default value with user inputs
				if ($setting['type'] == 'text' || $setting['type'] == 'textarea') {
					if (!empty($_POST[$name])) {
						$settings[$elementnum]['option'] = $value;
					}
				} 
				$elementnum++;
			}
			// Process form submission
			if ($error['general']['status'] == 1 || $error['email']['status'] == 1) {
				$settings = $this->generateTags($settings);
				foreach ($error['general']['errornum'] as $num) {
					$settings[$num]['tag'] .= '<div class="form_error">Required field</div>';
				}
				foreach ($error['email']['errornum'] as $num) {
					$settings[$num]['tag'] .= '<div class="form_error">Not a valid Email address</div>';
				}
				$this->showForm($item->itemid, $settings, $extra);
			} else {
				$result = $this->sendMail($settings);
				if (!$result) {
					echo "Something wrong";
				} else {
					echo "Success";
				}
			}
		}
	}

	// Send mail
	// Takes  form settings and send email. 
	// Return true if succeed.
	function sendMail($settings)
	{
		$mail = new PHPMailer();
		
		// Grab common settings
		$message = '';
		$email = '';
		foreach ($settings as $setting) {
			// Get user's email address if such field exists.
			if ($setting['type'] == 'text' && $setting['name'] == 'email' && isset($setting['value'])) {
				$email = $value;	
			}
			// Compile a message to be sent.
			$type = array ('text', 'radio', 'select', 'checkbox');
			if (!empty($setting['value'])) {
				if (in_array($setting['type'], $type)) {
					$message .= $setting['name'] . ": " . $setting['value'] . "\r\n";
				} elseif ($setting['type'] == 'textarea') {
					$message .= $setting['name'] . ": \r\n" . $setting['value'] . "\r\n";
				}
			}
		}
		$mail->SetFrom($this->getOption('from'));
		$mail->AddAddress($this->getOption('to'));
		if ($this->getOption('reply') == 'yes' && isset($email)) {
			$mail->AddReplyTo($email, $email);
		}
		$mail->Subject = $this->getOption('subject');
		$mail->Body = $message;
		$mail->WordWrap = 100;

		// Choose which method to use
		$method = $this->getOption("method");
		switch ($methiod) {
			case 0:
				$mail->IsSendmail();	
				break;
			case 1:
				$mail->Host = $this->getOption('host');
				$mail->Port = $this->getOption('port');
				break;
			case 2:
				$mail->Host = 'smtp.gmail.com';
				$mail->SMTPSecure = 'tls';
				$mail->Port = '587';
				break;
			default:
				$mail->IsSendmail();
				break;
		}

		// Settings for SMTP and Gmail SMTP
		if ($method == 1 || $method ==2) {
			$mail->IsSMTP();
			$mail->SMTPAuth = true;
			$mail->Username = $this->getOption('username');
			$mail->Password = $this->getOption('password');
			$mail->SMTPDebug = 1;
		}

		if (!$mail->Send()) {
			if ($this->getOption('debug') == 'yes') {
				echo $mail->ErrorInfo;
			}
			return false;
		} else {
			return true;
		}
	}

	
	// Show Form
	// Takes form code along with extra stuff to add to the code.
	// Dislay form. 
	function showForm($itemid, $settings, $extra)
	{
		echo '<form name="contactform" id="contactform" method="post" action="' 
			 . createItemLink($itemid) . '" onsubmit="return showProcess();">' . "\r\n";
		
		echo $this->getFormCode($this->getOption('form'), $settings);

		// Process extra here

		echo '</form>' . "\r\n" 
			 . '<div id="other" class="hidePane">Processing</div>' . "\r\n" 
			 . $this->addJs() . "\r\n" 
			 . $this->addCss() . "\r\n";
	}

	// Read Markup
	// Take cutom markup which user entered in plugin setting page. 
	// Break it down and store it in an array. 
	// Convert markup information into tags and add them to the array. 
	// Returns the array. 
	function readMarkup($markup)
	{
		// Break down markups
		$settings = array();
		$a_keys = array('type', 'name', 'required', 'option');
		preg_match_all('/\[%\(.*%\]/', $markup, $matches);
		foreach ($matches as $match) {
			$pattern = array('/\[%\(/', '/\)%\]/');
			$stripped = preg_replace($pattern, '', $match);
			foreach ( $stripped as $set) {
				$a_value = explode(',',$set);
				$i = 0;
				foreach ( $a_keys as $key ) {
					$setting[$key] = $a_value[$i];
					$i++;
				}
				array_push($settings, $setting);	
			}
		}
		$settings = $this->generateTags($settings);
		return $settings;
	}
	
	// Generate tags based on custom markup information
	// Take array of markup information and create tags.
	// Add tags to the array and return it. 
	function generateTags($settings)
	{
		$i = 0;
		foreach ($settings as $setting) {
			if ($setting['type'] == 'text') {
				$tag = '<input type="text" name="' . $setting['name'] . '" id="' . $setting['name'];
				if (isset($setting['value'])) {
					$tag .= '"value= "' . $setting['value'];
				} else {
					$tag .= '" value = "' . $setting['option'];
				}
				$tag .= '" />' . "\r\n";
			} elseif ($setting['type'] == 'checkbox') {
				preg_match('/[a-zA-Z0-9\s]+\|+?[a-z-A-Z0-9\s]+/', $setting['option'], $match);
				$tag = '';
				$options = explode('|', $match[0]);	
				$tag .= '<input type="' . $setting['type'] . '" ' 
						 . 'name="' . $setting['name'] . '" '
						 . 'id="' . $setting['name'] . '" ' 
						 . 'value="' . $options[1] . '" ';
				if ($setting['value'] == $options[1]) {
					$tag .= 'checked ';
				}
				$tag .= ' /> ' . $options[0] . "\r\n";
			} elseif ($setting['type'] == 'radio') {
				preg_match_all('/[a-zA-Z0-9\s]+\|+?[a-z-A-Z0-9\s]+/', $setting['option'], $matches);
				$tag = '';
				$optid = 1;
				foreach ($matches[0] as $match) {		
					$options = explode('|', $match);	
					$tag .= '<input type="' . $setting['type'] . '" ' 
						 . 'name="' . $setting['name'] . '" '
						 . 'id="' . $setting['name'] . $optid . '" ' 
						 . 'value="' . $options[1] . '"';
				   	if ($setting['value'] == $options[1]) {
						$tag .= 'checked ';
					}	
					$tag .= ' /> ' . $options[0] . "\r\n";
					$optid++;
				}
			} elseif ($setting['type'] == 'select') {
				$tag = '<select name="' . $setting['name'] . '" id="' . $setting['name'] . '">' . "\r\n";
				preg_match_all('/[a-zA-Z0-9\s]+\|+?[a-zA-Z0-9\s]+/', $setting['option'], $matches);
				foreach ($matches[0] as $match) {
					$option = explode('|', $match);
					if ($setting['value'] == $option[1]) {
						$tag .= '<option value="' . $option[1] . '" selected>' 
							 . $option[0] . '</option>' . "\r\n";
					} else {
						$tag .= '<option value="' . $option[1] . '">' 
							 . $option[0] . '</option>' . "\r\n";
					}
				}
				$tag .= '</select>' . "\r\n";
			} elseif ($setting['type'] == 'textarea') {
				$tag = '<textarea name="' . $setting['name'] . '" id="' . $setting['name'] .'">' . $setting['option'] . '</textarea>';
			} elseif ($setting['type'] == 'submit') {
				$tag = '<input type="submit" name="submit" id="submit" value="' . $setting['name'] . '" />' . "\r\n";
			} else {
				$tag = '';
			}
			$setting['tag'] = $tag;
			$settings[$i] = $setting;
			$i++;
		}
		return $settings;
	}	

	// getFormCode
	// Replace custom markups with form elements. 
	// Return modified code.
	function getFormCode($markup, $settings)
	{
		// Replace custom markups with HTML tags
		preg_match_all('/\[%\(.*\)%\]/', $markup, $matches);
		$tags = array();
		foreach ($settings as $setting) {
			array_push($tags, $setting['tag']);
		}
		$markup = str_replace($matches[0], $tags, $markup);

		return $markup;		
	}
	
	// returns JavaScript for displaying 'processing...' message
	function addJs()
	{
		$js = <<<EOT
			<script type="text/javascript">
				window.onunload = function() {};
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
					top: 50%;
					visibility: visible;
					width: 100%;
				}
				.hidePane {
					visibility: hidden;
				}
				.form_error {
					color: #FF0000;
				}
			</style>
EOT;
		return $css;
	}

	// clean up user input
	function clean_var($variable) {
    	$variable = strip_tags(stripslashes(trim(rtrim($variable))));
  		return $variable;
	}

	// Validate email
	// Takes email address and retruns boolean for validity.
	// Thanks to http://www.easyphpcontactform.com and
	// http://www.linuxjournal.com/article/9585
	function validEmail($email)
	{
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else if ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} else if ($local[0] == '.' || $local[$localLen-1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} else if (preg_match('/\\.\\./', $local)) {
				// local part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				$isValid = false;
			} else if (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', 
				str_replace("\\\\","",$local))) {
				// character not valid in local part unless 
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
					$isValid = false;
				}
			}
			if ($isValid && function_exists('checkdnsrr')){
				if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
					// domain not found in DNS
					$isValid = false;
				}
			}
		}
		return $isValid;
	} // end validEmail()

} 
