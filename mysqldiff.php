<?php
/******************************************************************************************************************/
$help = <<<EOD
This is the CLI extension of the MySQLDiff 1.5.0 program. It can be used to detect layout differences between two databases.
MySQLdiff will create a SQL-ALTER-Script which has to be run onto the source database to 'patch' it to the state of the target database.
Both the source and target databases must be specified with their database dump files. The resulting alter script will be either
written to stdout, or in an output file or displayed in the browser.

So that MySQLDiff can work, it requires a database connection with enough privileges to create a temporary database!

The script can be called either from command line or from the browser. 
In the former case, it can take parameters either from an ini file, or from the command line, or from both.
In the latter case, it can take parameters either from an ini file, or from the query string, or from both.
The parameters in the command line/query string always override those read from the ini file.
The default ini file is called 'ini.php'.
For the description of each available parameters, see 'ini.php'.

Examples of calling the script from the command line:

# this runs with parameters taken solely from 'ini.php':
php mysqldiff.php

# this runs with parameters taken solely from an other ini file:
php mysqldiff.php --iniFile=/some/path/other.ini.php

# this runs with parameters mostly read from 'ini.php' and some of them are overridden by the command line:
php mysqldiff.php --source=db1.sql --target=db2.sql

Examples of calling the script from the browser:

# this runs with parameters taken solely from 'ini.php':
http://path/to/mysqldiff/mysqldiff.php

# this runs with parameters mostly read from 'ini.php' and some of them are overridden by the command line:
http://path/to/mysqldiff/mysqldiff.php?source=db1.sql&target=db2.sql

# you can suppress any output messages but the fatal errors with specifying the 'silent' 
# parameter either in the command line, or in the query string. E.g.:
http://path/to/mysqldiff/mysqldiff.php?silent=1
php mysqldiff.php -silent
EOD;
/*********************************************************************************************************************/

// So that the program can be called from any other directory:
chdir(dirname(__FILE__));

$md = new MySQLDiffCLI();
$md->validate();

class MySQLDiffCLI
{
var $silent = FALSE;

function MySQLDiffCLI()
{
    global $parameters, $help, $argc, $argv;
    
    error_reporting(0);
    $iniFileName = "ini.php";  // default ini file
    $parameters = array();
    // if called from command line:
    if( !empty($argc) )
    {
        // reading parameters into $parameters:
        if( $argc>1 ) MySQLDiffCLI::readCLI($parameters);
        if( isset($parameters["help"]) ) $this->outDie($help);
        // if the parameters contain an 'iniFile', we override ini.php with it:
        if( isset($parameters["iniFile"]) ) $iniFileName = $parameters["iniFile"];
        // if we have an ini file, include it:
        if( file_exists("$iniFileName") ) 
        {        
            // this fills out the $parameters array from the ini file:
            if( !empty($parameters->silent) ) $this->silent=TRUE;
            $this->out("Reading ini file: $iniFileName");
            include($iniFileName);
            // if we have also command line parameters, their values can override the values read from the ini file, so we read them again:
            if( $argc>1 ) MySQLDiffCLI::readCLI($parameters);
        }
        else $this->out("Ini file was not specified or doesn't exists. Initializing from the command line.");
    }
    // if called from the browser:
    else
    {
        // reading parameters from the query string into $parameters:
        if( isset($_GET) && count($_GET) ) $parameters = $_GET;
        if( isset($parameters["help"]) ) $this->outDie(nl2br($help));
        // if the parameters contain an 'iniFile', we override ini.php with it:
        if( isset($parameters["iniFile"]) ) $iniFileName = $parameters["iniFile"];
        // if we have an ini file, include it:
        if( file_exists("$iniFileName") ) 
        {        
            // this fills out the $parameters array from the ini file:
            if( !empty($_GET["silent"]) ) $this->silent=TRUE;
            $this->out("Reading ini file: $iniFileName");
            include($iniFileName);
            // if we have also query string parameters, their values can override the values read from the ini file, so we read them again:
            if( isset($_GET) || count($_GET) ) 
            {
                foreach( $_GET as $param=>$value ) $parameters[$param] = $value;
            }
        }
        else $this->out("Ini file was not specified or doesn't exists. Initializing from the query string.");
    }
    foreach( $parameters as $param=>$val ) $this->$param = $val;
}

function validate()
{
    // check for mandatory parameters:
    $this->out("Validating parameters");
    foreach( array("hostName", "dbUser", "dbUserPw") as $param )
    {
        if( !isset($this->$param) || ($param!="dbUserPw" && $this->$param=="") ) $this->outDie("'$param' is mandatory. Program failed - exiting.");
    }
    if( empty($this->source) && empty($this->sourceDb) ) $this->outDie("It is mandatory that you specify either 'source' or 'sourceDb'. Program failed - exiting.");
    if( empty($this->target) && empty($this->targetDb) ) $this->outDie("It is mandatory that you specify either 'target' or 'targetDb'. Program failed - exiting.");
    if( !empty($this->source) )
    {
        if( !($f = fopen($this->source, "r")) ) $this->outDie("'$this->source' couldn't be open for read - exiting.");
        else fclose($f);
    }
    if( !empty($this->target) )
    {
        if( !($f = fopen($this->target, "r")) ) $this->outDie("'$this->target' couldn't be open for read - exiting.");
        else fclose($f);
    }
    if( $this->output && !($f = fopen($this->output, "w")) ) $this->outDie("'$this->output' couldn't be open for write - exiting.");
    else fclose($f);
    
}

function readCLI(&$params) 
{
    global $argv;
    
    foreach ($argv as $arg) 
    {
        if (ereg('--([^=]+)=(.*)',$arg,$reg)) 
        {
            $params[$reg[1]] = $reg[2];
        } 
        elseif(ereg('--?([a-zA-Z0-9]+)',$arg,$reg))
        {
            $params[$reg[1]] = 'true';
        }
        elseif($arg=="help")
        {
            $params["help"]='true';
        }
    }
}

function out($s)
{
    global $argc;
    
    if( !$this->silent )
    {
        if( !empty($argc) ) fwrite( STDOUT, "$s\n");
        else echo "$s<br>";
    }
}

function outDie($s)
{
    global $argc;
    
    if( !empty($argc) ) fwrite( STDERR, "$s\n");
    else echo "$s<br>";
    die();
}

}

// Initialisation of MySQLDiff:
$md->out("Initialising MySQLDiff");

require_once("library/database.lib.php");
require_once("library/url.lib.php");
ini_set("track_errors", 1);

if ( file_exists("config.inc.php") ) require_once("config.inc.php");
require_once("library/global.inc.php");

$redirect="";
require_once("library/resources.lib.php");

// Source:
$_SESSION["target"]["hostname"] = $_SESSION["source"]["hostname"] = $md->hostName;
$_SESSION["target"]["username"] = $_SESSION["source"]["username"] = $md->dbUser;
$_SESSION["target"]["password"] = $_SESSION["source"]["password"] = $md->dbUserPw;

if( !empty($md->sourceDb) )
{
    $_SESSION["source"]["database"] = $md->sourceDb;
    $_SESSION["source"]["select"] = "Database";
}
else
{
    $_SESSION["source"]["upload"] = implode("", file($md->source));
    $_SESSION["source"]["upload_file"] = $md->source;
    $_SESSION["source"]["select"] = "Upload";
}
if( !empty($md->targetDb) )
{
    $_SESSION["target"]["database"] = $md->targetDb;
    $_SESSION["target"]["select"] = "Database";
}
else
{
    $_SESSION["target"]["upload"] = implode("", file($md->target));
    $_SESSION["target"]["upload_file"] = $md->target;
    $_SESSION["target"]["select"] = "Upload";
}

$md->out("Checking MySql connection");

if ( !checkConnection($_SESSION["source"], $cfg_source, $info) ) 
{
    $md->outDie(sprintf($textres["error_connection_failed"], $info));
}
if ( !checkConnection($_SESSION["target"], $cfg_source, $info) ) 
{
    $md->outDie(sprintf($textres["error_connection_failed"], $info));
}
$sourcedesc = "source";
$targetdesc = "target";

// Options:
$options = array("type","options","auto_incr","charset","comment","changes",
                 "cfk_back","no_cfk_checks","backticks","short");
foreach( $options as $opt ) $_SESSION["options"][$opt] = $md->$opt;
$_SESSION["options"]["data"] = $md->data_insert || $md->data_replace;

if($_SESSION["options"]["data"] ) $tables = fetchTables($_SESSION["target"]);
if( $md->data_insert ) $_SESSION["data"]["insert"] = $tables;
if( $md->data_replace ) $_SESSION["data"]["replace"] = $tables;

$md->out("Generating SQL result");
require_once("library/generator.lib.php");

ob_start();
generateScript(FALSE, FALSE);
$result = str_replace("&nbsp;", " ", ob_get_contents());
ob_end_clean();

if( $md->output ) file_put_contents($md->output, $result);
elseif( !empty($argc) ) fwrite( STDOUT, "$result\n");
else echo "<pre>$result</pre><br>";

function checkConnection($data, &$cfg, &$info) {
	GLOBAL $php_errormsg;

	$result = FALSE;

	$con = new cConnection(is_numeric($data["hostname"]) ? $cfg[$data["hostname"]]["hostname"] : $data["hostname"], $data["username"], $data["password"]);
	if ( $con->open() ) {
		if ( isset($data["database"]) && trim($data["database"]) != "" ) {
			$result = $con->selectDatabase(trim($data["database"]));
		} else $result = TRUE;
	} else {
		$info = isset($php_errormsg) ? $php_errormsg : NULL;
	}
	return $result;
}

function fetchTables($data) {
	$result=NULL;

	$con = New cConnection($data["hostname"], $data["username"], $data["password"]);
	if ( $con->open() ) {
		$result = $con->fetchTablelist($data["database"], FALSE, FALSE);
		$con->close();
	}
	return isset($result) ? $result : NULL;
}

?>
