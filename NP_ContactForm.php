<?
/**
  *
  * NP_ContactForm.php
  * For PHP 5
  *
  * This plugin adds contact form. 
  *
  * @author       Osada
  * @version    1.0
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

    function install()
    {
        $this->createOption("host", "Host Name", "text", "");
        $this->createOption("port", "Port Number", "text", "");
        $this->createOption("username", "User Name", "text", "");
        $this->createOption("password", "Password", "password", "");
        $this->createOption("from_addr", "FROM Address", "text", "");
        $this->createOption("from_name", "FROM Name", "text", "");
        $this->createOption("subject", "Message Subject", "text", "");
        $this->createOption("wordwrap", "Word Wrap", "text", "50");
        $this->createOption("add_addr", "To Address", "text", "");
    }

    function uninstall()
    {
        $this->deleteItemOption("host");
        $this->deleteItemOption("port");
        $this->deleteItemOption("username");
        $this->deleteItemOption("password");
        $this->deleteItemOption("from_addr");
        $this->deleteItemOption("from_name");
        $this->deleteItemOption("subject");
        $this->deleteItemOption("wordwrap");
        $this->deleteItemOption("add_addr");
    }

    function doItemVar($data, $param)
    {
        if (session_id() == '') session_start();
        if (!isset($_SESSION['initiated']))
        {
            session_regenerate_id();
            $_SESSION['initiated'] = true;
        }
        if (!isset($_SESSION['key'])) $_SESSION['key'] = md5(mt_rand());

        global $itemid, $name, $email, $message;
        
        if (!isset($_POST['submit']))
        {
            $this->showForm();
        } 
        else 
        {
            $error = 0;
            
            if(!empty($_POST['name']))
            {
                $name[0] = $this->cleanInput($_POST['name']);
                $name[0] = htmlspecialchars($name[0], ENT_QUOTES);
            }
            else
            {
                $error = 1;
                $name[1] = ' <span class="error">お名前を入力してください。</span>';
            }

            if(!empty($_POST['email']))
            {
                $email[0] = $this->cleanInput($_POST['email']);
                if(!$this->validEmail($email[0]))
                {
                    $error = 1;
                    $email[1] = ' <span class="error">有効なＥメールアドレスを入力してください。</span>';
                }
            }
            else
            {
                $error = 1;
                $email[1] = ' <span class="error">Ｅメールアドレスを入力してください。</span>';
            }

            if(!empty($_POST['message'])) 
            {
                $message[0] = $this->cleanInput($_POST['message']);
                $message[0] = htmlspecialchars($message[0], ENT_QUOTES);
            }
            else
            {
                $error = 1;
                $message[1] = ' <span class="error">メッセージを入力してください。</span>';
            }

            if($error == 1)
            {
                $this->showForm();
            }
            else
            {   
                if ($_SESSION['key'] == $_POST['key'])
                {
                    $name[0] = htmlspecialchars_decode($name[0], ENT_QUOTES);
                    $message[0] = htmlspecialchars_decode($message[0], ENT_QUOTES);

                    $body = "名前：\r\n$name[0]\r\n\r\n";
                    $body .= "Eメールアドレス：\r\n$email[0]\r\n\r\n";
                    $body .= "メッセージ：\r\n$message[0]\r\n\r\n";

                    require_once('contactform/class.phpmailer.php');
                    include('clas.smtp.php');

                    $mail = new PHPMailer();

                    $mail->IsSMTP();
                    $mail->SMTPDebug = 1;
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = "ssl";
                    $mail->Host = $this->getOption('host');
                    $mail->Port = $this->getOption('port');
                    $mail->Username = $this->getOption('username');
                    $mail->Password = $this->getOption('password');
                    $mail->CharSet = "utf-8";
                    $mail->Encoding = "8bit";
                    $mail->SetFrom($this->getOption('from_addr'), $this->getOption('from_name'));
                    $mail->Subject = $this->getOption('subject');
                    $mail->Body = "$body";
                    $mail->WordWrap = $this->getOption('wordwrap');
                    $mail->AddAddress($this->getOption('add_addr'));
                  
                    if(!$mail->Send())
                    {
                        echo "Mailer Error: " . $mail->ErrorInfo;
                    }
                    else
                    {
                        echo "メッセージが送信されました。";
                        $_SESSION['key'] = md5(mt_rand());
                    }
                }
                else
                {
                    session_unset();
                    session_destroy();
                    
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?itemid=' . $itemid);
                }
            }
        }
    }

    
    function showForm()
    {
        global $itemid;
        global $name, $email, $message;
        
        echo <<<EOD
        <form method="post" id="cForm" action="{$_SERVER['PHP_SELF']}?itemid={$itemid}">
            <fieldset>
                <legend>お問い合わせフォーム</legend>
                <div>
                    <label for="name">お名前：{$name[1]}</label>
                    <input type="text" name="name" maxlength="40" size="30" value="{$_POST[name]}" />
                </div>
                <div>
                    <label for="email">Eメールアドレス：{$email[1]}</label>
                    <input type="text" name="email" maxlength="50" size="30" value="{$_POST[email]}" />
                </div>
                <div>
                    <label for="message">メッセージ：{$message[1]}</label>
                    <textarea name="message" cols="30" rows="10">{$_POST[message]}</textarea>
                </div>
                <input type="hidden" name="key" value="{$_SESSION['key']}" />
                <div><input type="submit" name="submit" value="メールを送信" class="submit" /></div>
            </fieldset>
        </form>
        
        <div id="hoverMsg" class="hidePane">
            メッセージを送信中です<span id="processing">…</span>
        </div>
EOD;
    }

    // function to strip all tags, commas, and unnecessory spaces in input.
    function cleanInput($inputValue)
    {
        $inputValue = strip_tags(stripslashes(trim($inputValue)));
        return $inputValue;
    }

    // function to validate email addresses. 
    // Thanks to www.easyphpcontactform.com for email address validation script.
    function validEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex)
        {
            $isValid = false;
        }
        else
        {
            $domain = substr($email, $atIndex+1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64)
            {
                // local part length exceeded
                $isValid = false;
            }
            else if ($domainLen < 1 || $domainLen > 255)
            {
                // domain part length exceeded
                $isValid = false;
            }
            else if ($local[0] == '.' || $local[$localLen-1] == '.')
            {
                // local part starts or ends with '.'
                $isValid = false;
            }
            else if (preg_match('/\\.\\./', $local))
            {
                // local part has two consecutive dots
                $isValid = false;
            }
            else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
            {
                // character not valid in domain part
                $isValid = false;
            }
            else if (preg_match('/\\.\\./', $domain))
            {
                // domain part has two consecutive dots
                $isValid = false;
            }
            else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local)))
            {
                // character not valid in local part unless 
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/',
                    str_replace("\\\\","",$local)))
                {
                    $isValid = false;
                }
            }
            if ($isValid && function_exists('checkdnsrr'))
            {
                if (!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) 
                {
                    // domain not found in DNS
                    $isValid = false;
                }
            }
        }
        return $isValid;
    }

}
