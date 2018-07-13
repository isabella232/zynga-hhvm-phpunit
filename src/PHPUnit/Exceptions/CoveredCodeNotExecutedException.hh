<?hh // strict

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Exceptions;

use PHPUnit\Exceptions\AssertionFailedError;
use PHPUnit\Exceptions\Error\RiskyTestError;

/**
 * Extension to AssertionFailedError to mark the special
 * case of a test that does not execute the code it wants to cover.
 *
 * @since Class available since Release 5.2.0
 */
class CoveredCodeNotExecutedException extends RiskyTestError {
}
