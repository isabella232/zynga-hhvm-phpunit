<?hh // partial

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Exceptions;

/**
 * Base class for all PHPUnit Framework exceptions.
 *
 * Ensures that exceptions thrown during a test run do not leave stray
 * references behind.
 *
 * Every Exception contains a stack trace. Each stack frame contains the 'args'
 * of the called function. The function arguments can contain references to
 * instantiated objects. The references prevent the objects from being
 * destructed (until test results are eventually printed), so memory cannot be
 * freed up.
 *
 * With enabled process isolation, test results are serialized in the child
 * process and unserialized in the parent process. The stack trace of Exceptions
 * may contain objects that cannot be serialized or unserialized (e.g., PDO
 * connections). Unserializing user-space objects from the child process into
 * the parent would break the intended encapsulation of process isolation.
 *
 * @see http://fabien.potencier.org/article/9/php-serialization-stack-traces-and-exceptions
 * @since Class available since Release 3.4.0
 */
use \RuntimeException;
use \Exception as BaseException;

use PHPUnit\Interfaces\Exception as ExceptionInterface;

use \PHPUnit_Framework_TestFailure;
use \PHPUnit_Util_Filter;

class Exception extends RuntimeException implements ExceptionInterface {
    /**
     * @var array
     */
    protected array $serializableTrace;

    public function __construct(string $message = '', int $code = 0, ?BaseException $previous = null) {

        parent::__construct($message, $code, $previous);

        $this->serializableTrace = array();
        $this->serializableTrace = $this->getTrace();

        foreach ($this->serializableTrace as $i => $call) {
            unset($this->serializableTrace[$i]['args']);
        }

    }

    /**
     * Returns the serializable trace (without 'args').
     *
     * @return array
     */
    public function getSerializableTrace(): array {
      return $this->serializableTrace;
    }

    /**
     * @return string
     */
    public function __toString() {

        $string = PHPUnit_Framework_TestFailure::exceptionToString($this);

        if ($trace = PHPUnit_Util_Filter::getFilteredStacktrace($this)) {
            $string .= "\n" . $trace;
        }

        return $string;
    }

    public function __sleep(): mixed {
      $objectVars = get_object_vars($this);
      if ( is_array($objectVars) ) {
        return array_keys($objectVars);
      }
      return null;
    }
}