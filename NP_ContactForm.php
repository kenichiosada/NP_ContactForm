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
	}
	
	function doItemVar(&$item, $param)
	{	
		if (!isset($_POST['submit'])){
			$this->showForm($this->getOption('form'), $item->itemid);
		} else {
			echo "submitted";
			$this->showForm($this->getOption('form'), $item->itemid);
		}
		
		/*
		//Send email message according to a method set on plugin option page. 
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

	// clean up user input
	function clean_var($variable) {
    	$variable = strip_tags(stripslashes(trim(rtrim($variable))));
  		return $variable;
	}

	

	// Show form
	// Generate HTML code for a contact form based on custom markups entered by a user. 
	// Return true is succeed.
	//
	function showForm($markup, $itemid)
	{
		// Store markup information in an array
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

		// Create HTML tag based on markup information
		// Tags generated are stored in an array along with the markup inforamtion. 
		$i = 0;
		foreach ($settings as $setting) {
			if ($setting['type'] == 'text') {
				$tag = '<input type="text" name="' . $setting['name'] . '" id="' . $setting['name'];
				if (isset($setting['option'])) {
					$tag .= '" value = "' . $setting['option'];
				}
				$tag .= '" />';
			} elseif ($setting['type'] == 'checkbox' || $setting['type'] == 'radio') {
				$options = explode('|', $setting['option']);
				$optid = 1;
				$tag = '';
				foreach ($options as $option) {
					$tag .= '<input type="' . $setting['type'] . '" ' 
						 . 'name="' . $setting['name'] . '" '
						 . 'id="' . $setting['name'] . $optid . '" ' 
						 . 'value="' . $option . '" />' . "\r\n";
					$optid++;
				}
			} elseif ($setting['type'] == 'select') {
				$tag = '<select name="' . $setting['name'] . '" id="' . $setting['name'] . '">' . "\r\n";
				preg_match_all('/[a-zA-Z0-9]+\|+?[a-zA-Z0-9]+/', $setting['option'], $matches);
				foreach ($matches[0] as $match) {
					$option = explode('|', $match);
					$option = explode('|', $match);
					$tag .= '<option value="' . $option[1] . '">' . $option[0] . '</option>' . "\r\n";
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

		// Replace custom markups with HTML tags
		// Add <form> tag to finished product and generate a code for a form.
		//preg_match_all('/\[%\(.*\)%\]/', $markup, $matches);
		preg_match_all('/\[%\((\w+),(\w+).*\)%\]/', $markup, $matches);
		$tags = array();
		foreach ($settings as $setting) {
			array_push($tags, $setting['tag']);
		}
		$markup = str_replace($matches[0], $tags, $markup);

		$code = '<form name="contactform" id="contactform" method="post" action="'
		      . createItemLink($itemid) . '" onsubmit="return showProcess();">' . "\r\n";
		$code .= $markup;
		$code .= '</form>' . "\r\n"
			  .  '<div id="other" class="hidePane">Processing</div>' . "\r\n";
		
		echo $code . $this->addJs() . $this->addCss() . "\r\n";
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
