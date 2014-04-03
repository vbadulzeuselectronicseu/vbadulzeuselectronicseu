<?php
function callback($buffer)
{
    $buffer = str_replace("\r\n",' ',$buffer);
    $buffer = str_replace("\n",' ',$buffer);
    $buffer = str_replace("	",' ',$buffer);
    $buffer = str_replace("   ",' ',$buffer);
    $buffer = str_replace("  ",' ',$buffer);
    $buffer = str_replace("        ",' ',$buffer);
    $buffer = preg_replace("'/\*[^>]*?.*?\*/'si","",$buffer);
    return $buffer;
}
ob_start("callback"); 

// require_once('FirePHPCore/fb.php'); // библиотека для 
// require_once('FirePHPCore/FirePHP.class.php');
// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH . '/library'),
    get_include_path(),
))); 
/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

#var_dump($application );
#exit();
$application->bootstrap()->run();
