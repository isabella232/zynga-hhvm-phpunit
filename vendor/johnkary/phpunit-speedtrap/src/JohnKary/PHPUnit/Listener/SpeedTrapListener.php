<?hh

namespace JohnKary\PHPUnit\Listener;

use Zynga\PHPUnit\V2\Interfaces\TestInterface;
use Zynga\PHPUnit\V2\Interfaces\TestListenerInterface;
use Zynga\PHPUnit\V2\TestSuite;

use \Exception;
use Zynga\Framework\Testing\TestCase\V2\Base as TestingTestCaseBase;

/**
 * A PHPUnit TestListener that exposes your slowest running tests by outputting
 * results directly to the console.
 */
class SpeedTrapListener implements TestListenerInterface {
  /**
   * Internal tracking for test suites.
   *
   * Increments as more suites are run, then decremented as they finish. All
   * suites have been run when returns to 0.
   *
   * @var integer
   */
  protected $suites = 0;

  /**
   * Time in milliseconds at which a test will be considered "slow" and be
   * reported by this listener.
   *
   * @var int
   */
  protected $slowThreshold;

  /**
   * Number of tests to report on for slowness.
   *
   * @var int
   */
  protected $reportLength;

  /**
   * Collection of slow tests.
   *
   * @var array
   */
  protected $slow = array();

  /**
   * Construct a new instance.
   *
   * @param array $options
   */
  public function __construct(array $options = array()) {
    $this->loadOptions($options);
  }

  /**
   * An error occurred.
   *
   * @param TestInterface $test
   * @param Exception              $e
   * @param float                   $time
   */
  public function addError(TestInterface $test, Exception $e, float $time) {}

  /**
   * A failure occurred.
   *
   * @param TestInterface                 $test
   * @param Exception $e
   * @param float                                   $time
   */
  public function addFailure(
    TestInterface $test,
    Exception $e,
    float $time,
  ): void {}

  /**
   * A warning occurred.
   *
   * @param TestInterface    $test
   * @param Exception $e
   * @param float                     $time
   *
   * @since Method available since Release 6.0.0
   */
  public function addWarning(
    TestInterface $test,
    Exception $e,
    float $time,
  ): void {}

  /**
   * Incomplete test.
   *
   * @param TestInterface $test
   * @param Exception              $e
   * @param float                   $time
   */
  public function addIncompleteTest(
    TestInterface $test,
    Exception $e,
    float $time,
  ): void {}

  /**
   * Risky test.
   *
   * @param TestInterface $test
   * @param Exception              $e
   * @param float                   $time
   * @since  Method available since Release 4.0.0
   */
  public function addRiskyTest(
    TestInterface $test,
    Exception $e,
    float $time,
  ): void {}

  /**
   * Skipped test.
   *
   * @param TestInterface $test
   * @param Exception              $e
   * @param float                   $time
   */
  public function addSkippedTest(
    TestInterface $test,
    Exception $e,
    float $time,
  ): void {}

  /**
   * A test started.
   *
   * @param TestInterface $test
   */
  public function startTest(TestInterface $test): void {}

  /**
   * A test ended.
   *
   * @param TestInterface $test
   * @param float                   $time
   */
  public function endTest(TestInterface $test, float $time): void {

    if (!$test instanceof TestCase && !$test instanceof TestingTestCaseBase) {
      return;
    }

    $time = $this->toMilliseconds($time);
    $threshold = $this->slowThreshold;

    if ($this->isSlow($time, $threshold)) {
      $this->addSlowTest($test, $time);
    }

  }

  /**
   * A test suite started.
   *
   * @param TestSuite $suite
   */
  public function startTestSuite(TestInterface $suite): void {
    $this->suites++;
  }

  /**
   * A test suite ended.
   *
   * @param TestSuite $suite
   */
  public function endTestSuite(TestInterface $suite) {
    $this->suites--;

    if (0 === $this->suites && $this->hasSlowTests()) {
      arsort($this->slow); // Sort longest running tests to the top

      $this->renderHeader();
      $this->renderBody();
      $this->renderFooter();
    }
  }

  /**
   * Whether the given test execution time is considered slow.
   *
   * @param int $time          Test execution time in milliseconds
   * @param int $slowThreshold Test execution time at which a test should be considered slow (milliseconds)
   * @return bool
   */
  protected function isSlow($time, $slowThreshold) {
    return $time >= $slowThreshold;
  }

  /**
   * Stores a test as slow.
   *
   * @param \TestInterface $test
   * @param int                         $time Test execution time in milliseconds
   */
  protected function addSlowTest(TestInterface $test, $time) {
    $label = $this->makeLabel($test);

    $this->slow[$label] = $time;
  }

  /**
   * Whether at least one test has been considered slow.
   *
   * @return bool
   */
  protected function hasSlowTests() {
    return !empty($this->slow);
  }

  /**
   * Convert PHPUnit's reported test time (microseconds) to milliseconds.
   *
   * @param float $time
   * @return int
   */
  protected function toMilliseconds($time) {
    return (int) round($time * 1000);
  }

  /**
   * Label for describing a test.
   *
   * @param TestInterface $test
   * @return string
   */
  protected function makeLabel(TestInterface $test) {
    $name = 'UNKNOWN';

    if ($test instanceof TestCase) {
      $name = $test->getName();
    }
    return sprintf('%s:%s', get_class($test), $name);
  }

  /**
   * Calculate number of slow tests to report about.
   *
   * @return int
   */
  protected function getReportLength() {
    return min(count($this->slow), $this->reportLength);
  }

  /**
   * Find how many slow tests occurred that won't be shown due to list length.
   *
   * @return int Number of hidden slow tests
   */
  protected function getHiddenCount() {
    $total = count($this->slow);
    $showing = $this->getReportLength();

    $hidden = 0;
    if ($total > $showing) {
      $hidden = $total - $showing;
    }

    return $hidden;
  }

  /**
   * Renders slow test report header.
   */
  protected function renderHeader() {
    echo
      sprintf(
        "\n\nYou should really fix these slow tests (>%sms)...\n",
        $this->slowThreshold,
      )
    ;
  }

  /**
   * Renders slow test report body.
   */
  protected function renderBody() {
    $slowTests = $this->slow;

    $length = $this->getReportLength();
    for ($i = 1; $i <= $length; ++$i) {
      $label = key($slowTests);
      $time = array_shift($slowTests);

      echo sprintf(" %s. %sms to run %s\n", $i, $time, $label);
    }
  }

  /**
   * Renders slow test report footer.
   */
  protected function renderFooter() {
    if ($hidden = $this->getHiddenCount()) {
      echo
        sprintf(
          "...and there %s %s more above your threshold hidden from view",
          $hidden == 1 ? 'is' : 'are',
          $hidden,
        )
      ;
    }
  }

  /**
   * Populate options into class internals.
   *
   * @param array $options
   */
  protected function loadOptions(array $options) {
    $this->slowThreshold =
      isset($options['slowThreshold']) ? $options['slowThreshold'] : 500;
    $this->reportLength =
      isset($options['reportLength']) ? $options['reportLength'] : 10;
  }

  /**
   * Get slow test threshold for given test. A TestInterface can override the
   * suite-wide slow threshold by using the annotation @slowThreshold with
   * the threshold value in milliseconds.
   *
   * The following test will only be considered slow when its execution time
   * reaches 5000ms (5 seconds):
   *
   * <code>
   * \@slowThreshold 5000
   * public function testLongRunningProcess() {}
   * </code>
   *
   * @param TestInterface $test
   * @return int
   */
  protected function getSlowThreshold(TestInterface $test) {

    if (!$test instanceof TestCase) {
      return $this->slowThreshold;
    }

    $ann = $test->getAnnotations();

    return
      isset($ann['method']['slowThreshold'][0])
        ? $ann['method']['slowThreshold'][0]
        : $this->slowThreshold;
  }

  public function flush(): void {}

}
