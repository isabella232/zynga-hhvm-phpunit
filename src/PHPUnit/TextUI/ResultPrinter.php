<?hh

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use PHPUnit\Exceptions\AssertionFailedError;
use PHPUnit\Exceptions\Exception as PHPUnit_Exceptions_Exception;
use PHPUnit\Exceptions\Warning;
use PHPUnit\Interfaces\TestInterface;
use PHPUnit\Interfaces\TestListener;
use PHPUnit\Util\UtilInvalidArgumentHelper;
use PHPUnit\Util\UtilTest;

use SebastianBergmann\Environment\Console;

use \PHPUnit_Util_Printer;
use \PHPUnit_Framework_Test;
use \PHPUnit_Framework_TestSuite;

/**
 * Prints the result of a TextUI TestRunner run.
 *
 * @since Class available since Release 2.0.0
 */
class PHPUnit_TextUI_ResultPrinter extends PHPUnit_Util_Printer implements TestListener
{
    const EVENT_TEST_START      = 0;
    const EVENT_TEST_END        = 1;
    const EVENT_TESTSUITE_START = 2;
    const EVENT_TESTSUITE_END   = 3;

    const COLOR_NEVER   = 'never';
    const COLOR_AUTO    = 'auto';
    const COLOR_ALWAYS  = 'always';
    const COLOR_DEFAULT = self::COLOR_NEVER;

    /**
     * @var array
     */
    private static $ansiCodes = [
      'bold'       => 1,
      'fg-black'   => 30,
      'fg-red'     => 31,
      'fg-green'   => 32,
      'fg-yellow'  => 33,
      'fg-blue'    => 34,
      'fg-magenta' => 35,
      'fg-cyan'    => 36,
      'fg-white'   => 37,
      'bg-black'   => 40,
      'bg-red'     => 41,
      'bg-green'   => 42,
      'bg-yellow'  => 43,
      'bg-blue'    => 44,
      'bg-magenta' => 45,
      'bg-cyan'    => 46,
      'bg-white'   => 47
    ];

    /**
     * @var int
     */
    protected $column = 0;

    /**
     * @var int
     */
    protected $maxColumn;

    /**
     * @var bool
     */
    protected $lastTestFailed = false;

    /**
     * @var int
     */
    protected $numAssertions = 0;

    /**
     * @var int
     */
    protected $numTests = -1;

    /**
     * @var int
     */
    protected $numTestsRun = 0;

    /**
     * @var int
     */
    protected $numTestsWidth;

    /**
     * @var bool
     */
    protected $colors = false;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var bool
     */
    protected $verbose = false;

    /**
     * @var int
     */
    private $numberOfColumns;

    /**
     * @var bool
     */
    private $reverse = false;

    /**
     * @var bool
     */
    private $defectListPrinted = false;

    /**
     * Constructor.
     *
     * @param mixed      $out
     * @param bool       $verbose
     * @param string     $colors
     * @param bool       $debug
     * @param int|string $numberOfColumns
     * @param bool       $reverse
     *
     * @throws PHPUnit_Exceptions_Exception
     *
     * @since Method available since Release 3.0.0
     */
    public function __construct($out = null, $verbose = false, $colors = self::COLOR_DEFAULT, $debug = false, $numberOfColumns = 80, $reverse = false)
    {
        parent::__construct($out);

        if (!is_bool($verbose)) {
            throw UtilInvalidArgumentHelper::factory(2, 'boolean');
        }

        $availableColors = [self::COLOR_NEVER, self::COLOR_AUTO, self::COLOR_ALWAYS];

        if (!in_array($colors, $availableColors)) {
            throw UtilInvalidArgumentHelper::factory(
                3,
                vsprintf('value from "%s", "%s" or "%s"', $availableColors)
            );
        }

        if (!is_bool($debug)) {
            throw UtilInvalidArgumentHelper::factory(4, 'boolean');
        }

        if (!is_int($numberOfColumns) && $numberOfColumns != 'max') {
            throw UtilInvalidArgumentHelper::factory(5, 'integer or "max"');
        }

        if (!is_bool($reverse)) {
            throw UtilInvalidArgumentHelper::factory(6, 'boolean');
        }

        $console            = new Console();
        $maxNumberOfColumns = $console->getNumberOfColumns();

        if ($numberOfColumns == 'max' || $numberOfColumns > $maxNumberOfColumns) {
            $numberOfColumns = $maxNumberOfColumns;
        }

        $this->numberOfColumns = $numberOfColumns;
        $this->verbose         = $verbose;
        $this->debug           = $debug;
        $this->reverse         = $reverse;

        if ($colors === self::COLOR_AUTO && $console->hasColorSupport()) {
            $this->colors = true;
        } else {
            $this->colors = (self::COLOR_ALWAYS === $colors);
        }
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    public function printResult(PHPUnit_Framework_TestResult $result)
    {
        $this->printHeader();
        $this->printErrors($result);
        $this->printWarnings($result);
        $this->printFailures($result);

        if ($this->verbose) {
            $this->printRisky($result);
            $this->printIncompletes($result);
            $this->printSkipped($result);
        }

        $this->printFooter($result);
    }

    /**
     * @param array  $defects
     * @param string $type
     */
    protected function printDefects(array $defects, $type)
    {
        $count = count($defects);

        if ($count == 0) {
            return;
        }

        if ($this->defectListPrinted) {
            $this->write("\n--\n\n");
        }

        $this->write(
            sprintf(
                "There %s %d %s%s:\n",
                ($count == 1) ? 'was' : 'were',
                $count,
                $type,
                ($count == 1) ? '' : 's'
            )
        );

        $i = 1;

        if ($this->reverse) {
            $defects = array_reverse($defects);
        }

        foreach ($defects as $defect) {
            $this->printDefect($defect, $i++);
        }

        $this->defectListPrinted = true;
    }

    /**
     * @param PHPUnit_Framework_TestFailure $defect
     * @param int                           $count
     */
    protected function printDefect(PHPUnit_Framework_TestFailure $defect, $count)
    {
        $this->printDefectHeader($defect, $count);
        $this->printDefectTrace($defect);
    }

    /**
     * @param PHPUnit_Framework_TestFailure $defect
     * @param int                           $count
     */
    protected function printDefectHeader(PHPUnit_Framework_TestFailure $defect, $count)
    {
        $this->write(
            sprintf(
                "\n%d) %s\n",
                $count,
                $defect->getTestName()
            )
        );
    }

    /**
     * @param PHPUnit_Framework_TestFailure $defect
     */
    protected function printDefectTrace(PHPUnit_Framework_TestFailure $defect)
    {
        $e = $defect->thrownException();
        $this->write((string) $e);

        if ( $e instanceof Exception ) {
          while ($e->getPrevious() instanceof Exception && $e = $e->getPrevious()) {
            $this->write("\nCaused by\n" . $e);
          }
        }
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    protected function printErrors(PHPUnit_Framework_TestResult $result)
    {
        $this->printDefects($result->errors(), 'error');
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    protected function printFailures(PHPUnit_Framework_TestResult $result)
    {
        $this->printDefects($result->failures(), 'failure');
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    protected function printWarnings(PHPUnit_Framework_TestResult $result)
    {
        $this->printDefects($result->warnings(), 'warning');
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    protected function printIncompletes(PHPUnit_Framework_TestResult $result)
    {
        $this->printDefects($result->notImplemented(), 'incomplete test');
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     *
     * @since Method available since Release 4.0.0
     */
    protected function printRisky(PHPUnit_Framework_TestResult $result)
    {
        $this->printDefects($result->risky(), 'risky test');
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     *
     * @since Method available since Release 3.0.0
     */
    protected function printSkipped(PHPUnit_Framework_TestResult $result)
    {
        $this->printDefects($result->skipped(), 'skipped test');
    }

    protected function printHeader()
    {
        $this->write("\n\n" . PHP_Timer::resourceUsage() . "\n\n");
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     */
    protected function printFooter(PHPUnit_Framework_TestResult $result)
    {
      $color = null;
        if (count($result) === 0) {
            $this->writeWithColor(
                'fg-black, bg-yellow',
                'No tests executed!'
            );

            return;
        }

        if ($result->wasSuccessful() &&
            $result->allHarmless() &&
            $result->allCompletelyImplemented() &&
            $result->noneSkipped()) {
            $this->writeWithColor(
                'fg-black, bg-green',
                sprintf(
                    'OK (%d test%s, %d assertion%s)',
                    count($result),
                    (count($result) == 1) ? '' : 's',
                    $this->numAssertions,
                    ($this->numAssertions == 1) ? '' : 's'
                )
            );
        } else {
            if ($result->wasSuccessful()) {
                $color = 'fg-black, bg-yellow';

                if ($this->verbose) {
                    $this->write("\n");
                }

                $this->writeWithColor(
                    $color,
                    'OK, but incomplete, skipped, or risky tests!'
                );
            } else {
                $this->write("\n");

                if ($result->errorCount()) {
                    $color = 'fg-white, bg-red';

                    $this->writeWithColor(
                        $color,
                        'ERRORS!'
                    );
                } elseif ($result->failureCount()) {
                    $color = 'fg-white, bg-red';

                    $this->writeWithColor(
                        $color,
                        'FAILURES!'
                    );
                } elseif ($result->warningCount()) {
                    $color = 'fg-black, bg-yellow';

                    $this->writeWithColor(
                        $color,
                        'WARNINGS!'
                    );
                }
            }

            $this->writeCountString(count($result), 'Tests', $color, true);
            $this->writeCountString($this->numAssertions, 'Assertions', $color, true);
            $this->writeCountString($result->errorCount(), 'Errors', $color);
            $this->writeCountString($result->failureCount(), 'Failures', $color);
            $this->writeCountString($result->warningCount(), 'Warnings', $color);
            $this->writeCountString($result->skippedCount(), 'Skipped', $color);
            $this->writeCountString($result->notImplementedCount(), 'Incomplete', $color);
            $this->writeCountString($result->riskyCount(), 'Risky', $color);
            $this->writeWithColor($color, '.', true);
        }
    }

    /**
     */
    public function printWaitPrompt()
    {
        $this->write("\n<RETURN> to continue\n");
    }

    /**
     * An error occurred.
     *
     * @param TestInterface $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addError(TestInterface $test, Exception $e, float $time): void
    {
        $this->writeProgressWithColor('fg-red, bold', 'E');
        $this->lastTestFailed = true;
    }

    /**
     * A failure occurred.
     *
     * @param TestInterface                 $test
     * @param AssertionFailedError $e
     * @param float                                  $time
     */
    public function addFailure(TestInterface $test, AssertionFailedError $e, float $time): void
    {
        $this->writeProgressWithColor('bg-red, fg-white', 'F');
        $this->lastTestFailed = true;
    }

    /**
     * A warning occurred.
     *
     * @param TestInterface    $test
     * @param Warning $e
     * @param float                     $time
     *
     * @since Method available since Release 5.1.0
     */
    public function addWarning(TestInterface $test, Warning $e, $time)
    {
        $this->writeProgressWithColor('fg-yellow, bold', 'W');
        $this->lastTestFailed = true;
    }

    /**
     * Incomplete test.
     *
     * @param TestInterface $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addIncompleteTest(TestInterface $test, Exception $e, float $time): void
    {
        $this->writeProgressWithColor('fg-yellow, bold', 'I');
        $this->lastTestFailed = true;
    }

    /**
     * Risky test.
     *
     * @param TestInterface $test
     * @param Exception              $e
     * @param float                  $time
     *
     * @since Method available since Release 4.0.0
     */
    public function addRiskyTest(TestInterface $test, Exception $e, float $time): void
    {
        $this->writeProgressWithColor('fg-yellow, bold', 'R');
        $this->lastTestFailed = true;
    }

    /**
     * Skipped test.
     *
     * @param TestInterface $test
     * @param Exception              $e
     * @param float                  $time
     *
     * @since Method available since Release 3.0.0
     */
    public function addSkippedTest(TestInterface $test, Exception $e, float $time): void
    {
        $this->writeProgressWithColor('fg-cyan, bold', 'S');
        $this->lastTestFailed = true;
    }

    /**
     * A testsuite started.
     *
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @since Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite): void
    {
        if ($this->numTests == -1) {
            $this->numTests      = count($suite);
            $this->numTestsWidth = strlen((string) $this->numTests);
            $this->maxColumn     = $this->numberOfColumns - strlen('  /  (XXX%)') - (2 * $this->numTestsWidth);
        }
    }

    /**
     * A testsuite ended.
     *
     * @param PHPUnit_Framework_TestSuite $suite
     *
     * @since Method available since Release 2.2.0
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite): void
    {
    }

    /**
     * A test started.
     *
     * @param TestInterface $test
     */
    public function startTest(TestInterface $test): void
    {
        if ($this->debug) {
            $this->write(
                sprintf(
                    "\nStarting test '%s'.\n",
                    UtilTest::describe($test)
                )
            );
        }
    }

    /**
     * A test ended.
     *
     * @param TestInterface $test
     * @param float                  $time
     */
    public function endTest(TestInterface $test, float $time): void {
        if (!$this->lastTestFailed) {
            $this->writeProgress('.');
        }

        if ($test instanceof TestInterface) {
            $this->numAssertions += $test->getNumAssertions();
        } elseif ($test instanceof PHPUnit_Extensions_PhptTestCase) {
            $this->numAssertions++;
        }

        $this->lastTestFailed = false;

        if ($test instanceof TestInterface) {
            if (!$test->hasExpectationOnOutput()) {
                $this->write($test->getActualOutput());
            }
        }
    }

    /**
     * @param string $progress
     */
    protected function writeProgress($progress)
    {
        $this->write($progress);
        $this->column++;
        $this->numTestsRun++;

        if ($this->column == $this->maxColumn
            || $this->numTestsRun == $this->numTests
        ) {
            if ($this->numTestsRun == $this->numTests) {
                $this->write(str_repeat(' ', $this->maxColumn - $this->column));
            }

            $this->write(
                sprintf(
                    //' %' . $this->numTestsWidth . 'd / %' .
                    //$this->numTestsWidth . 'd (%3s%%)',
                    ' %7d / %7d (%3s%%)',
                    $this->numTestsRun,
                    $this->numTests,
                    floor(($this->numTestsRun / $this->numTests) * 100)
                )
            );

            if ($this->column == $this->maxColumn) {
                $this->writeNewLine();
            }
        }
    }

    protected function writeNewLine()
    {
        $this->column = 0;
        $this->write("\n");
    }

    /**
     * Formats a buffer with a specified ANSI color sequence if colors are
     * enabled.
     *
     * @param string $color
     * @param string $buffer
     *
     * @return string
     *
     * @since Method available since Release 4.0.0
     */
    protected function formatWithColor($color, $buffer)
    {
        if (!$this->colors) {
            return $buffer;
        }

        $trimCallback = function ($line) { return trim($line); };
        $codes   = array_map($trimCallback, explode(',', $color));
        $lines   = explode("\n", $buffer);
        $sizeOfCallback = function ($line) { return strlen($line);};
        $padding = max(array_map($sizeOfCallback, $lines));
        $styles  = [];

        foreach ($codes as $code) {
            $styles[] = self::$ansiCodes[$code];
        }

        $style = sprintf("\x1b[%sm", implode(';', $styles));

        $styledLines = [];

        foreach ($lines as $line) {
            $styledLines[] = $style . str_pad($line, $padding) . "\x1b[0m";
        }

        return implode("\n", $styledLines);
    }

    /**
     * Writes a buffer out with a color sequence if colors are enabled.
     *
     * @param string $color
     * @param string $buffer
     * @param bool   $lf
     *
     * @since Method available since Release 4.0.0
     */
    protected function writeWithColor($color, $buffer, $lf = true)
    {
        $this->write($this->formatWithColor($color, $buffer));

        if ($lf) {
            $this->write("\n");
        }
    }

    /**
     * Writes progress with a color sequence if colors are enabled.
     *
     * @param string $color
     * @param string $buffer
     *
     * @since Method available since Release 4.0.0
     */
    protected function writeProgressWithColor($color, $buffer)
    {
        $buffer = $this->formatWithColor($color, $buffer);
        $this->writeProgress($buffer);
    }

    /**
     * @param int    $count
     * @param string $name
     * @param string $color
     * @param bool   $always
     *
     * @since Method available since Release 4.6.5
     */
    private function writeCountString($count, $name, $color, $always = false)
    {
        static $first = true;

        if ($always || $count > 0) {
            $this->writeWithColor(
                $color,
                sprintf(
                    '%s%s: %d',
                    !$first ? ', ' : '',
                    $name,
                    $count
                ),
                false
            );

            $first = false;
        }
    }
}