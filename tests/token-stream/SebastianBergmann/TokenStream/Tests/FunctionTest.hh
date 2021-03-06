<?hh // strict

namespace SebastianBergmann\TokenStream\Tests;
/*
 * This file is part of php-token-stream.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Zynga\Framework\Environment\CodePath\V1\CodePath;
use Zynga\Framework\Testing\TestCase\V2\Base as TestCase;
use SebastianBergmann\TokenStream\Token\Stream;
use SebastianBergmann\TokenStream\Token\Stream\CachingFactory;
use SebastianBergmann\TokenStream\Tokens\PHP_Token_Function;
use Zynga\CodeBase\V1\FileFactory;

class FunctionTest extends TestCase {

  protected function getFilesDirectory(): string {
    return
      CodePath::getRoot().
      DIRECTORY_SEPARATOR.
      'vendor'.
      DIRECTORY_SEPARATOR.
      'zynga'.
      DIRECTORY_SEPARATOR.
      'phpunit'.
      DIRECTORY_SEPARATOR.
      'tests'.
      DIRECTORY_SEPARATOR.
      'token-stream'.
      DIRECTORY_SEPARATOR.
      '_fixture'.
      DIRECTORY_SEPARATOR;
  }

  protected function getTestFunctions(): Vector<PHP_Token_Function> {
    $filename = $this->getFilesDirectory().'source.php';
    $codeFile = FileFactory::get($filename);
    $tokenStream = $codeFile->stream();
    $functions = Vector {};

    $tokens = $tokenStream->tokens();

    foreach ($tokens as $token) {
      if ($token instanceof PHP_Token_Function) {
        $functions->add($token);
      }
    }
    return $functions;
  }

  public function testGetArguments(): void {

    $functions = $this->getTestFunctions();

    $this->assertEquals([], $functions[0]->getArguments()->toArray());

    $this->assertEquals(
      ['$baz' => 'Baz'],
      $functions[1]->getArguments()->toArray(),
    );

    $this->assertEquals(
      ['$foobar' => 'Foobar'],
      $functions[2]->getArguments()->toArray(),
    );

    $this->assertEquals(
      ['$barfoo' => 'Barfoo'],
      $functions[3]->getArguments()->toArray(),
    );

    $this->assertEquals([], $functions[4]->getArguments()->toArray());

    $this->assertEquals(
      ['$x' => null, '$y' => null],
      $functions[5]->getArguments()->toArray(),
    );
  }

  public function testGetName(): void {
    $functions = $this->getTestFunctions();
    $this->assertEquals('foo', $functions[0]->getName());
    $this->assertEquals('bar', $functions[1]->getName());
    $this->assertEquals('foobar', $functions[2]->getName());
    $this->assertEquals('barfoo', $functions[3]->getName());
    $this->assertEquals('baz', $functions[4]->getName());
  }

  public function testGetLine(): void {
    $functions = $this->getTestFunctions();
    $this->assertEquals(5, $functions[0]->getLine());
    $this->assertEquals(10, $functions[1]->getLine());
    $this->assertEquals(17, $functions[2]->getLine());
    $this->assertEquals(21, $functions[3]->getLine());
    $this->assertEquals(29, $functions[4]->getLine());
  }

  public function testGetEndLine(): void {
    $functions = $this->getTestFunctions();
    $this->assertEquals(5, $functions[0]->getEndLine());
    $this->assertEquals(12, $functions[1]->getEndLine());
    $this->assertEquals(19, $functions[2]->getEndLine());
    $this->assertEquals(23, $functions[3]->getEndLine());
    $this->assertEquals(31, $functions[4]->getEndLine());
  }

  public function testGetDocblock(): void {

    $functions = $this->getTestFunctions();
    $this->assertEquals('', $functions[0]->getDocblock());

    $this->assertEquals(
      "/**\n     * @param Baz \$baz\n     */",
      $functions[1]->getDocblock(),
    );

    $this->assertEquals(
      "/**\n     * @param Foobar \$foobar\n     */",
      $functions[2]->getDocblock(),
    );

    $this->assertEquals('', $functions[3]->getDocblock());
    // JEO: Another key difference in zynga version vs phpunit stock. We are more permissive for the docblock.
    $this->assertEquals(
      "/**\n     * This docblock does not belong to the baz function, except in the zynga version where we are more permissive in whitespace parsing.\n     */",
      $functions[4]->getDocblock(),
    );
  }

  public function testSignature(): void {

    $filename = $this->getFilesDirectory().'source5.php';
    $codeFile = FileFactory::get($filename);
    $f = $codeFile->functions()->getAll();
    $c = $codeFile->classes()->getAll();
    $i = $codeFile->interfaces()->getAll();

    $this->assertEquals(
      'foo($a, array $b, array $c = array())',
      $f['foo']->signature,
    );

    $this->assertEquals(
      'm($a, array $b, array $c = array())',
      $c['c']->methods['m']->signature,
    );

    $this->assertEquals(
      'm($a, array $b, array $c = array())',
      $c['a']->methods['m']->signature,
    );

    //file_put_contents('/tmp/jeo-debug.txt', var_export($i['i'], true));

    $this->assertEquals(
      'm($a, array $b, array $c = array())',
      $i['i']->methods['m']->signature,
    );
  }

  public function testCcn(): void {

    $filename = $this->getFilesDirectory().'source6.php';
    $codeFile = FileFactory::get($filename);

    $c = $codeFile->classes()->getAll();

    $this->assertEquals(1, $c['testCCN']->methods['noBody']->getCcn());
    $this->assertEquals(2, $c['testCCN']->methods['hasIf']->getCcn());
    $this->assertEquals(8, $c['testCCN']->methods['ifOnIf']->getCcn());

  }

}
