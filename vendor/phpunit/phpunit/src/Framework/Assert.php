<?php

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Zynga\Framework\ReflectionCache\V1\ReflectionClasses;

use SebastianBergmann\PHPUnit\Assertions;
use SebastianBergmann\PHPUnit\AssertionsFactory;
use SebastianBergmann\PHPUnit\Exceptions\AssertionFailedException;
use SebastianBergmann\PHPUnit\Exceptions\InvalidArgumentException;

// --
// We are in the process of porting all of these assertion bits into hacklang
// strict code. You will see a common pattern of:
//
//   $assertions = AssertionsFactory::factory();
//   return $assertions->assertArrayHasKey($key, $array, $message);
//
// When we complete all the porting, we will be attempting removal of the base
//  level class of PHPUnit_Framework_Assert
//
// --
/**
 * A set of assert methods.
 *
 * @since Class available since Release 2.0.0
 */
abstract class PHPUnit_Framework_Assert extends Assertions {

  /**
   * Asserts that two variables have the same type and value.
   * Used on objects, it asserts that two variables reference
   * the same object.
   *
   * @param mixed  $expected
   * @param mixed  $actual
   * @param string $message
   */
  public static function assertSame($expected, $actual, $message = '') {
    if (is_bool($expected) && is_bool($actual)) {
      $assertions = AssertionsFactory::factory();
      $assertions->assertEquals($expected, $actual, $message);
    } else {
      $constraint = new PHPUnit_Framework_Constraint_IsIdentical($expected);

      static::legacyAssertThat($actual, $constraint, $message);
    }
  }

  /**
   * Asserts that a variable and an attribute of an object have the same type
   * and value.
   *
   * @param mixed         $expected
   * @param string        $actualAttributeName
   * @param string|object $actualClassOrObject
   * @param string        $message
   */
  public static function assertAttributeSame(
    $expected,
    $actualAttributeName,
    $actualClassOrObject,
    $message = ''
  ) {
    static::assertSame(
      $expected,
      static::legacyReadAttribute($actualClassOrObject, $actualAttributeName),
      $message
    );
  }

  /**
   * Asserts that two variables do not have the same type and value.
   * Used on objects, it asserts that two variables do not reference
   * the same object.
   *
   * @param mixed  $expected
   * @param mixed  $actual
   * @param string $message
   */
  public static function assertNotSame($expected, $actual, $message = '') {
    if (is_bool($expected) && is_bool($actual)) {
      $assertions = AssertionsFactory::factory();
      $assertions->assertNotEquals($expected, $actual, $message);
    } else {
      $constraint = new PHPUnit_Framework_Constraint_Not(
        new PHPUnit_Framework_Constraint_IsIdentical($expected)
      );

      static::legacyAssertThat($actual, $constraint, $message);
    }
  }

  /**
   * Asserts that a variable and an attribute of an object do not have the
   * same type and value.
   *
   * @param mixed         $expected
   * @param string        $actualAttributeName
   * @param string|object $actualClassOrObject
   * @param string        $message
   */
  public static function assertAttributeNotSame(
    $expected,
    $actualAttributeName,
    $actualClassOrObject,
    $message = ''
  ) {
    static::assertNotSame(
      $expected,
      static::legacyReadAttribute($actualClassOrObject, $actualAttributeName),
      $message
    );
  }

  /**
   * Asserts that a variable is of a given type.
   *
   * @param string $expected
   * @param mixed  $actual
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertInstanceOf($expected, $actual, $message = '') {
    if (!(is_string($expected) &&
          (class_exists($expected) || interface_exists($expected)))) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        1,
        'class or interface name'
      );
    }

    $constraint = new PHPUnit_Framework_Constraint_IsInstanceOf($expected);

    static::legacyAssertThat($actual, $constraint, $message);
  }

  /**
   * Asserts that an attribute is of a given type.
   *
   * @param string        $expected
   * @param string        $attributeName
   * @param string|object $classOrObject
   * @param string        $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertAttributeInstanceOf(
    $expected,
    $attributeName,
    $classOrObject,
    $message = ''
  ) {
    static::assertInstanceOf(
      $expected,
      static::legacyReadAttribute($classOrObject, $attributeName),
      $message
    );
  }

  /**
   * Asserts that a variable is not of a given type.
   *
   * @param string $expected
   * @param mixed  $actual
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertNotInstanceOf(
    $expected,
    $actual,
    $message = ''
  ) {
    if (!(is_string($expected) &&
          (class_exists($expected) || interface_exists($expected)))) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        1,
        'class or interface name'
      );
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_IsInstanceOf($expected)
    );

    static::legacyAssertThat($actual, $constraint, $message);
  }

  /**
   * Asserts that an attribute is of a given type.
   *
   * @param string        $expected
   * @param string        $attributeName
   * @param string|object $classOrObject
   * @param string        $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertAttributeNotInstanceOf(
    $expected,
    $attributeName,
    $classOrObject,
    $message = ''
  ) {
    static::assertNotInstanceOf(
      $expected,
      static::legacyReadAttribute($classOrObject, $attributeName),
      $message
    );
  }

  /**
   * Asserts that a variable is of a given type.
   *
   * @param string $expected
   * @param mixed  $actual
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertInternalType(
    $expected,
    $actual,
    $message = ''
  ) {
    if (!is_string($expected)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_IsType($expected);

    static::legacyAssertThat($actual, $constraint, $message);
  }

  /**
   * Asserts that an attribute is of a given type.
   *
   * @param string        $expected
   * @param string        $attributeName
   * @param string|object $classOrObject
   * @param string        $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertAttributeInternalType(
    $expected,
    $attributeName,
    $classOrObject,
    $message = ''
  ) {
    static::assertInternalType(
      $expected,
      static::legacyReadAttribute($classOrObject, $attributeName),
      $message
    );
  }

  /**
   * Asserts that a variable is not of a given type.
   *
   * @param string $expected
   * @param mixed  $actual
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertNotInternalType(
    $expected,
    $actual,
    $message = ''
  ) {
    if (!is_string($expected)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_IsType($expected)
    );

    static::legacyAssertThat($actual, $constraint, $message);
  }

  /**
   * Asserts that an attribute is of a given type.
   *
   * @param string        $expected
   * @param string        $attributeName
   * @param string|object $classOrObject
   * @param string        $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertAttributeNotInternalType(
    $expected,
    $attributeName,
    $classOrObject,
    $message = ''
  ) {
    static::assertNotInternalType(
      $expected,
      static::legacyReadAttribute($classOrObject, $attributeName),
      $message
    );
  }

  /**
   * Asserts that a string matches a given regular expression.
   *
   * @param string $pattern
   * @param string $string
   * @param string $message
   */
  public static function assertRegExp($pattern, $string, $message = '') {
    if (!is_string($pattern)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_PCREMatch($pattern);

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string does not match a given regular expression.
   *
   * @param string $pattern
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 2.1.0
   */
  public static function assertNotRegExp($pattern, $string, $message = '') {
    if (!is_string($pattern)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_PCREMatch($pattern)
    );

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
   * is the same.
   *
   * @param array|Countable|Traversable $expected
   * @param array|Countable|Traversable $actual
   * @param string                      $message
   */
  public static function assertSameSize($expected, $actual, $message = '') {
    if (!$expected instanceof Countable &&
        !$expected instanceof Traversable &&
        !is_array($expected)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        1,
        'countable or traversable'
      );
    }

    if (!$actual instanceof Countable &&
        !$actual instanceof Traversable &&
        !is_array($actual)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        2,
        'countable or traversable'
      );
    }

    static::legacyAssertThat(
      $actual,
      new PHPUnit_Framework_Constraint_SameSize($expected),
      $message
    );
  }

  /**
   * Assert that the size of two arrays (or `Countable` or `Traversable` objects)
   * is not the same.
   *
   * @param array|Countable|Traversable $expected
   * @param array|Countable|Traversable $actual
   * @param string                      $message
   */
  public static function assertNotSameSize($expected, $actual, $message = '') {
    if (!$expected instanceof Countable &&
        !$expected instanceof Traversable &&
        !is_array($expected)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        1,
        'countable or traversable'
      );
    }

    if (!$actual instanceof Countable &&
        !$actual instanceof Traversable &&
        !is_array($actual)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        2,
        'countable or traversable'
      );
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_SameSize($expected)
    );

    static::legacyAssertThat($actual, $constraint, $message);
  }



  /**
   * Asserts that a string does not match a given format string.
   *
   * @param string $format
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertStringNotMatchesFormat(
    $format,
    $string,
    $message = ''
  ) {
    if (!is_string($format)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_StringMatches($format)
    );

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string matches a given format file.
   *
   * @param string $formatFile
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertStringMatchesFormatFile(
    $formatFile,
    $string,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();

    $assertions->assertFileExists($formatFile, $message);

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_StringMatches(
      file_get_contents($formatFile)
    );

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string does not match a given format string.
   *
   * @param string $formatFile
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.5.0
   */
  public static function assertStringNotMatchesFormatFile(
    $formatFile,
    $string,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();

    $assertions->assertFileExists($formatFile, $message);

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_StringMatches(
        file_get_contents($formatFile)
      )
    );

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string starts with a given prefix.
   *
   * @param string $prefix
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.4.0
   */
  public static function assertStringStartsWith(
    $prefix,
    $string,
    $message = ''
  ) {
    if (!is_string($prefix)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_StringStartsWith($prefix);

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string starts not with a given prefix.
   *
   * @param string $prefix
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.4.0
   */
  public static function assertStringStartsNotWith(
    $prefix,
    $string,
    $message = ''
  ) {
    if (!is_string($prefix)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_StringStartsWith($prefix)
    );

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string ends with a given suffix.
   *
   * @param string $suffix
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.4.0
   */
  public static function assertStringEndsWith(
    $suffix,
    $string,
    $message = ''
  ) {
    if (!is_string($suffix)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_StringEndsWith($suffix);

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that a string ends not with a given suffix.
   *
   * @param string $suffix
   * @param string $string
   * @param string $message
   *
   * @since Method available since Release 3.4.0
   */
  public static function assertStringEndsNotWith(
    $suffix,
    $string,
    $message = ''
  ) {
    if (!is_string($suffix)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!is_string($string)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    $constraint = new PHPUnit_Framework_Constraint_Not(
      new PHPUnit_Framework_Constraint_StringEndsWith($suffix)
    );

    static::legacyAssertThat($string, $constraint, $message);
  }

  /**
   * Asserts that two XML files are equal.
   *
   * @param string $expectedFile
   * @param string $actualFile
   * @param string $message
   *
   * @since Method available since Release 3.1.0
   */
  public static function assertXmlFileEqualsXmlFile(
    $expectedFile,
    $actualFile,
    $message = ''
  ) {

    $expected = PHPUnit_Util_XML::loadFile($expectedFile);
    $actual = PHPUnit_Util_XML::loadFile($actualFile);

    $assertions = AssertionsFactory::factory();
    $assertions->assertEquals($expected, $actual, $message);

  }

  /**
   * Asserts that two XML files are not equal.
   *
   * @param string $expectedFile
   * @param string $actualFile
   * @param string $message
   *
   * @since Method available since Release 3.1.0
   */
  public static function assertXmlFileNotEqualsXmlFile(
    $expectedFile,
    $actualFile,
    $message = ''
  ) {

    $expected = PHPUnit_Util_XML::loadFile($expectedFile);
    $actual = PHPUnit_Util_XML::loadFile($actualFile);

    $assertions = AssertionsFactory::factory();
    $assertions->assertNotEquals($expected, $actual, $message);

  }

  /**
   * Asserts that two XML documents are equal.
   *
   * @param string $expectedFile
   * @param string $actualXml
   * @param string $message
   *
   * @since Method available since Release 3.3.0
   */
  public static function assertXmlStringEqualsXmlFile(
    $expectedFile,
    $actualXml,
    $message = ''
  ) {
    $expected = PHPUnit_Util_XML::loadFile($expectedFile);
    $actual = PHPUnit_Util_XML::load($actualXml);

    $assertions = AssertionsFactory::factory();
    $assertions->assertEquals($expected, $actual, $message);

  }

  /**
   * Asserts that two XML documents are not equal.
   *
   * @param string $expectedFile
   * @param string $actualXml
   * @param string $message
   *
   * @since Method available since Release 3.3.0
   */
  public static function assertXmlStringNotEqualsXmlFile(
    $expectedFile,
    $actualXml,
    $message = ''
  ) {
    $expected = PHPUnit_Util_XML::loadFile($expectedFile);
    $actual = PHPUnit_Util_XML::load($actualXml);

    $assertions = AssertionsFactory::factory();
    $assertions->assertNotEquals($expected, $actual, $message);

  }

  /**
   * Asserts that two XML documents are equal.
   *
   * @param string $expectedXml
   * @param string $actualXml
   * @param string $message
   *
   * @since Method available since Release 3.1.0
   */
  public static function assertXmlStringEqualsXmlString(
    $expectedXml,
    $actualXml,
    $message = ''
  ) {

    $expected = PHPUnit_Util_XML::load($expectedXml);
    $actual = PHPUnit_Util_XML::load($actualXml);

    $assertions = AssertionsFactory::factory();

    return $assertions->assertEquals($expected, $actual, $message);

  }

  /**
   * Asserts that two XML documents are not equal.
   *
   * @param string $expectedXml
   * @param string $actualXml
   * @param string $message
   *
   * @since Method available since Release 3.1.0
   */
  public static function assertXmlStringNotEqualsXmlString(
    $expectedXml,
    $actualXml,
    $message = ''
  ) {
    $expected = PHPUnit_Util_XML::load($expectedXml);
    $actual = PHPUnit_Util_XML::load($actualXml);

    $assertions = AssertionsFactory::factory();
    $assertions->assertNotEquals($expected, $actual, $message);

  }

  /**
   * Asserts that a hierarchy of DOMElements matches.
   *
   * @param DOMElement $expectedElement
   * @param DOMElement $actualElement
   * @param bool       $checkAttributes
   * @param string     $message
   *
   * @since Method available since Release 3.3.0
   */
  public static function assertEqualXMLStructure(
    DOMElement $expectedElement,
    DOMElement $actualElement,
    $checkAttributes = false,
    $message = ''
  ) {
    $tmp = new DOMDocument();
    $expectedElement = $tmp->importNode($expectedElement, true);

    $tmp = new DOMDocument();
    $actualElement = $tmp->importNode($actualElement, true);

    unset($tmp);

    $assertions = AssertionsFactory::factory();

    $assertions->assertEquals(
      $expectedElement->tagName,
      $actualElement->tagName,
      $message
    );

    if ($checkAttributes) {
      $assertions->assertEquals(
        $expectedElement->attributes->length,
        $actualElement->attributes->length,
        sprintf(
          '%s%sNumber of attributes on node "%s" does not match',
          $message,
          !empty($message) ? "\n" : '',
          $expectedElement->tagName
        )
      );

      for ($i = 0; $i < $expectedElement->attributes->length; $i++) {
        $expectedAttribute = $expectedElement->attributes->item($i);
        $actualAttribute =
          $actualElement->attributes->getNamedItem($expectedAttribute->name);

        if (!$actualAttribute) {
          static::legacyFail(
            sprintf(
              '%s%sCould not find attribute "%s" on node "%s"',
              $message,
              !empty($message) ? "\n" : '',
              $expectedAttribute->name,
              $expectedElement->tagName
            )
          );
        }
      }
    }

    PHPUnit_Util_XML::removeCharacterDataNodes($expectedElement);
    PHPUnit_Util_XML::removeCharacterDataNodes($actualElement);

    $assertions->assertEquals(
      $expectedElement->childNodes->length,
      $actualElement->childNodes->length,
      sprintf(
        '%s%sNumber of child nodes of "%s" differs',
        $message,
        !empty($message) ? "\n" : '',
        $expectedElement->tagName
      )
    );

    for ($i = 0; $i < $expectedElement->childNodes->length; $i++) {
      static::assertEqualXMLStructure(
        $expectedElement->childNodes->item($i),
        $actualElement->childNodes->item($i),
        $checkAttributes,
        $message
      );
    }
  }

  /**
   * Evaluates a PHPUnit_Framework_Constraint matcher object.
   *
   * @param mixed                        $value
   * @param PHPUnit_Framework_Constraint $constraint
   * @param string                       $message
   *
   * @since Method available since Release 3.0.0
   */
  public static function legacyAssertThat(
    $value,
    PHPUnit_Framework_Constraint $constraint,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();
    $assertions->counter()->add(count($constraint));

    $constraint->evaluate($value, $message);
  }

  /**
   * Asserts that a string is a valid JSON string.
   *
   * @param string $actualJson
   * @param string $message
   *
   * @since Method available since Release 3.7.20
   */
  public static function assertJson($actualJson, $message = '') {
    if (!is_string($actualJson)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    static::legacyAssertThat($actualJson, static::isJson(), $message);
  }

  /**
   * Asserts that two given JSON encoded objects or arrays are equal.
   *
   * @param string $expectedJson
   * @param string $actualJson
   * @param string $message
   */
  public static function assertJsonStringEqualsJsonString(
    $expectedJson,
    $actualJson,
    $message = ''
  ) {
    static::assertJson($expectedJson, $message);
    static::assertJson($actualJson, $message);

    $expected = json_decode($expectedJson);
    $actual = json_decode($actualJson);

    $assertions = AssertionsFactory::factory();

    $assertions->assertEquals($expected, $actual, $message);

  }

  /**
   * Asserts that two given JSON encoded objects or arrays are not equal.
   *
   * @param string $expectedJson
   * @param string $actualJson
   * @param string $message
   */
  public static function assertJsonStringNotEqualsJsonString(
    $expectedJson,
    $actualJson,
    $message = ''
  ) {
    static::assertJson($expectedJson, $message);
    static::assertJson($actualJson, $message);

    $expected = json_decode($expectedJson);
    $actual = json_decode($actualJson);

    $assertions = AssertionsFactory::factory();
    $assertions->assertNotEquals($expected, $actual, $message);

  }

  /**
   * Asserts that the generated JSON encoded object and the content of the given file are equal.
   *
   * @param string $expectedFile
   * @param string $actualJson
   * @param string $message
   */
  public static function assertJsonStringEqualsJsonFile(
    $expectedFile,
    $actualJson,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();

    $assertions->assertFileExists($expectedFile, $message);
    $expectedJson = file_get_contents($expectedFile);

    static::assertJson($expectedJson, $message);
    static::assertJson($actualJson, $message);

    // call constraint
    $constraint = new PHPUnit_Framework_Constraint_JsonMatches($expectedJson);

    static::legacyAssertThat($actualJson, $constraint, $message);
  }

  /**
   * Asserts that the generated JSON encoded object and the content of the given file are not equal.
   *
   * @param string $expectedFile
   * @param string $actualJson
   * @param string $message
   */
  public static function assertJsonStringNotEqualsJsonFile(
    $expectedFile,
    $actualJson,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();

    $assertions->assertFileExists($expectedFile, $message);

    $expectedJson = file_get_contents($expectedFile);

    static::assertJson($expectedJson, $message);
    static::assertJson($actualJson, $message);

    // call constraint
    $constraint = new PHPUnit_Framework_Constraint_JsonMatches($expectedJson);

    static::legacyAssertThat(
      $actualJson,
      new PHPUnit_Framework_Constraint_Not($constraint),
      $message
    );
  }

  /**
   * Asserts that two JSON files are equal.
   *
   * @param string $expectedFile
   * @param string $actualFile
   * @param string $message
   */
  public static function assertJsonFileEqualsJsonFile(
    $expectedFile,
    $actualFile,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();

    $assertions->assertFileExists($expectedFile, $message);
    $assertions->assertFileExists($actualFile, $message);

    $actualJson = file_get_contents($actualFile);
    $expectedJson = file_get_contents($expectedFile);

    static::assertJson($expectedJson, $message);
    static::assertJson($actualJson, $message);

    // call constraint
    $constraintExpected =
      new PHPUnit_Framework_Constraint_JsonMatches($expectedJson);

    $constraintActual =
      new PHPUnit_Framework_Constraint_JsonMatches($actualJson);

    static::legacyAssertThat($expectedJson, $constraintActual, $message);
    static::legacyAssertThat($actualJson, $constraintExpected, $message);
  }

  /**
   * Asserts that two JSON files are not equal.
   *
   * @param string $expectedFile
   * @param string $actualFile
   * @param string $message
   */
  public static function assertJsonFileNotEqualsJsonFile(
    $expectedFile,
    $actualFile,
    $message = ''
  ) {

    $assertions = AssertionsFactory::factory();

    $assertions->assertFileExists($expectedFile, $message);
    $assertions->assertFileExists($actualFile, $message);

    $actualJson = file_get_contents($actualFile);
    $expectedJson = file_get_contents($expectedFile);

    static::assertJson($expectedJson, $message);
    static::assertJson($actualJson, $message);

    // call constraint
    $constraintExpected =
      new PHPUnit_Framework_Constraint_JsonMatches($expectedJson);

    $constraintActual =
      new PHPUnit_Framework_Constraint_JsonMatches($actualJson);

    static::legacyAssertThat(
      $expectedJson,
      new PHPUnit_Framework_Constraint_Not($constraintActual),
      $message
    );
    static::legacyAssertThat(
      $actualJson,
      new PHPUnit_Framework_Constraint_Not($constraintExpected),
      $message
    );
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_And matcher object.
   *
   * @return PHPUnit_Framework_Constraint_And
   *
   * @since Method available since Release 3.0.0
   */
  public static function logicalAnd() {
    $constraints = func_get_args();

    $constraint = new PHPUnit_Framework_Constraint_And();
    $constraint->setConstraints($constraints);

    return $constraint;
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Or matcher object.
   *
   * @return PHPUnit_Framework_Constraint_Or
   *
   * @since Method available since Release 3.0.0
   */
  public static function logicalOr() {
    $constraints = func_get_args();

    $constraint = new PHPUnit_Framework_Constraint_Or();
    $constraint->setConstraints($constraints);

    return $constraint;
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Not matcher object.
   *
   * @param PHPUnit_Framework_Constraint $constraint
   *
   * @return PHPUnit_Framework_Constraint_Not
   *
   * @since Method available since Release 3.0.0
   */
  public static function logicalNot(PHPUnit_Framework_Constraint $constraint) {
    return new PHPUnit_Framework_Constraint_Not($constraint);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Xor matcher object.
   *
   * @return PHPUnit_Framework_Constraint_Xor
   *
   * @since Method available since Release 3.0.0
   */
  public static function logicalXor() {
    $constraints = func_get_args();

    $constraint = new PHPUnit_Framework_Constraint_Xor();
    $constraint->setConstraints($constraints);

    return $constraint;
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsAnything matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsAnything
   *
   * @since Method available since Release 3.0.0
   */
  public static function anything() {
    return new PHPUnit_Framework_Constraint_IsAnything();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsTrue matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsTrue
   *
   * @since Method available since Release 3.3.0
   */
  public static function isTrue() {
    return new PHPUnit_Framework_Constraint_IsTrue();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Callback matcher object.
   *
   * @param callable $callback
   *
   * @return PHPUnit_Framework_Constraint_Callback
   */
  public static function callback($callback) {
    return new PHPUnit_Framework_Constraint_Callback($callback);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsFalse matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsFalse
   *
   * @since Method available since Release 3.3.0
   */
  public static function isFalse() {
    return new PHPUnit_Framework_Constraint_IsFalse();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsJson matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsJson
   *
   * @since Method available since Release 3.7.20
   */
  public static function isJson() {
    return new PHPUnit_Framework_Constraint_IsJson();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsNull matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsNull
   *
   * @since Method available since Release 3.3.0
   */
  public static function isNull() {
    return new PHPUnit_Framework_Constraint_IsNull();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsFinite matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsFinite
   *
   * @since Method available since Release 5.0.0
   */
  public static function isFinite() {
    return new PHPUnit_Framework_Constraint_IsFinite();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsInfinite matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsInfinite
   *
   * @since Method available since Release 5.0.0
   */
  public static function isInfinite() {
    return new PHPUnit_Framework_Constraint_IsInfinite();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_TraversableContains matcher
   * object.
   *
   * @param mixed $value
   * @param bool  $checkForObjectIdentity
   * @param bool  $checkForNonObjectIdentity
   *
   * @return PHPUnit_Framework_Constraint_TraversableContains
   *
   * @since Method available since Release 3.0.0
   */
  public static function contains(
    $value,
    $checkForObjectIdentity = true,
    $checkForNonObjectIdentity = false
  ) {
    return new PHPUnit_Framework_Constraint_TraversableContains(
      $value,
      $checkForObjectIdentity,
      $checkForNonObjectIdentity
    );
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_TraversableContainsOnly matcher
   * object.
   *
   * @param string $type
   *
   * @return PHPUnit_Framework_Constraint_TraversableContainsOnly
   *
   * @since Method available since Release 3.1.4
   */
  public static function containsOnly($type) {
    return new PHPUnit_Framework_Constraint_TraversableContainsOnly($type);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_TraversableContainsOnly matcher
   * object.
   *
   * @param string $classname
   *
   * @return PHPUnit_Framework_Constraint_TraversableContainsOnly
   */
  public static function containsOnlyInstancesOf($classname) {
    return new PHPUnit_Framework_Constraint_TraversableContainsOnly(
      $classname,
      false
    );
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_ArrayHasKey matcher object.
   *
   * @param mixed $key
   *
   * @return PHPUnit_Framework_Constraint_ArrayHasKey
   *
   * @since Method available since Release 3.0.0
   */
  public static function arrayHasKey($key) {
    return new PHPUnit_Framework_Constraint_ArrayHasKey($key);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsEmpty matcher object.
   *
   * @return PHPUnit_Framework_Constraint_IsEmpty
   *
   * @since Method available since Release 3.5.0
   */
  public static function isEmpty() {
    throw new AssertionFailedException('isEmpty is deprecated');
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_FileExists matcher object.
   *
   * @return PHPUnit_Framework_Constraint_FileExists
   *
   * @since Method available since Release 3.0.0
   */
  public static function fileExists() {
    return new PHPUnit_Framework_Constraint_FileExists();
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_GreaterThan matcher object.
   *
   * @param mixed $value
   *
   * @return PHPUnit_Framework_Constraint_GreaterThan
   *
   * @since Method available since Release 3.0.0
   */
  public static function greaterThan($value) {
    throw new AssertionFailedException('deprecated greaterThan');
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Or matcher object that wraps
   * a PHPUnit_Framework_Constraint_IsEqual and a
   * PHPUnit_Framework_Constraint_GreaterThan matcher object.
   *
   * @param mixed $value
   *
   * @return PHPUnit_Framework_Constraint_Or
   *
   * @since Method available since Release 3.1.0
   */
  public static function greaterThanOrEqual($value) {
    throw new AssertionFailedException('deprecated greaterThan');
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_ClassHasAttribute matcher object.
   *
   * @param string $attributeName
   *
   * @return PHPUnit_Framework_Constraint_ClassHasAttribute
   *
   * @since Method available since Release 3.1.0
   */
  public static function classHasAttribute($attributeName) {
    return new PHPUnit_Framework_Constraint_ClassHasAttribute($attributeName);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_ClassHasStaticAttribute matcher
   * object.
   *
   * @param string $attributeName
   *
   * @return PHPUnit_Framework_Constraint_ClassHasStaticAttribute
   *
   * @since Method available since Release 3.1.0
   */
  public static function classHasStaticAttribute($attributeName) {
    return new PHPUnit_Framework_Constraint_ClassHasStaticAttribute(
      $attributeName
    );
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_ObjectHasAttribute matcher object.
   *
   * @param string $attributeName
   *
   * @return PHPUnit_Framework_Constraint_ObjectHasAttribute
   *
   * @since Method available since Release 3.0.0
   */
  public static function objectHasAttribute($attributeName) {
    return
      new PHPUnit_Framework_Constraint_ObjectHasAttribute($attributeName);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsIdentical matcher object.
   *
   * @param mixed $value
   *
   * @return PHPUnit_Framework_Constraint_IsIdentical
   *
   * @since Method available since Release 3.0.0
   */
  public static function identicalTo($value) {
    return new PHPUnit_Framework_Constraint_IsIdentical($value);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsInstanceOf matcher object.
   *
   * @param string $className
   *
   * @return PHPUnit_Framework_Constraint_IsInstanceOf
   *
   * @since Method available since Release 3.0.0
   */
  public static function isInstanceOf($className) {
    return new PHPUnit_Framework_Constraint_IsInstanceOf($className);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_IsType matcher object.
   *
   * @param string $type
   *
   * @return PHPUnit_Framework_Constraint_IsType
   *
   * @since Method available since Release 3.0.0
   */
  public static function isType($type) {
    return new PHPUnit_Framework_Constraint_IsType($type);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_LessThan matcher object.
   *
   * @param mixed $value
   *
   * @return PHPUnit_Framework_Constraint_LessThan
   *
   * @since Method available since Release 3.0.0
   */
  public static function lessThan($value) {
    return new PHPUnit_Framework_Constraint_LessThan($value);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Or matcher object that wraps
   * a PHPUnit_Framework_Constraint_IsEqual and a
   * PHPUnit_Framework_Constraint_LessThan matcher object.
   *
   * @param mixed $value
   *
   * @return PHPUnit_Framework_Constraint_Or
   *
   * @since Method available since Release 3.1.0
   */
  public static function lessThanOrEqual($value) {
    return static::logicalOr(
      new PHPUnit_Framework_Constraint_IsEqual($value),
      new PHPUnit_Framework_Constraint_LessThan($value)
    );
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_PCREMatch matcher object.
   *
   * @param string $pattern
   *
   * @return PHPUnit_Framework_Constraint_PCREMatch
   *
   * @since Method available since Release 3.0.0
   */
  public static function matchesRegularExpression($pattern) {
    return new PHPUnit_Framework_Constraint_PCREMatch($pattern);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_StringMatches matcher object.
   *
   * @param string $string
   *
   * @return PHPUnit_Framework_Constraint_StringMatches
   *
   * @since Method available since Release 3.5.0
   */
  public static function matches($string) {
    return new PHPUnit_Framework_Constraint_StringMatches($string);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_StringStartsWith matcher object.
   *
   * @param mixed $prefix
   *
   * @return PHPUnit_Framework_Constraint_StringStartsWith
   *
   * @since Method available since Release 3.4.0
   */
  public static function stringStartsWith($prefix) {
    return new PHPUnit_Framework_Constraint_StringStartsWith($prefix);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_StringContains matcher object.
   *
   * @param string $string
   * @param bool   $case
   *
   * @return PHPUnit_Framework_Constraint_StringContains
   *
   * @since Method available since Release 3.0.0
   */
  public static function stringContains($string, $case = true) {
    return new PHPUnit_Framework_Constraint_StringContains($string, $case);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_StringEndsWith matcher object.
   *
   * @param mixed $suffix
   *
   * @return PHPUnit_Framework_Constraint_StringEndsWith
   *
   * @since Method available since Release 3.4.0
   */
  public static function stringEndsWith($suffix) {
    return new PHPUnit_Framework_Constraint_StringEndsWith($suffix);
  }

  /**
   * Returns a PHPUnit_Framework_Constraint_Count matcher object.
   *
   * @param int $count
   *
   * @return PHPUnit_Framework_Constraint_Count
   */
  public static function countOf($count) {
    return new PHPUnit_Framework_Constraint_Count($count);
  }

  /**
   * Fails a test with the given message.
   *
   * @param string $message
   *
   * @throws PHPUnit_Framework_AssertionFailedError
   */
  private static function legacyFail($message = '') {
     $assertions = AssertionsFactory::factory();
     return $assertions->fail($message);
  }

  /**
   * Returns the value of an attribute of a class or an object.
   * This also works for attributes that are declared protected or private.
   *
   * @param string|object $classOrObject
   * @param string        $attributeName
   *
   * @return mixed
   *
   * @throws PHPUnit_Framework_Exception
   */
  public static function legacyReadAttribute($classOrObject, $attributeName) {
    if (!is_string($attributeName)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    if (!preg_match(
          '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',
          $attributeName
        )) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        2,
        'valid attribute name'
      );
    }

    if (is_string($classOrObject)) {
      if (!class_exists($classOrObject)) {
        throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
      }

      return static::legacyGetStaticAttribute($classOrObject, $attributeName);
    } else if (is_object($classOrObject)) {
      return static::legacyGetObjectAttribute($classOrObject, $attributeName);
    } else {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        1,
        'class name or object'
      );
    }
  }

  /**
   * Returns the value of a static attribute.
   * This also works for attributes that are declared protected or private.
   *
   * @param string $className
   * @param string $attributeName
   *
   * @return mixed
   *
   * @throws PHPUnit_Framework_Exception
   *
   * @since Method available since Release 4.0.0
   */
  private static function legacyGetStaticAttribute($className, $attributeName) {
    if (!is_string($className)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string');
    }

    if (!class_exists($className)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'class name');
    }

    if (!is_string($attributeName)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    if (!preg_match(
          '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',
          $attributeName
        )) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        2,
        'valid attribute name'
      );
    }

    $class = ReflectionClasses::getReflection($className);

    while ($class) {
      $attributes = $class->getStaticProperties();

      if (array_key_exists($attributeName, $attributes)) {
        return $attributes[$attributeName];
      }

      $class = $class->getParentClass();
    }

    throw new PHPUnit_Framework_Exception(
      sprintf('Attribute "%s" not found in class.', $attributeName)
    );
  }

  /**
   * Returns the value of an object's attribute.
   * This also works for attributes that are declared protected or private.
   *
   * @param object $object
   * @param string $attributeName
   *
   * @return mixed
   *
   * @throws PHPUnit_Framework_Exception
   *
   * @since Method available since Release 4.0.0
   */
  private static function legacyGetObjectAttribute($object, $attributeName) {
    if (!is_object($object)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'object');
    }

    if (!is_string($attributeName)) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string');
    }

    if (!preg_match(
          '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/',
          $attributeName
        )) {
      throw PHPUnit_Util_InvalidArgumentHelper::factory(
        2,
        'valid attribute name'
      );
    }

    try {
      $attribute = new ReflectionProperty($object, $attributeName);
    } catch (ReflectionException $e) {
      $reflector = new ReflectionObject($object);

      while ($reflector = $reflector->getParentClass()) {
        try {
          $attribute = $reflector->getProperty($attributeName);
          break;
        } catch (ReflectionException $e) {
        }
      }
    }

    if (isset($attribute)) {
      if (!$attribute || $attribute->isPublic()) {
        return $object->$attributeName;
      }

      $attribute->setAccessible(true);
      $value = $attribute->getValue($object);
      $attribute->setAccessible(false);

      return $value;
    }

    throw new PHPUnit_Framework_Exception(
      sprintf('Attribute "%s" not found in object.', $attributeName)
    );
  }

  /**
   * Mark the test as incomplete.
   *
   * @param string $message
   *
   * @throws PHPUnit_Framework_IncompleteTestError
   *
   * @since Method available since Release 3.0.0
   */
  public static function markTestIncomplete($message = '') {
    throw new PHPUnit_Framework_IncompleteTestError($message);
  }

  /**
   * Mark the test as skipped.
   *
   * @param string $message
   *
   * @throws PHPUnit_Framework_SkippedTestError
   *
   * @since Method available since Release 3.0.0
   */
  public static function markTestSkipped($message = '') {
    throw new PHPUnit_Framework_SkippedTestError($message);
  }

  /**
   * Return the current assertion count.
   *
   * @return int
   *
   * @since Method available since Release 3.3.3
   */
  public static function getCount() {
    $assertions = AssertionsFactory::factory();
    return $assertions->counter()->get();
  }

  /**
   * Reset the assertion counter.
   *
   * @since Method available since Release 3.3.3
   */
  public static function resetCount() {
    $assertions = AssertionsFactory::factory();
    return $assertions->counter()->reset();
  }

}
