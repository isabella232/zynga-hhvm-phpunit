<?hh // strict

namespace Zynga\PHPUnit\V2\Interfaces;

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use \Exception;
use \PHPUnit_Framework_Test;
use \PHPUnit_Framework_TestSuite;

/**
 * A Listener for test progress.
 *
 * @since      Interface available since Release 2.0.0
 */
interface TestListenerInterface {
  /**
   * An error occurred.
   *
   * @param PHPUnit_Framework_Test $test
   * @param Exception              $e
   * @param float                  $time
   */
  public function addError(
    PHPUnit_Framework_Test $test,
    Exception $e,
    float $time,
  ): void;

  /**
   * A warning occurred.
   *
   * @param PHPUnit_Framework_Test    $test
   * @param PHPUnit_Framework_Warning $e
   * @param float                     $time
   *
   * @since Method available since Release 6.0.0
   */
  public function addWarning(
    PHPUnit_Framework_Test $test,
    Exception $e,
    float $time,
  ): void;

  /**
   * A failure occurred.
   *
   * @param PHPUnit_Framework_Test                 $test
   * @param PHPUnit_Framework_AssertionFailedError $e
   * @param float                                  $time
   */
  public function addFailure(
    PHPUnit_Framework_Test $test,
    Exception $e,
    float $time,
  ): void;

  /**
   * Incomplete test.
   *
   * @param PHPUnit_Framework_Test $test
   * @param Exception              $e
   * @param float                  $time
   */
  public function addIncompleteTest(
    PHPUnit_Framework_Test $test,
    Exception $e,
    float $time,
  ): void;

  /**
   * Risky test.
   *
   * @param PHPUnit_Framework_Test $test
   * @param Exception              $e
   * @param float                  $time
   *
   * @since Method available since Release 4.0.0
   */
  public function addRiskyTest(
    PHPUnit_Framework_Test $test,
    Exception $e,
    float $time,
  ): void;

  /**
   * Skipped test.
   *
   * @param PHPUnit_Framework_Test $test
   * @param Exception              $e
   * @param float                  $time
   *
   * @since Method available since Release 3.0.0
   */
  public function addSkippedTest(
    PHPUnit_Framework_Test $test,
    Exception $e,
    float $time,
  ): void;

  /**
   * A test suite started.
   *
   * @param PHPUnit_Framework_TestSuite $suite
   *
   * @since Method available since Release 2.2.0
   */
  public function startTestSuite(PHPUnit_Framework_TestSuite $suite): void;

  /**
   * A test suite ended.
   *
   * @param PHPUnit_Framework_TestSuite $suite
   *
   * @since Method available since Release 2.2.0
   */
  public function endTestSuite(PHPUnit_Framework_TestSuite $suite): void;

  /**
   * A test started.
   *
   * @param PHPUnit_Framework_Test $test
   */
  public function startTest(PHPUnit_Framework_Test $test): void;

  /**
   * A test ended.
   *
   * @param PHPUnit_Framework_Test $test
   * @param float                  $time
   */
  public function endTest(PHPUnit_Framework_Test $test, float $time): void;

  /**
   * Flushing of output can be accomplished via this method.
   */
  public function flush(): void;

}
