<?hh // strict

namespace SebastianBergmann\TokenStream\Tokens;

use SebastianBergmann\TokenStream\TokenInterface;
use SebastianBergmann\TokenStream\TokenWithScope;
use SebastianBergmann\TokenStream\Token\Types;
use SebastianBergmann\TokenStream\Tokens\PHP_Token_While;
use SebastianBergmann\TokenStream\Tokens\PHP_Token_Semicolon;

class PHP_Token_Do extends TokenWithScope {
  private int $do_endTokenId = -1;
  private bool $do_didEndTokenId = false;

  public function getTokenType(): string {
    return Types::T_KEYWORD;
  }

  public function getShortTokenName(): string {
    return 'Do';
  }

   public function getEndTokenId(): int {

    if ( $this->do_didEndTokenId === true ) {
      return $this->do_endTokenId;
    }

    $this->do_didEndTokenId = true;

    // --
    // This use case is a litle bizaro since the block is
    // inverted from the others.
    // --
    // do { ... something ... } while (... conditions ); 
    // --
    $startAt = parent::getEndTokenId();

    $tokens = $this->tokenStream()->tokens();

    $tokenCount = $tokens->count();

    $foundWhile = false;
    for ( $i = $startAt; $i < $tokenCount; $i++ ) {
      $token = $tokens->get($i);

      if ( ! $token instanceof TokenInterface ) {
        break;
      }

      if ( $foundWhile == false && $token instanceof PHP_Token_While ) {
        $token->setIsDoWhile(true);
        $this->do_endTokenId = $token->getEndTokenId();
        $foundWhile = true;
      }

      /*
      if ( $token instanceof PHP_Token_Semicolon ) {
        $this->do_endTokenId = $token->getId();
        break;
      }
      */

    }

    return $this->do_endTokenId;

  }

}
