<?hh // strict

namespace SebastianBergmann\PHPUnit\Constriants;

use SebastianBergmann\PHPUnit\Constraints\Base;

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Constraint that asserts that the string it is evaluated for ends with a given
 * suffix.
 *
 * @since Class available since Release 3.4.0
 */
class StringEndsWith extends Base {

  private string $suffix;

  /**
   * @param string $suffix
   */
  public function __construct(string $suffix) {
    parent::__construct();
    $this->suffix = $suffix;
  }

  /**
   * Evaluates the constraint for parameter $other. Returns true if the
   * constraint is met, false otherwise.
   *
   * @param mixed $other Value or object to evaluate.
   *
   * @return bool
   */
  protected function matches(mixed $other): bool {
    return substr($other, 0 - strlen($this->suffix)) == $this->suffix;
  }

  /**
   * Returns a string representation of the constraint.
   *
   * @return string
   */
  public function toString(): string {
    return 'ends with "'.$this->suffix.'"';
  }

  public function setExpected(mixed $expected): bool {
    $this->suffix = strval($expected);
    return true;
  }

  public function resetExpected(): bool {
    $this->suffix = '';
    return true;
  }

}
