<?hh // strict

namespace SebastianBergmann\TokenStream\Tokens;

use SebastianBergmann\TokenStream\TokenOperator;

class PHP_Token_Minus_Equal extends TokenOperator {

  public function getShortTokenName(): string {
    return 'Minus_Equal';
  }

}
