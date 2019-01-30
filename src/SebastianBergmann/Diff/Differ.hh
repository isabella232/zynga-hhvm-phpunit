<?hh

/*
 * This file is part of the Diff package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\Diff;

use SebastianBergmann\Diff\LCS\LongestCommonSubsequence;
use SebastianBergmann\Diff\LCS\TimeEfficientImplementation;
use SebastianBergmann\Diff\LCS\MemoryEfficientImplementation;

/**
 * Diff implementation.
 */
class Differ {

  /**
   * @var string
   */
  private $header;

  /**
   * @var bool
   */
  private $showNonDiffLines;

  /**
   * @param string $header
   */
  public function __construct(
    $header = "--- Original\n+++ New\n",
    $showNonDiffLines = true,
  ) {
    $this->header = $header;
    $this->showNonDiffLines = $showNonDiffLines;
  }

  private function _convertParamToArray(mixed $param): array {

    if (is_array($param)) {
      return $param;
    }

    $values = array();

    if (is_string($param)) {
      $values[] = $param;
    } else {
      $values[] = strval($param);
    }

    return $values;

  }

  /**
   * Returns the diff between two arrays or strings as string.
   *
   * @param array|string             $from
   * @param array|string             $to
   * @param LongestCommonSubsequence $lcs
   *
   * @return string
   */
  public function diff(
    mixed $from,
    mixed $to,
    ?LongestCommonSubsequence $lcs = null,
  ) {

    $fromArray = $this->_convertParamToArray($from);
    $toArray = $this->_convertParamToArray($to);

    $buffer = $this->header;
    $diff = $this->diffToArray($fromArray, $toArray, $lcs);

    $inOld = false;
    $i = 0;
    $old = array();

    foreach ($diff as $line) {
      if ($line[1] === 0 /* OLD */) {
        if ($inOld === false) {
          $inOld = $i;
        }
      } else if ($inOld !== false) {
        if (is_int($inOld) && ($i - $inOld) > 5) {
          $old[$inOld] = $i - 1;
        }

        $inOld = false;
      }

      ++$i;

    }

    $start = isset($old[0]) ? $old[0] : 0;
    $end = count($diff);

    if ($tmp = array_search($end, $old)) {
      $end = $tmp;
    }

    $newChunk = true;

    for ($i = $start; $i < $end; $i++) {

      if (isset($old[$i])) {
        $buffer .= "\n";
        $newChunk = true;
        $i = $old[$i];
      }

      if ($newChunk) {
        if ($this->showNonDiffLines === true) {
          $buffer .= "@@ @@\n";
        }
        $newChunk = false;
      }

      if ($diff[$i][1] === 1 /* ADDED */) {
        $buffer .= '+'.$diff[$i][0]."\n";
      } else if ($diff[$i][1] === 2 /* REMOVED */) {
        $buffer .= '-'.$diff[$i][0]."\n";
      } else if ($this->showNonDiffLines === true) {
        $buffer .= ' '.$diff[$i][0]."\n";
      }
    }

    return $buffer;

  }

  /**
   * Returns the diff between two arrays or strings as array.
   *
   * Each array element contains two elements:
   *   - [0] => string $token
   *   - [1] => 2|1|0
   *
   * - 2: REMOVED: $token was removed from $from
   * - 1: ADDED: $token was added to $from
   * - 0: OLD: $token is not changed in $to
   *
   * @param array|string             $from
   * @param array|string             $to
   * @param LongestCommonSubsequence $lcs
   *
   * @return array
   */
  public function diffToArray(
    array $from,
    array $to,
    ?LongestCommonSubsequence $lcs = null,
  ) {

    $fromMatches = array();
    preg_match_all('(\r\n|\r|\n)', $from, $fromMatches);

    $toMatches = array();
    preg_match_all('(\r\n|\r|\n)', $to, $toMatches);

    $start = array();
    $end = array();
    $fromLength = count($from);
    $toLength = count($to);
    $length = min($fromLength, $toLength);

    for ($i = 0; $i < $length; ++$i) {
      if ($from[$i] === $to[$i]) {
        $start[] = $from[$i];
        unset($from[$i], $to[$i]);
      } else {
        break;
      }
    }

    $length -= $i;

    for ($i = 1; $i < $length; ++$i) {
      if ($from[$fromLength - $i] === $to[$toLength - $i]) {
        array_unshift($end, $from[$fromLength - $i]);
        unset($from[$fromLength - $i], $to[$toLength - $i]);
      } else {
        break;
      }
    }

    if ($lcs === null) {
      $lcs = $this->selectLcsImplementation($from, $to);
    }

    $common = $lcs->calculate(array_values($from), array_values($to));
    $diff = array();

    if (isset($fromMatches[0]) &&
        $toMatches[0] &&
        count($fromMatches[0]) === count($toMatches[0]) &&
        $fromMatches[0] !== $toMatches[0]) {
      $diff[] = array('#Warning: Strings contain different line endings!', 0);
    }

    foreach ($start as $token) {
      $diff[] = array($token, 0 /* OLD */);
    }

    reset($from);
    reset($to);

    foreach ($common as $token) {
      while ((($fromToken = reset($from)) !== $token)) {
        $diff[] = array(array_shift($from), 2 /* REMOVED */);
      }

      while ((($toToken = reset($to)) !== $token)) {
        $diff[] = array(array_shift($to), 1 /* ADDED */);
      }

      $diff[] = array($token, 0 /* OLD */);

      array_shift($from);
      array_shift($to);
    }

    while (($token = array_shift($from)) !== null) {
      $diff[] = array($token, 2 /* REMOVED */);
    }

    while (($token = array_shift($to)) !== null) {
      $diff[] = array($token, 1 /* ADDED */);
    }

    foreach ($end as $token) {
      $diff[] = array($token, 0 /* OLD */);
    }

    return $diff;
  }

  /**
   * @param array $from
   * @param array $to
   *
   * @return LongestCommonSubsequence
   */
  private function selectLcsImplementation(
    array $from,
    array $to,
  ): LongestCommonSubsequence {
    // We do not want to use the time-efficient implementation if its memory
    // footprint will probably exceed this value. Note that the footprint
    // calculation is only an estimation for the matrix and the LCS method
    // will typically allocate a bit more memory than this.
    $memoryLimit = 100 * 1024 * 1024;

    if ($this->calculateEstimatedFootprint($from, $to) > $memoryLimit) {
      return new MemoryEfficientImplementation();
    }

    return new TimeEfficientImplementation();
  }

  /**
   * Calculates the estimated memory footprint for the DP-based method.
   *
   * @param array $from
   * @param array $to
   *
   * @return int
   */
  private function calculateEstimatedFootprint(array $from, array $to): int {
    $itemSize = PHP_INT_SIZE == 4 ? 76 : 144;

    return $itemSize * pow(min(count($from), count($to)), 2);
  }

}
