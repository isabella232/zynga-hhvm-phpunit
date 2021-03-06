<?hh // strict

namespace Zynga\PHPUnit\V2\Assertions;

use Zynga\PHPUnit\V2\Assertions;
use Zynga\PHPUnit\V2\Constraints\CountConstraint;
use Zynga\PHPUnit\V2\Exceptions\InvalidArgumentExceptionFactory;
use \Countable;
use \Traversable;

class AssertCount {

  /**
   * Asserts the number of elements of an array, Countable or Traversable.
   *
   * @param int    $expectedCount
   * @param mixed  $haystack
   * @param string $message
   */
  final public static function evaluate(
    Assertions $assertions,
    int $expectedCount,
    mixed $haystack,
    string $message = '',
  ): bool {

    if (!is_int($expectedCount)) {
      throw InvalidArgumentExceptionFactory::factory(1, 'integer');
    }

    if (!$haystack instanceof Countable &&
        !$haystack instanceof Traversable &&
        !is_array($haystack)) {
      throw InvalidArgumentExceptionFactory::factory(
        2,
        'countable or traversable',
      );
    }

    $constraint = new CountConstraint();
    $constraint->setExpected($expectedCount);

    return $assertions->assertThat($haystack, $constraint, $message);

  }

}
