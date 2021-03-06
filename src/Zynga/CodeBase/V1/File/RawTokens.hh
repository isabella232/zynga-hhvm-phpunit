<?hh // strict

namespace Zynga\CodeBase\V1\File;

use Zynga\CodeBase\V1\File;
use Zynga\CodeBase\V1\File\RawToken;
use SebastianBergmann\TokenStream\Token\CustomTokens;

class RawTokens {
  private File $_parent;
  private bool $_isLoaded;
  private Vector<RawToken> $_tokens;

  public function __construct(File $parent) {
    $this->_parent = $parent;
    $this->_isLoaded = false;
    $this->_tokens = Vector {};
  }

  public function get(): Vector<RawToken> {
    $this->load();
    return $this->_tokens;
  }

  public function load(): bool {

    if ($this->_isLoaded === true) {
      return true;
    }

    $source = $this->_parent->source()->get();

    $dirtyTokens = token_get_all($source);

    if (!is_array($dirtyTokens)) {
      return false;
    }

    $rawToken = new RawToken();

    foreach ($dirtyTokens as $dirtyToken) {

      $token = null;

      if (is_array($dirtyToken)) {
        $token = clone $rawToken;
        $token->setAll($dirtyToken[0], $dirtyToken[1], $dirtyToken[2]);
      } else {

        $customTokenId = CustomTokens::getTokenIdFromString($dirtyToken);

        $token = clone $rawToken;

        if ($customTokenId != -1) {
          $token->setAll($customTokenId, $dirtyToken, -1);
        } else {
          //error_log('dirtyTokenFound='.$dirtyToken);
          $token->setAll(-1, $dirtyToken, -1);
        }

      }

      if ($token instanceof RawToken) {
        $this->_tokens->add($token);
      } else {
        error_log(
          'WARNING - Failed to parse raw_token='.json_encode($dirtyToken),
        );
      }

    }

    $this->_isLoaded = true;
    return true;
  }

}
