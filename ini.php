<?php
/******************************************************************************************************************
Initialization file used by the CLI version of MySQLDiff
*******************************************************************************************************************/

$parameters = array(

// So that MySQLDiff can work, it requires a database connection with enough privileges to create a temporary database:
    
// Database host name:
"hostName"=>"localhost",

// Database user name:
"dbUser"=>"root",

// Database password:
"dbUserPw"=>"",

// Dump file of the source database:
"source"=>"",

// Live source database. If specified, it overrides the 'source' parameter:
"sourceDb"=>"classtest_201",

// Dump file of the target database:
"target"=>"",

// Live target database. If specified, it overrides the 'target' parameter:
"targetDb"=>"classtest_212rss",

// Output file name. The result will be written to stdout (or displayed in the browser) if left blank:
"output"=>"",

//////////////////////////////////////////////////////////////////////////////////////////////////
// Options:
//////////////////////////////////////////////////////////////////////////////////////////////////

// Change table type
"type"=>1,

// Alter table options
"options"=>1,

// Consider auto_increment parameter
"auto_incr"=>0,

// Alter table charset
"charset"=>1,

// Alter comments
"comment"=>1,

// Generate hint on changes in attribute format
"changes"=>1,

// Move foreign keys to the end of script
"cfk_back"=>1,

// Deactivate foreign key checks before script run.
"no_cfk_checks"=>1,

// Use Backticks for table and attribute names
"backticks"=>0,

// Create INSERT-statements for the tables
"data_insert"=>0,

// Create REPLACE-statements for the tables
"data_replace"=>0,

// Merge statements
"short"=>1,

);

?>
