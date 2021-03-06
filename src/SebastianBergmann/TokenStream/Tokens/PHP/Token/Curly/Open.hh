<?hh // strict

namespace SebastianBergmann\TokenStream\Tokens;

use SebastianBergmann\TokenStream\Token;
use SebastianBergmann\TokenStream\Token\Types;

class PHP_Token_Curly_Open extends Token {

  public function getTokenType(): string {
    return Types::T_DEFAULT;
  }

  public function getShortTokenName(): string {
    return 'Curly_Open';
  }

}
