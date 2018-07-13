<?hh

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Exceptions\Exception as PHPUnit_Exceptions_Exception;
use PHPUnit\Exceptions\IncompleteTestError;
use PHPUnit\Exceptions\SkippedTestError;
use PHPUnit\Framework\AsyncRunner;
use PHPUnit\Framework\WarningTestCase;
use PHPUnit\Interfaces\SelfDescribingInterface;
use PHPUnit\Interfaces\TestInterface;
use PHPUnit\Interfaces\TestSuiteInterface;
use PHPUnit\Util\UtilInvalidArgumentHelper;
use PHPUnit\Util\UtilTest;
use PHPUnit\Util\UtilTestSuiteIterator;

use Zynga\Framework\Testing\TestCase\V2\Base as ZyngaTestCaseBase;

use \IteratorAggregate;

/**
 * A TestSuite is a composite of Tests. It runs a collection of test cases.
 *
 * Here is an example using the dynamic test definition.
 *
 * <code>
 * <?php
 * $suite = new PHPUnit_Framework_TestSuite;
 * $suite->addTest(new MathTest('testPass'));
 * ?>
 * </code>
 *
 * Alternatively, a TestSuite can extract the tests to be run automatically.
 * To do so you pass a ReflectionClass instance for your
 * PHPUnit_Framework_TestCase class to the PHPUnit_Framework_TestSuite
 * constructor.
 *
 * <code>
 * <?php
 * $suite = new PHPUnit_Framework_TestSuite(
 *   new ReflectionClass('MathTest')
 * );
 * ?>
 * </code>
 *
 * This constructor creates a suite with all the methods starting with
 * "test" that take no arguments.
 *
 * @since Class available since Release 2.0.0
 */
class PHPUnit_Framework_TestSuite implements
  TestSuiteInterface,
  SelfDescribingInterface,
  IteratorAggregate<TestSuiteInterface>
{
    /**
     * Last count of tests in this suite.
     *
     * @var int|null
     */
    private ?int $cachedNumTests = null;

    /**
     * Enable or disable the backup and restoration of the $GLOBALS array.
     *
     * @var bool
     */
    protected ?bool $backupGlobals = null;

    /**
     * Enable or disable the backup and restoration of static attributes.
     *
     * @var bool
     */
    protected ?bool $backupStaticAttributes = null;

    /**
     * @var bool
     */
    private ?bool $beStrictAboutChangesToGlobalState = null;

    /**
     * @var bool
     */
    protected ?bool $runTestInSeparateProcess = false;

    /**
     * The name of the test suite.
     *
     * @var string
     */
    protected string $name = '';

    /**
     * The test groups of the test suite.
     *
     * @var array
     */
    protected Map<string, Vector<TestInterface>> $groups = Map {};

    /**
     * The tests in the test suite.
     *
     * @var array
     */
    protected Vector<TestInterface> $tests = Vector {};

    /**
     * The number of tests in the test suite.
     *
     * @var int
     */
    protected int $numTests = -1;

    /**
     * @var bool
     */
    protected bool $testCase = false;

    /**
     * @var array
     */
    protected Vector<string> $foundClasses = Vector {};

    /**
     * @var PHPUnit_Runner_Filter_Factory
     */
    private ?PHPUnit_Runner_Filter_Factory $iteratorFilter = null;

    /**
     * Constructs a new TestSuite:
     *
     *   - PHPUnit_Framework_TestSuite() constructs an empty TestSuite.
     *
     *   - PHPUnit_Framework_TestSuite(ReflectionClass) constructs a
     *     TestSuite from the given class.
     *
     *   - PHPUnit_Framework_TestSuite(ReflectionClass, String)
     *     constructs a TestSuite from the given class with the given
     *     name.
     *
     *   - PHPUnit_Framework_TestSuite(String) either constructs a
     *     TestSuite from the given class (if the passed string is the
     *     name of an existing class) or constructs an empty TestSuite
     *     with the given name.
     *
     * @param mixed  $theClass
     * @param string $name
     *
     * @throws PHPUnit_Exceptions_Exception
     */
    public function __construct(?ReflectionClass $theClass) {

      // JEO: Come back to this, as they are getting cranky with isSubclassOf
      /*
      if ( $theClass->isSubClassOf(ZyngaTestCaseBase::class) !== true &&
           $theClass->isSubClassOf(PHPUnit_Framework_TestCase::class) !== true ) {
          throw new PHPUnit_Exceptions_Exception(
              'Class "' . $theClass->name . '" does not extend ' .
              '(' . ZyngaTestCaseBase::class . ' or ' .
              PHPUnit_Framework_TestCase::class . ')'
          );
        }
      */

      if ( ! $theClass instanceof ReflectionClass ) {
        return;
      }

        $this->name = $theClass->name;

        $constructor = $theClass->getConstructor();

        if ($constructor !== null &&
            !$constructor->isPublic()) {
            $this->addTest(
                new WarningTestCase(
                    sprintf(
                        'Class "%s" has no public constructor.',
                        $theClass->getName()
                    )
                )
            );

            return;
        }

        foreach ($theClass->getMethods() as $method) {
            $this->addTestMethod($theClass, $method);
        }

        if (empty($this->tests)) {
            $this->addTest(
                new WarningTestCase(
                    sprintf(
                        'No tests found in class "%s".',
                        $theClass->getName()
                    )
                )
            );
        }

        $this->testCase = true;
    }

    /**
     * Returns a string representation of the test suite.
     *
     * @return string
     */
    public function toString()
    {
        return $this->getName();
    }

    /**
     * Adds a test to the suite.
     *
     * @param PHPUnit_Framework_Test $test
     * @param array                  $groups
     */
    public function addTest(TestInterface $test, Vector<string> $groups = Vector {}): void{

        $class = new ReflectionClass($test);

        if (!$class->isAbstract()) {

            $this->tests->add($test);
            $this->numTests = -1;

            if ($test instanceof TestInterface && $groups->count() == 0) {
                $groups = $test->getGroups();
            }

            if ($groups->count() == 0) {
                $groups->add('default');
            }

            foreach ($groups as $group) {

              $groupData = Vector {};

              if ( $this->groups->containsKey($group) ) {
                $t_groupData = $this->groups->get($group);
                if ( $groupData instanceof Vector ) {
                  $groupData = $t_groupData;
                }
              }

              if ( $groupData instanceof Vector ) {
                $groupData->add($test);
                $this->groups->set($group, $groupData);
              }

            }

            if ( $test instanceof TestInterface ) {
              $test->setGroups($groups);
            }

        }
    }

    /**
     * Adds the tests from the given class to the suite.
     *
     * @param mixed $testClass
     *
     * @throws PHPUnit_Exceptions_Exception
     */
    public function addTestSuite($testClass)
    {
      /*
        if (is_string($testClass) && class_exists($testClass)) {
            $testClass = new ReflectionClass($testClass);
        }

        if (!is_object($testClass)) {
            throw UtilInvalidArgumentHelper::factory(
                1,
                'class name or object'
            );
        }

        if ($testClass instanceof self) {
            $this->addTest($testClass);
        } elseif ($testClass instanceof ReflectionClass) {
            $suiteMethod = false;

            if (!$testClass->isAbstract()) {
                if ($testClass->hasMethod(PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME)) {
                    $method = $testClass->getMethod(
                        PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME
                    );

                    if ($method->isStatic()) {
                        $this->addTest(
                            $method->invoke(null, $testClass->getName())
                        );

                        $suiteMethod = true;
                    }
                }
            }

            if (!$suiteMethod && !$testClass->isAbstract()) {
                $this->addTest(new self($testClass));
            }
        } else {
            throw new PHPUnit_Exceptions_Exception();
        }
        */
    }

    /**
     * Wraps both <code>addTest()</code> and <code>addTestSuite</code>
     * as well as the separate import statements for the user's convenience.
     *
     * If the named file cannot be read or there are no new tests that can be
     * added, a <code>WarningTestCase</code> will be created instead,
     * leaving the current test run untouched.
     *
     * @param string $filename
     *
     * @throws PHPUnit_Exceptions_Exception
     *
     * @since Method available since Release 2.3.0
     */
    public function addTestFile($filename)
    {
        if (!is_string($filename)) {
            throw UtilInvalidArgumentHelper::factory(1, 'string');
        }

        if (file_exists($filename) && substr($filename, -5) == '.phpt') {
            $this->addTest(
                new PHPUnit_Extensions_PhptTestCase($filename)
            );

            return;
        }

        // The given file may contain further stub classes in addition to the
        // test class itself. Figure out the actual test class.
        $classes    = get_declared_classes();
        $filename   = PHPUnit_Util_Fileloader::checkAndLoad($filename);
        $newClasses = array_diff(get_declared_classes(), $classes);

        // The diff is empty in case a parent class (with test methods) is added
        // AFTER a child class that inherited from it. To account for that case,
        // cumulate all discovered classes, so the parent class may be found in
        // a later invocation.
        if (!empty($newClasses)) {
            // On the assumption that test classes are defined first in files,
            // process discovered classes in approximate LIFO order, so as to
            // avoid unnecessary reflection.
            $this->foundClasses = array_merge($newClasses, $this->foundClasses);
        }

        // The test class's name must match the filename, either in full, or as
        // a PEAR/PSR-0 prefixed shortname ('NameSpace_ShortName'), or as a
        // PSR-1 local shortname ('NameSpace\ShortName'). The comparison must be
        // anchored to prevent false-positive matches (e.g., 'OtherShortName').
        $shortname      = basename($filename, '.php');
        $shortnameRegEx = '/(?:^|_|\\\\)' . preg_quote($shortname, '/') . '$/';

        foreach ($this->foundClasses as $i => $className) {
            if (preg_match($shortnameRegEx, $className)) {
                $class = new ReflectionClass($className);

                if ($class->getFileName() == $filename) {
                    $newClasses = [$className];
                    unset($this->foundClasses[$i]);
                    break;
                }
            }
        }

        foreach ($newClasses as $className) {
            $class = new ReflectionClass($className);

            if (!$class->isAbstract()) {
                if ($class->hasMethod(PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME)) {
                    $method = $class->getMethod(
                        PHPUnit_Runner_BaseTestRunner::SUITE_METHODNAME
                    );

                    if ($method->isStatic()) {
                        $this->addTest($method->invoke(null, $className));
                    }
                } elseif ($class->implementsInterface('PHPUnit_Framework_Test')) {
                    $this->addTestSuite($class);
                }
            }
        }

        $this->numTests = -1;
    }

    /**
     * Wrapper for addTestFile() that adds multiple test files.
     *
     * @param array|Iterator $filenames
     *
     * @throws PHPUnit_Exceptions_Exception
     *
     * @since Method available since Release 2.3.0
     */
    public function addTestFiles(Traversable $filenames)
    {
        if (!(is_array($filenames) ||
             (is_object($filenames) && $filenames instanceof Iterator))) {
            throw UtilInvalidArgumentHelper::factory(
                1,
                'array or iterator'
            );
        }

        foreach ($filenames as $filename) {
            $this->addTestFile((string) $filename);
        }
    }

    /**
     * Counts the number of test cases that will be run by this test.
     *
     * @param bool $preferCache Indicates if cache is preferred.
     *
     * @return int
     */
    public function count($preferCache = false)
    {
        if ($preferCache && $this->cachedNumTests !== null) {
            $numTests = $this->cachedNumTests;
        } else {
            $numTests = 0;

            foreach ($this as $test) {
                $numTests += count($test);
            }

            $this->cachedNumTests = $numTests;
        }

        return $numTests;
    }

    /**
     * @param ReflectionClass $theClass
     * @param string          $name
     *
     * @return PHPUnit_Framework_Test
     *
     * @throws PHPUnit_Exceptions_Exception
     */
    public static function createTest(ReflectionClass $theClass, $name)
    {
        $className = $theClass->getName();

        if (!$theClass->isInstantiable()) {
            return new WarningTestCase(
                sprintf('Cannot instantiate class "%s".', $className)
            );
        }

        $backupSettings = UtilTest::getBackupSettings(
            $className,
            $name
        );

        $preserveGlobalState = UtilTest::getPreserveGlobalStateSettings(
            $className,
            $name
        );

        $runTestInSeparateProcess = UtilTest::getProcessIsolationSettings(
            $className,
            $name
        );


        $constructor = $theClass->getConstructor();

        $test = $theClass->newInstance($name);

        /*
        $t = null;
        $data = null;

        if ($constructor !== null) {
            $parameters = $constructor->getParameters();

            // TestCase() or TestCase($name)
            if (count($parameters) < 2) {
                $test = new $className($name);
            } else {
                try {
                  // Data providers are disabled.
                    $data = UtilTest::getProvidedData(
                        $className,
                        $name
                    );
                } catch (IncompleteTestError $e) {
                    $message = sprintf(
                        'Test for %s::%s marked incomplete by data provider',
                        $className,
                        $name
                    );

                    $_message = $e->getMessage();

                    if (!empty($_message)) {
                        $message .= "\n" . $_message;
                    }

                    $data = new PHPUnit_Framework_IncompleteTestCase($className, $name, $message);
                } catch (SkippedTestError $e) {
                    $message = sprintf(
                        'Test for %s::%s skipped by data provider',
                        $className,
                        $name
                    );

                    $_message = $e->getMessage();

                    if (!empty($_message)) {
                        $message .= "\n" . $_message;
                    }

                    $data = self::skipTest($className, $name, $message);
                } catch (Throwable $_t) {
                    $t = $_t;
                } catch (Exception $_t) {
                    $t = $_t;
                }

                if (isset($t)) {
                    $message = sprintf(
                        'The data provider specified for %s::%s is invalid.',
                        $className,
                        $name
                    );

                    $_message = $t->getMessage();

                    if (!empty($_message)) {
                        $message .= "\n" . $_message;
                    }

                    $data = new WarningTestCase($message);
                }

                // Test method with @dataProvider.
                if (isset($data)) {
                    $test = new PHPUnit_Framework_TestSuite_DataProvider(
                        $className . '::' . $name
                    );

                    if (empty($data)) {
                        $data = new WarningTestCase(
                            sprintf(
                                'No tests found in suite "%s".',
                                $test->getName()
                            )
                        );
                    }

                    $groups = UtilTest::getGroups($className, $name);

                    if ($data instanceof WarningTestCase ||
                        $data instanceof PHPUnit_Framework_SkippedTestCase ||
                        $data instanceof PHPUnit_Framework_IncompleteTestCase) {
                        $test->addTest($data, $groups);
                    } else {
                        foreach ($data as $_dataName => $_data) {
                            $_test = new $className($name, $_data, $_dataName);

                            if ($runTestInSeparateProcess) {
                                $_test->setRunTestInSeparateProcess(true);

                                if ($preserveGlobalState !== null) {
                                    $_test->setPreserveGlobalState($preserveGlobalState);
                                }
                            }

                            if ($backupSettings['backupGlobals'] !== null) {
                                $_test->setBackupGlobals(
                                    $backupSettings['backupGlobals']
                                );
                            }

                            if ($backupSettings['backupStaticAttributes'] !== null) {
                                $_test->setBackupStaticAttributes(
                                    $backupSettings['backupStaticAttributes']
                                );
                            }

                            $test->addTest($_test, $groups);
                        }
                    }
                } else {
                    $test = new $className();
                }
            }
        }
        */

        if (!isset($test)) {
            throw new PHPUnit_Exceptions_Exception('No valid test provided.');
        }

        if ($test instanceof TestInterface) {

            $test->setName($name);

            if ($runTestInSeparateProcess) {
                $test->setRunTestInSeparateProcess(true);

                if ($preserveGlobalState !== null) {
                    $test->setPreserveGlobalState($preserveGlobalState);
                }
            }

            if ($backupSettings['backupGlobals'] !== null) {
                $test->setBackupGlobals($backupSettings['backupGlobals']);
            }

            if ($backupSettings['backupStaticAttributes'] !== null) {
                $test->setBackupStaticAttributes(
                    $backupSettings['backupStaticAttributes']
                );
            }
        }

        return $test;
    }

    /**
     * Creates a default TestResult object.
     *
     * @return PHPUnit_Framework_TestResult
     */
    protected function createResult()
    {
        return new PHPUnit_Framework_TestResult();
    }

    /**
     * Returns the name of the suite.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the test groups of the suite.
     *
     * @return array
     *
     * @since Method available since Release 3.2.0
     */
    public function getGroups()
    {
        return array_keys($this->groups);
    }

    public function getGroupDetails()
    {
        return $this->groups;
    }

    /**
     * Set tests groups of the test case
     *
     * @param array $groups
     *
     * @since Method available since Release 4.0.0
     */
    public function setGroupDetails(Map<string, Vector<TestInterface>> $groups): void {
      $this->groups = $groups;
    }


    /**
     * Runs the tests and collects their result in a TestResult.
     *
     * @param PHPUnit_Framework_TestResult $result
     *
     * @return PHPUnit_Framework_TestResult
     */
    public function run(?PHPUnit_Framework_TestResult $result = null) {

        if ($result === null) {
            $result = $this->createResult();
        }

        if (count($this) == 0) {

            return $result;
        }

        $hookMethods = UtilTest::getHookMethods($this->name);

        $result->startTestSuite($this);

        $t = null;

        try {

            $this->setUp();

            if ( $hookMethods->containsKey('beforeClass') ) {
              $beforeClassHooks = $hookMethods->get('beforeClass');

              if ( $beforeClassHooks instanceof Vector ) {
                foreach ($beforeClassHooks as $beforeClassMethod) {
                  if ($this->testCase === true &&
                      class_exists($this->name, false) &&
                      method_exists($this->name, $beforeClassMethod)) {
                      if ($missingRequirements = UtilTest::getMissingRequirements($this->name, $beforeClassMethod)) {
                          $this->markTestSuiteSkipped(implode(PHP_EOL, $missingRequirements));
                      }

                      call_user_func([$this->name, $beforeClassMethod]);
                  }
                }
              }
            }


        } catch (SkippedTestSuiteError $e) {

            $numTests = count($this);

            foreach ( $this->tests as $test ) {
                $result->startTest($test);
                $result->addFailure($test, $e, 0.0);
                $result->endTest($test, 0.0);
            }

            $this->tearDown();
            $result->endTestSuite($this);

            return $result;
        } catch (Throwable $_t) {
            $t = $_t;
        } catch (Exception $_t) {
            $t = $_t;
        }

        if (isset($t)) {
            $numTests = count($this);

            foreach ( $this->tests as $test) {
                $result->startTest($test);
                $result->addError($test, $t, 0.0);
                $result->endTest($test, 0.0);
            }

            $this->tearDown();
            $result->endTestSuite($this);
            return $result;
        }

        // JEO: Quick get the first and last tests off the stack.
        $firstTest = null;
        $lastTest = null;

        foreach ( $this as $test ) {
          if ( $firstTest === null ) {
            $firstTest = $test;
          }
          $lastTest = $test;
        }

        // JEO: Run the doSetupBeforeClass on the first test.
        if ( $firstTest instanceof ZyngaTestCaseBase && method_exists($firstTest, 'doSetUpBeforeClass') ) {
          $firstTest->doSetUpBeforeClass();
        } else if (get_class($firstTest) != 'PHPUnit_Framework_TestSuite') {
          error_log('WARNING - doSetUpBeforeClass NOT defined on your test=' . get_class($firstTest));
        }

        // Prototype async method.
        $testStack = array();
        foreach ($this->tests as $test) {
          $testStack[] = AsyncRunner::runTestAsync($test, $result);
        }

        \HH\Asio\join(AsyncRunner::runTests($testStack));

        /*
        foreach ($this as $test) {

            if ($result->shouldStop()) {
                break;
            }

            if (($test instanceof TestInterface )||
                $test instanceof self) {
                $test->setbeStrictAboutChangesToGlobalState($this->beStrictAboutChangesToGlobalState);
                $test->setBackupGlobals($this->backupGlobals);
                $test->setBackupStaticAttributes($this->backupStaticAttributes);
                $test->setRunTestInSeparateProcess($this->runTestInSeparateProcess);
            }

            $test->run($result);

        }
        */

        // JEO: Run the doTearDownAfterClass
        if ( $lastTest instanceof ZyngaTestCaseBase && method_exists($lastTest, 'doTearDownAfterClass') ) {
          $lastTest->doTearDownAfterClass();
        } else if (get_class($lastTest) != 'PHPUnit_Framework_TestSuite') {
          error_log('WARNING - doTearDownAfterClass NOT defined on your test=' . get_class($lastTest));
        }

        if ( $hookMethods->containsKey('afterClass') ) {
          $afterClassHooks = $hookMethods->get('afterClass');
          if ( $afterClassHooks instanceof Vector ) {
            foreach ($afterClassHooks as $afterClassMethod) {
              if ($this->testCase === true && class_exists($this->name, false) && method_exists($this->name, $afterClassMethod)) {
                call_user_func([$this->name, $afterClassMethod]);
              }
            }
          }
        }

        $this->tearDown();

        $result->endTestSuite($this);

        return $result;
    }

    /**
     * @param bool $runTestInSeparateProcess
     *
     * @throws PHPUnit_Exceptions_Exception
     *
     * @since Method available since Release 3.7.0
     */
    public function setRunTestInSeparateProcess($runTestInSeparateProcess)
    {
        if (is_bool($runTestInSeparateProcess)) {
            $this->runTestInSeparateProcess = $runTestInSeparateProcess;
        } else {
            throw UtilInvalidArgumentHelper::factory(1, 'boolean');
        }
    }

    /**
     * Runs a test.
     *
     * @deprecated
     *
     * @param PHPUnit_Framework_Test       $test
     * @param PHPUnit_Framework_TestResult $result
     */
    public function runTest(PHPUnit_Framework_Test $test, PHPUnit_Framework_TestResult $result) {
      $test->run($result);
    }

    /**
     * Sets the name of the suite.
     *
     * @param  string
     */
    public function setName(string $name): bool {
      $this->name = $name;
      return true;
    }

    /**
     * Returns the test at the given index.
     *
     * @param  int|false
     *
     * @return PHPUnit_Framework_Test
     */
    public function testAt($index)
    {
        if (isset($this->tests[$index])) {
            return $this->tests[$index];
        } else {
            return false;
        }
    }

    /**
     * Returns the tests as an enumeration.
     *
     * @return array
     */
    public function tests()
    {
        return $this->tests;
    }

    /**
     * Set tests of the test suite
     *
     * @param array $tests
     *
     * @since Method available since Release 4.0.0
     */
    public function setTests(Vector<TestInterface> $tests): void {
        $this->tests = $tests;
    }

    /**
     * Mark the test suite as skipped.
     *
     * @param string $message
     *
     * @throws SkippedTestSuiteError
     *
     * @since Method available since Release 3.0.0
     */
    public function markTestSuiteSkipped($message = '')
    {
        throw new SkippedTestSuiteError($message);
    }

    /**
     * @param ReflectionClass  $class
     * @param ReflectionMethod $method
     */
    protected function addTestMethod(ReflectionClass $class, ReflectionMethod $method)
    {
        if (!$this->isTestMethod($method)) {
            return;
        }

        $name = $method->getName();

        if (!$method->isPublic()) {
            $this->addTest(
                new WarningTestCase(
                    sprintf(
                        'Test method "%s" in test class "%s" is not public.',
                        $name,
                        $class->getName()
                    )
                )
            );

            return;
        }

        $test = self::createTest($class, $name);

        if (($test instanceof ZyngaZyngaTestCaseBaseBase || $test instanceof PHPUnit_Framework_ZyngaTestCaseBase) ||
            $test instanceof PHPUnit_Framework_TestSuite_DataProvider) {
            $test->setDependencies(
                UtilTest::getDependencies($class->getName(), $name)
            );
        }

        $this->addTest(
            $test,
            UtilTest::getGroups($class->getName(), $name)
        );
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return bool
     */
    public function isTestMethod(ReflectionMethod $method)
    {
        if (strpos($method->name, 'test') === 0) {
            return true;
        }

        // @scenario on TestCase::testMethod()
        // @test     on TestCase::testMethod()
        $docComment = $method->getDocComment();

        return strpos($docComment, '@test')     !== false ||
               strpos($docComment, '@scenario') !== false;
    }

    /**
     * @param string $message
     *
     * @return WarningTestCase
     */
    protected static function warning($message)
    {
        return new WarningTestCase($message);
    }

    /**
     * @param string $class
     * @param string $methodName
     * @param string $message
     *
     * @return PHPUnit_Framework_SkippedTestCase
     *
     * @since Method available since Release 4.3.0
     */
    protected static function skipTest($class, $methodName, $message) {
        return new PHPUnit_Framework_SkippedTestCase($class, $methodName, $message);
    }

    /**
     * @param string $class
     * @param string $methodName
     * @param string $message
     *
     * @return PHPUnit_Framework_IncompleteTestCase
     *
     * @since Method available since Release 4.3.0
     */
    protected static function incompleteTest($class, $methodName, $message)
    {
        return new PHPUnit_Framework_IncompleteTestCase($class, $methodName, $message);
    }

    /**
     * @param bool $beStrictAboutChangesToGlobalState
     *
     * @since Method available since Release 4.6.0
     */
    public function setbeStrictAboutChangesToGlobalState($beStrictAboutChangesToGlobalState)
    {
        if (is_null($this->beStrictAboutChangesToGlobalState) && is_bool($beStrictAboutChangesToGlobalState)) {
            $this->beStrictAboutChangesToGlobalState = $beStrictAboutChangesToGlobalState;
        }
    }

    /**
     * @param bool $backupGlobals
     *
     * @since Method available since Release 3.3.0
     */
    public function setBackupGlobals($backupGlobals)
    {
        if (is_null($this->backupGlobals) && is_bool($backupGlobals)) {
            $this->backupGlobals = $backupGlobals;
        }
    }

    /**
     * @param bool $backupStaticAttributes
     *
     * @since Method available since Release 3.4.0
     */
    public function setBackupStaticAttributes($backupStaticAttributes)
    {
        if (is_null($this->backupStaticAttributes) &&
            is_bool($backupStaticAttributes)) {
            $this->backupStaticAttributes = $backupStaticAttributes;
        }
    }

    /**
     * Returns an iterator for this test suite.
     *
     * @return RecursiveIteratorIterator
     *
     * @since Method available since Release 3.1.0
     */
    public function getIterator()
    {
        $iterator = new UtilTestSuiteIterator($this);

        if ($this->iteratorFilter !== null) {
            $iterator = $this->iteratorFilter->factory($iterator, $this);
        }

        return $iterator;
    }

    public function injectFilter(PHPUnit_Runner_Filter_Factory $filter)
    {
        $this->iteratorFilter = $filter;
        foreach ($this as $test) {
            if ($test instanceof self) {
                $test->injectFilter($filter);
            }
        }
    }

    /**
     * Template Method that is called before the tests
     * of this test suite are run.
     *
     * @since Method available since Release 3.1.0
     */
    protected function setUp()
    {
    }

    /**
     * Template Method that is called after the tests
     * of this test suite have finished running.
     *
     * @since Method available since Release 3.1.0
     */
    protected function tearDown()
    {
    }
}
