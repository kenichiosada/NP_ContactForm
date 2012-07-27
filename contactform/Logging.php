<?php

/**
 * Logging class:
 * - contains lfile, lwrite and lclose public methods
 * - lfile sets path and name of log file
 * - lwrite writes message to the log file (and implicitly opens log file)
 * - lclose closes log file
 * - first call of lwrite method will open log file implicitly
 * - message is written with the following format: [d/M/Y:H:i:s] (script name) message
 *
 * Original source:
 *  http://www.redips.net/php/write-to-log-file/
 *
 * It is modified to be able to output contents of an array to a log file.
 * If outputting array, log file should not have any extension. 
 * Adding .txt to a log file will display entire contents of an array in one line. 
 *
 * Usage: 
 *  $log = new Logging();
 *  $log->lfile('/tmp/mylog');
 *  $log->lwrite('Test message1');
 *  $log->lwrite($array);
 *  $log->lclose();
 *
*/

class Logging {
    // declare log file and file pointer as private properties
    private $log_file, $fp;
    // set log file (path and name)
    public function lfile($path) {
        $this->log_file = $path;
    }
    // write message to the log file
	public function lwrite($message) {
		
		// check if $message is an array. 
		if (is_array($message)){
			$message = print_r($message, true);
		}
		
        // if file pointer doesn't exist, then open log file
        if (!$this->fp) {
            $this->lopen();
        }
        // define script name
        $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
        // define current time and suppress E_WARNING if using the system TZ settings
        // (don't forget to set the INI setting date.timezone)
        $time = @date('[d/M/Y:H:i:s]');
        // write current time, script name and message to the log file
        fwrite($this->fp, "$time ($script_name) $message" . PHP_EOL);
    }
    // close log file (it's always a good idea to close a file when you're done with it)
    public function lclose() {
        fclose($this->fp);
    }
    // open log file (private method)
    private function lopen() {
        // in case of Windows set default log file
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $log_file_default = 'c:/php/logfile';
        }
        // set default log file for Linux and other systems
        else {
            $log_file_default = '/tmp/logfile';
        }
        // define log file from lfile method or use previously set default
        $lfile = $this->log_file ? $this->log_file : $log_file_default;
        // open log file for writing only and place file pointer at the end of the file
        // (if the file does not exist, try to create it)
        $this->fp = fopen($lfile, 'a') or exit("Can't open $lfile!");
	}
}

