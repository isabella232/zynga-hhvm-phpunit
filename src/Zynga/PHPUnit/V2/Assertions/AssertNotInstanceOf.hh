<?hh // strict

namespace Zynga\PHPUnit\V2\Assertions;

use Zynga\PHPUnit\V2\Assertions;
use Zynga\PHPUnit\V2\Constraints\IsInstanceOfConstraint;
use Zynga\PHPUnit\V2\Constraints\NotConstraint;
use Zynga\PHPUnit\V2\Exceptions\InvalidArgumentExceptionFactory;

class AssertNotInstanceOf {

  /**
   * Asserts that a variable is not of a given type.
   *
   * @param string $expected
   * @param mixed  $actual
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function evaluate(
    Assertions $assertions,
    string $expected,
    mixed $actual,
    string $message = '',
  ): bool {

    if (!(is_string($expected) &&
          (class_exists($expected) || interface_exists($expected)))) {
      throw InvalidArgumentExceptionFactory::factory(
        1,
        'class or interface name',
      );
    }

    $isInstanceOf = new IsInstanceOfConstraint();
    $isInstanceOf->setExpected($expected);

    $constraint = new NotConstraint();
    $constraint->setExpected($isInstanceOf);

    return $assertions->assertThat($actual, $constraint, $message);

  }

}
