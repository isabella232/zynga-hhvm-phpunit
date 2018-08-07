<?hh

// --
// This is the driver file for dealing with the fact that 3.18.x series has
// issues with handling arguments as part of #!, but will work fine if a driver
// script handles them via hhvm <args> scriptName
// --

$projectRoot = dirname(dirname(dirname(dirname(dirname(__FILE__)))));

require_once $projectRoot.'/vendor/autoload.php';
require_once $projectRoot.'/vendor/zynga/phpunit/vendor/autoload.php';

use Zynga\Framework\Environment\CodePath\V1\CodePath;
use Zynga\PHPUnit\V2\RunSingleTestRunner;

$userName = 'unknown';

if (isset($_ENV['USER'])) {
  $userName = $_ENV['USER'];
} else {
  $userName = get_current_user();
}

// This is a work around that the environment may / may not be fully up at this
// moment, so codepath might be unititalized.
if (CodePath::getRoot() == '') {
  CodePath::setRoot($projectRoot);
}

$enableXHProf = false;
$cleanArgv = array();

foreach ($argv as $argValue) {
  if ($argValue == '--zynga-with-xhprof') {
    $enableXHProf = true;
  } else {
    $cleanArgv[] = $argValue;
  }
}

use Zynga\Framework\Performance\V1\XHProfiler;

// --
// JEO: needed to be able to performance profile phpunit itself, it has a nasty startup cost.
// --
if ($enableXHProf === true) {
  putenv('xhprof.enable=true');
  XHProfiler::setProfileDir(CodePath::getRoot().'/tmp/phpunit-xhprof');
  XHProfiler::startProfiling();
}

$runner = new RunSingleTestRunner($projectRoot, $userName, $cleanArgv);

$runRV = $runner->run();

if ($enableXHProf === true) {
  $name = 'phpunit-commandline';
  XHProfiler::stopProfiling($name);
}

exit($runRV);
