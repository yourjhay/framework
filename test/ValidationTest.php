<?php

namespace Simple\Tests;

use PHPUnit\Framework\TestCase;
use Simple\Validation\Validator;

class ValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
        $this->resetValidatorStaticState();
    }

    private function resetValidatorStaticState(): void
    {
        $ref = new \ReflectionClass(Validator::class);
        foreach (['fields', 'validation_methods', 'validation_methods_errors', 'filter_methods', 'instance'] as $prop) {
            $p = $ref->getParentClass()->getProperty($prop);
            $p->setAccessible(true);
            $p->setValue(null, match ($prop) {
                'instance' => null,
                default => [],
            });
        }
    }

    // -------------------------------------------
    //  Required Validator
    // -------------------------------------------

    public function testRequiredPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'value'],
            ['field' => 'required']
        );
        $this->assertTrue($result);
    }

    public function testRequiredFailsOnEmptyString(): void
    {
        $result = $this->validator->validate(
            ['field' => ''],
            ['field' => 'required']
        );
        $this->assertIsArray($result);
    }

    public function testRequiredFailsOnNull(): void
    {
        $result = $this->validator->validate(
            ['field' => null],
            ['field' => 'required']
        );
        $this->assertIsArray($result);
    }

    public function testRequiredFailsOnMissingKey(): void
    {
        $result = $this->validator->validate(
            [],
            ['field' => 'required']
        );
        $this->assertIsArray($result);
    }

    public function testRequiredFailsOnEmptyArray(): void
    {
        $result = $this->validator->validate(
            ['field' => []],
            ['field' => 'required']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  valid_email
    // -------------------------------------------

    public function testValidEmailPasses(): void
    {
        $result = $this->validator->validate(
            ['email' => 'user@example.com'],
            ['email' => 'valid_email']
        );
        $this->assertTrue($result);
    }

    public function testValidEmailFails(): void
    {
        $result = $this->validator->validate(
            ['email' => 'not-an-email'],
            ['email' => 'valid_email']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  max_len, min_len, exact_len, between_len
    // -------------------------------------------

    public function testMaxLenPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc'],
            ['field' => 'max_len,5']
        );
        $this->assertTrue($result);
    }

    public function testMaxLenFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abcdef'],
            ['field' => 'max_len,5']
        );
        $this->assertIsArray($result);
    }

    public function testMinLenPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abcdef'],
            ['field' => 'min_len,5']
        );
        $this->assertTrue($result);
    }

    public function testMinLenFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc'],
            ['field' => 'min_len,5']
        );
        $this->assertIsArray($result);
    }

    public function testExactLenPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abcde'],
            ['field' => 'exact_len,5']
        );
        $this->assertTrue($result);
    }

    public function testExactLenFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abcd'],
            ['field' => 'exact_len,5']
        );
        $this->assertIsArray($result);
    }

    public function testBetweenLenPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'hello'],
            ['field' => 'between_len,3;7']
        );
        $this->assertTrue($result);
    }

    public function testBetweenLenFailsTooShort(): void
    {
        $result = $this->validator->validate(
            ['field' => 'hi'],
            ['field' => 'between_len,3;7']
        );
        $this->assertIsArray($result);
    }

    public function testBetweenLenFailsTooLong(): void
    {
        $result = $this->validator->validate(
            ['field' => 'very long string'],
            ['field' => 'between_len,3;7']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  alpha, alpha_numeric, alpha_dash, etc.
    // -------------------------------------------

    public function testAlphaPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abcdef'],
            ['field' => 'alpha']
        );
        $this->assertTrue($result);
    }

    public function testAlphaFailsWithNumbers(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc123'],
            ['field' => 'alpha']
        );
        $this->assertIsArray($result);
    }

    public function testAlphaNumericPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc123'],
            ['field' => 'alpha_numeric']
        );
        $this->assertTrue($result);
    }

    public function testAlphaNumericFailsWithSpaces(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc 123'],
            ['field' => 'alpha_numeric']
        );
        $this->assertIsArray($result);
    }

    public function testAlphaDashPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc_def'],
            ['field' => 'alpha_dash']
        );
        $this->assertTrue($result);
    }

    public function testAlphaNumericDashPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc-123_def'],
            ['field' => 'alpha_numeric_dash']
        );
        $this->assertTrue($result);
    }

    public function testAlphaNumericSpacePasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc 123'],
            ['field' => 'alpha_numeric_space']
        );
        $this->assertTrue($result);
    }

    public function testAlphaSpacePasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc def'],
            ['field' => 'alpha_space']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  numeric, integer, float
    // -------------------------------------------

    public function testNumericPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => '123.45'],
            ['field' => 'numeric']
        );
        $this->assertTrue($result);
    }

    public function testNumericFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'abc'],
            ['field' => 'numeric']
        );
        $this->assertIsArray($result);
    }

    public function testIntegerPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 42],
            ['field' => 'integer']
        );
        $this->assertTrue($result);
    }

    public function testIntegerFailsOnFloat(): void
    {
        $result = $this->validator->validate(
            ['field' => 3.14],
            ['field' => 'integer']
        );
        $this->assertIsArray($result);
    }

    public function testFloatPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 3.14],
            ['field' => 'float']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  boolean
    // -------------------------------------------

    public function testBooleanPassesOnOne(): void
    {
        $result = $this->validator->validate(
            ['field' => 1],
            ['field' => 'boolean']
        );
        $this->assertTrue($result);
    }

    public function testBooleanPassesOnTrueString(): void
    {
        $result = $this->validator->validate(
            ['field' => 'true'],
            ['field' => 'boolean']
        );
        $this->assertTrue($result);
    }

    public function testBooleanFailsOnArbitraryString(): void
    {
        $result = $this->validator->validate(
            ['field' => 'maybe'],
            ['field' => 'boolean']
        );
        $this->assertIsArray($result);
    }

    public function testBooleanStrictPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => true],
            ['field' => 'boolean,strict']
        );
        $this->assertTrue($result);
    }

    public function testBooleanStrictFailsOnOne(): void
    {
        $result = $this->validator->validate(
            ['field' => 1],
            ['field' => 'boolean,strict']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  valid_url, valid_ip, valid_ipv4, valid_ipv6
    // -------------------------------------------

    public function testValidUrlPasses(): void
    {
        $result = $this->validator->validate(
            ['url' => 'https://example.com'],
            ['url' => 'valid_url']
        );
        $this->assertTrue($result);
    }

    public function testValidUrlFails(): void
    {
        $result = $this->validator->validate(
            ['url' => 'not-a-url'],
            ['url' => 'valid_url']
        );
        $this->assertIsArray($result);
    }

    public function testValidIpPasses(): void
    {
        $result = $this->validator->validate(
            ['ip' => '192.168.1.1'],
            ['ip' => 'valid_ip']
        );
        $this->assertTrue($result);
    }

    public function testValidIpFails(): void
    {
        $result = $this->validator->validate(
            ['ip' => '999.999.999.999'],
            ['ip' => 'valid_ip']
        );
        $this->assertIsArray($result);
    }

    public function testValidIpv4Passes(): void
    {
        $result = $this->validator->validate(
            ['ip' => '10.0.0.1'],
            ['ip' => 'valid_ipv4']
        );
        $this->assertTrue($result);
    }

    public function testValidIpv6Passes(): void
    {
        $result = $this->validator->validate(
            ['ip' => '::1'],
            ['ip' => 'valid_ipv6']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  contains, contains_list, doesnt_contain_list
    // -------------------------------------------

    public function testContainsPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'a'],
            ['field' => 'contains,a;b;c']
        );
        $this->assertTrue($result);
    }

    public function testContainsFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'd'],
            ['field' => 'contains,a;b;c']
        );
        $this->assertIsArray($result);
    }

    public function testDoesntContainListPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'd'],
            ['field' => 'doesnt_contain_list,a;b;c']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  min_numeric, max_numeric
    // -------------------------------------------

    public function testMinNumericPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 10],
            ['field' => 'min_numeric,5']
        );
        $this->assertTrue($result);
    }

    public function testMinNumericFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 3],
            ['field' => 'min_numeric,5']
        );
        $this->assertIsArray($result);
    }

    public function testMaxNumericPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 3],
            ['field' => 'max_numeric,5']
        );
        $this->assertTrue($result);
    }

    public function testMaxNumericFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 10],
            ['field' => 'max_numeric,5']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  starts
    // -------------------------------------------

    public function testStartsPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'hello world'],
            ['field' => 'starts,hello']
        );
        $this->assertTrue($result);
    }

    public function testStartsFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'world hello'],
            ['field' => 'starts,hello']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  regex
    // -------------------------------------------

    public function testRegexPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => 'test-123'],
            ['field' => 'regex,/^test-\d{3}$/']
        );
        $this->assertTrue($result);
    }

    public function testRegexFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'test-abcd'],
            ['field' => 'regex,/^test-\d{3}$/']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  date
    // -------------------------------------------

    public function testDatePassesIso(): void
    {
        $result = $this->validator->validate(
            ['field' => '2025-01-01'],
            ['field' => 'date']
        );
        $this->assertTrue($result);
    }

    public function testDateFails(): void
    {
        $result = $this->validator->validate(
            ['field' => 'not-a-date'],
            ['field' => 'date']
        );
        $this->assertIsArray($result);
    }

    public function testDateCustomFormatPasses(): void
    {
        $result = $this->validator->validate(
            ['field' => '15/01/2025'],
            ['field' => 'date,d/m/Y']
        );
        $this->assertTrue($result);
    }

    public function testDateCustomFormatFails(): void
    {
        $result = $this->validator->validate(
            ['field' => '2025-01-15'],
            ['field' => 'date,d/m/Y']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  min_age
    // -------------------------------------------

    public function testMinAgePasses(): void
    {
        $result = $this->validator->validate(
            ['field' => '2000-01-01'],
            ['field' => 'min_age,18']
        );
        $this->assertTrue($result);
    }

    public function testMinAgeFails(): void
    {
        $result = $this->validator->validate(
            ['field' => date('Y-m-d')],
            ['field' => 'min_age,18']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  valid_cc
    // -------------------------------------------

    public function testValidCcPasses(): void
    {
        $result = $this->validator->validate(
            ['cc' => '4111111111111111'],
            ['cc' => 'valid_cc']
        );
        $this->assertTrue($result);
    }

    public function testValidCcFails(): void
    {
        $result = $this->validator->validate(
            ['cc' => '1234567890123456'],
            ['cc' => 'valid_cc']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  valid_name
    // -------------------------------------------

    public function testValidNamePasses(): void
    {
        $result = $this->validator->validate(
            ['name' => 'John Doe'],
            ['name' => 'valid_name']
        );
        $this->assertTrue($result);
    }

    public function testValidNameFailsWithNumbers(): void
    {
        $result = $this->validator->validate(
            ['name' => 'John123'],
            ['name' => 'valid_name']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  street_address
    // -------------------------------------------

    public function testStreetAddressPasses(): void
    {
        $result = $this->validator->validate(
            ['address' => '123 Main St'],
            ['address' => 'street_address']
        );
        $this->assertTrue($result);
    }

    public function testStreetAddressFails(): void
    {
        $result = $this->validator->validate(
            ['address' => 'Main Street'],
            ['address' => 'street_address']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  phone_number
    // -------------------------------------------

    public function testPhoneNumberPasses(): void
    {
        $result = $this->validator->validate(
            ['phone' => '555-555-5555'],
            ['phone' => 'phone_number']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  valid_json_string
    // -------------------------------------------

    public function testValidJsonStringPasses(): void
    {
        $result = $this->validator->validate(
            ['json' => '{"key":"value"}'],
            ['json' => 'valid_json_string']
        );
        $this->assertTrue($result);
    }

    public function testValidJsonStringFails(): void
    {
        $result = $this->validator->validate(
            ['json' => '{invalid json}'],
            ['json' => 'valid_json_string']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  equalsfield
    // -------------------------------------------

    public function testEqualsfieldPasses(): void
    {
        $result = $this->validator->validate(
            ['password' => 'secret', 'password_confirm' => 'secret'],
            ['password_confirm' => 'equalsfield,password']
        );
        $this->assertTrue($result);
    }

    public function testEqualsfieldFails(): void
    {
        $result = $this->validator->validate(
            ['password' => 'secret', 'password_confirm' => 'different'],
            ['password_confirm' => 'equalsfield,password']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  valid_array_size_greater, _lesser, _equal
    // -------------------------------------------

    public function testArraySizeGreaterPasses(): void
    {
        $result = $this->validator->validate(
            ['items' => ['a', 'b', 'c']],
            ['items' => 'valid_array_size_greater,2']
        );
        $this->assertTrue($result);
    }

    public function testArraySizeGreaterFails(): void
    {
        $result = $this->validator->validate(
            ['items' => ['a']],
            ['items' => 'valid_array_size_greater,2']
        );
        $this->assertIsArray($result);
    }

    public function testArraySizeLesserPasses(): void
    {
        $result = $this->validator->validate(
            ['items' => ['a']],
            ['items' => 'valid_array_size_lesser,5']
        );
        $this->assertTrue($result);
    }

    public function testArraySizeEqualPasses(): void
    {
        $result = $this->validator->validate(
            ['items' => ['a', 'b']],
            ['items' => 'valid_array_size_equal,2']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  guidv4
    // -------------------------------------------

    public function testGuidv4Passes(): void
    {
        $result = $this->validator->validate(
            ['guid' => '550e8400-e29b-41d4-a716-446655440000'],
            ['guid' => 'guidv4']
        );
        $this->assertTrue($result);
    }

    public function testGuidv4Fails(): void
    {
        $result = $this->validator->validate(
            ['guid' => 'not-a-guid'],
            ['guid' => 'guidv4']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  iban
    // -------------------------------------------

    public function testIbanPasses(): void
    {
        if (!function_exists('bcmod')) {
            $this->markTestSkipped('BCMath extension is required for IBAN validation.');
        }

        $result = $this->validator->validate(
            ['iban' => 'GB29NWBK60161331926819'],
            ['iban' => 'iban']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  required_file / extension
    // -------------------------------------------

    public function testRequiredFilePasses(): void
    {
        $_FILES['file'] = [
            'name' => 'test.png',
            'type' => 'image/png',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => 0,
            'size' => 1024,
        ];
        $result = $this->validator->validate(
            ['file' => $_FILES['file']],
            ['file' => 'required_file']
        );
        $this->assertTrue($result);
    }

    public function testRequiredFileFailsOnError(): void
    {
        $_FILES['file'] = [
            'name' => 'test.png',
            'type' => 'image/png',
            'tmp_name' => '',
            'error' => 1,
            'size' => 0,
        ];
        $result = $this->validator->validate(
            ['file' => $_FILES['file']],
            ['file' => 'required_file']
        );
        $this->assertIsArray($result);
    }

    public function testExtensionPasses(): void
    {
        $_FILES['file'] = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => 0,
            'size' => 2048,
        ];
        $result = $this->validator->validate(
            ['file' => $_FILES['file']],
            ['file' => 'extension,jpg;jpeg;png']
        );
        $this->assertTrue($result);
    }

    public function testExtensionFails(): void
    {
        $_FILES['file'] = [
            'name' => 'document.exe',
            'type' => 'application/x-msdownload',
            'tmp_name' => '/tmp/phpXXXXXX',
            'error' => 0,
            'size' => 1024,
        ];
        $result = $this->validator->validate(
            ['file' => $_FILES['file']],
            ['file' => 'extension,jpg;jpeg;png']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  Multiple rules chained
    // -------------------------------------------

    public function testMultipleRulesAllPass(): void
    {
        $result = $this->validator->validate(
            ['username' => 'johndoe'],
            ['username' => 'required|alpha_numeric|min_len,3|max_len,20']
        );
        $this->assertTrue($result);
    }

    public function testMultipleRulesOneFails(): void
    {
        $result = $this->validator->validate(
            ['username' => 'jd'],
            ['username' => 'required|alpha_numeric|min_len,3|max_len,20']
        );
        $this->assertIsArray($result);
    }

    // -------------------------------------------
    //  Optional fields (not required, empty)
    // -------------------------------------------

    public function testOptionalFieldSkipsWhenEmpty(): void
    {
        $result = $this->validator->validate(
            ['field' => ''],
            ['field' => 'valid_email']
        );
        $this->assertTrue($result);
    }

    public function testOptionalFieldValidatesWhenPresent(): void
    {
        $result = $this->validator->validate(
            ['field' => 'user@example.com'],
            ['field' => 'valid_email']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  Nested / dot-notation fields
    // -------------------------------------------

    public function testNestedFieldValidationPasses(): void
    {
        $result = $this->validator->validate(
            ['user' => ['email' => 'user@example.com']],
            ['user.email' => 'required|valid_email']
        );
        $this->assertTrue($result);
    }

    public function testNestedFieldValidationFails(): void
    {
        $result = $this->validator->validate(
            ['user' => ['email' => 'bad']],
            ['user.email' => 'valid_email']
        );
        $this->assertIsArray($result);
    }

    public function testWildcardNestedValidationPasses(): void
    {
        $result = $this->validator->validate(
            ['products' => [['name' => 'Widget'], ['name' => 'Gadget']]],
            ['products.*.name' => 'required|min_len,3']
        );
        $this->assertTrue($result);
    }

    // -------------------------------------------
    //  Filters
    // -------------------------------------------

    public function testFilterTrim(): void
    {
        $result = $this->validator->filter(
            ['field' => '  hello  '],
            ['field' => 'trim']
        );
        $this->assertSame('hello', $result['field']);
    }

    public function testFilterLowercase(): void
    {
        $result = $this->validator->filter(
            ['field' => 'HELLO'],
            ['field' => 'lower_case']
        );
        $this->assertSame('hello', $result['field']);
    }

    public function testFilterUppercase(): void
    {
        $result = $this->validator->filter(
            ['field' => 'hello'],
            ['field' => 'upper_case']
        );
        $this->assertSame('HELLO', $result['field']);
    }

    public function testFilterSlug(): void
    {
        $result = $this->validator->filter(
            ['field' => 'Hello World!'],
            ['field' => 'slug']
        );
        $this->assertSame('hello-world', $result['field']);
    }

    public function testFilterSanitizeString(): void
    {
        $result = $this->validator->filter(
            ['field' => '<script>alert("xss")</script>clean'],
            ['field' => 'sanitize_string']
        );
        $this->assertStringNotContainsString('<script>', $result['field']);
        $this->assertStringContainsString('clean', $result['field']);
    }

    public function testFilterBooleanTrue(): void
    {
        $result = $this->validator->filter(
            ['field' => 'yes'],
            ['field' => 'boolean']
        );
        $this->assertTrue($result['field']);
    }

    public function testFilterBooleanFalse(): void
    {
        $result = $this->validator->filter(
            ['field' => 'no'],
            ['field' => 'boolean']
        );
        $this->assertFalse($result['field']);
    }

    public function testFilterWholeNumber(): void
    {
        $result = $this->validator->filter(
            ['field' => 3.99],
            ['field' => 'whole_number']
        );
        $this->assertSame(3, $result['field']);
    }

    public function testFilterChain(): void
    {
        $result = $this->validator->filter(
            ['title' => '  My Amazing Post!!!  '],
            ['title' => 'trim|slug']
        );
        $this->assertSame('my-amazing-post', $result['title']);
    }

    // -------------------------------------------
    //  is_valid shorthand
    // -------------------------------------------

    public function testIsValidReturnsTrue(): void
    {
        $result = Validator::is_valid(
            ['email' => 'user@example.com'],
            ['email' => 'valid_email']
        );
        $this->assertTrue($result);
    }

    public function testIsValidReturnsErrors(): void
    {
        $result = Validator::is_valid(
            ['email' => 'invalid'],
            ['email' => 'valid_email']
        );
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    // -------------------------------------------
    //  filter_input shorthand
    // -------------------------------------------

    public function testFilterInput(): void
    {
        $result = Validator::filter_input(
            ['name' => '  John  '],
            ['name' => 'trim|upper_case']
        );
        $this->assertSame('JOHN', $result['name']);
    }

    // -------------------------------------------
    //  run()
    // -------------------------------------------

    public function testRunReturnsDataOnSuccess(): void
    {
        $this->validator->validation_rules(['email' => 'valid_email']);
        $this->validator->filter_rules(['email' => 'trim']);

        $result = $this->validator->run(['email' => ' user@example.com ']);
        $this->assertIsArray($result);
        $this->assertSame('user@example.com', $result['email']);
    }

    public function testRunReturnsFalseOnFailure(): void
    {
        $this->validator->validation_rules(['email' => 'valid_email']);

        $result = $this->validator->run(['email' => 'invalid']);
        $this->assertFalse($result);
    }

    // -------------------------------------------
    //  sanitize
    // -------------------------------------------

    public function testSanitizeStripsTags(): void
    {
        $result = $this->validator->sanitize(
            ['field' => '<b>bold</b>'],
            ['field']
        );
        $this->assertStringNotContainsString('<b>', $result['field']);
    }

    // -------------------------------------------
    //  xss_clean
    // -------------------------------------------

    public function testXssCleanStripsTags(): void
    {
        $result = Validator::xss_clean(
            ['comment' => '<script>alert("xss")</script>']
        );
        $this->assertStringNotContainsString('<script>', $result['comment']);
        $this->assertStringContainsString('alert', $result['comment']);
    }

    // -------------------------------------------
    //  get_readable_errors / get_errors_array
    // -------------------------------------------

    public function testGetReadableErrors(): void
    {
        $this->validator->validate(
            ['email' => 'invalid'],
            ['email' => 'valid_email']
        );
        $errors = $this->validator->get_readable_errors();
        $this->assertIsArray($errors);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Email', $errors[0]);
    }

    public function testGetErrorsArray(): void
    {
        $this->validator->validate(
            ['email' => 'invalid'],
            ['email' => 'valid_email']
        );
        $errors = $this->validator->get_errors_array();
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testGetReadableErrorsReturnsEmptyOnNoErrors(): void
    {
        $errors = $this->validator->get_readable_errors();
        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    // -------------------------------------------
    //  field helper
    // -------------------------------------------

    public function testFieldReturnsValue(): void
    {
        $result = Validator::field('name', ['name' => 'John']);
        $this->assertSame('John', $result);
    }

    public function testFieldReturnsDefault(): void
    {
        $result = Validator::field('missing', [], 'default');
        $this->assertSame('default', $result);
    }

    // -------------------------------------------
    //  set_field_name
    // -------------------------------------------

    public function testSetFieldNameAppearsInErrors(): void
    {
        Validator::set_field_name('usr', 'Username');
        $result = Validator::is_valid(
            ['usr' => ''],
            ['usr' => 'required']
        );
        $this->assertStringContainsString('Username', $result[0]);
    }

    // -------------------------------------------
    //  Custom error messages
    // -------------------------------------------

    public function testCustomFieldErrorMessages(): void
    {
        $result = Validator::is_valid(
            ['email' => 'invalid'],
            ['email' => 'valid_email'],
            ['email' => ['valid_email' => 'Custom email error']]
        );
        $this->assertStringContainsString('Custom email error', $result[0]);
    }

    // -------------------------------------------
    //  Global error messages
    // -------------------------------------------

    public function testSetErrorMessage(): void
    {
        Validator::set_error_message('valid_email', 'Global email error');
        $result = Validator::is_valid(
            ['email' => 'invalid'],
            ['email' => 'valid_email']
        );
        $this->assertStringContainsString('Global email error', $result[0]);
    }

    // -------------------------------------------
    //  has_validator / has_filter
    // -------------------------------------------

    public function testHasValidatorReturnsTrue(): void
    {
        $this->assertTrue(Validator::has_validator('required'));
    }

    public function testHasValidatorReturnsFalse(): void
    {
        $this->assertFalse(Validator::has_validator('nonexistent_rule'));
    }

    public function testHasFilterReturnsTrue(): void
    {
        $this->assertTrue(Validator::has_filter('trim'));
    }

    public function testHasFilterReturnsFalse(): void
    {
        $this->assertFalse(Validator::has_filter('nonexistent_filter'));
    }

    // -------------------------------------------
    //  Singleton
    // -------------------------------------------

    public function testGetInstanceReturnsSameInstance(): void
    {
        $a = Validator::get_instance();
        $b = Validator::get_instance();
        $this->assertSame($a, $b);
    }

    // -------------------------------------------
    //  Custom validators & filters via callbacks
    // -------------------------------------------

    public function testAddCustomValidator(): void
    {
        Validator::add_validator('is_even', function ($field, array $input, array $params, $value) {
            return is_numeric($value) && $value % 2 === 0;
        }, '{field} must be even.');

        $result = $this->validator->validate(
            ['num' => 4],
            ['num' => 'is_even']
        );
        $this->assertTrue($result);
    }

    public function testCustomValidatorFails(): void
    {
        Validator::add_validator('is_odd', function ($field, array $input, array $params, $value) {
            return is_numeric($value) && $value % 2 !== 0;
        }, '{field} must be odd.');

        $result = $this->validator->validate(
            ['num' => 4],
            ['num' => 'is_odd']
        );
        $this->assertIsArray($result);
    }

    public function testAddCustomFilter(): void
    {
        Validator::add_filter('reverse', function ($value, array $params = []) {
            return strrev($value);
        });

        $result = $this->validator->filter(
            ['field' => 'hello'],
            ['field' => 'reverse']
        );
        $this->assertSame('olleh', $result['field']);
    }

    public function testAddDuplicateValidatorThrows(): void
    {
        $this->expectException(\Exception::class);
        Validator::add_validator('required', function () {}, 'duplicate');
    }

    // -------------------------------------------
    //  Rule missing throws
    // -------------------------------------------

    public function testInvalidValidatorThrows(): void
    {
        $this->expectException(\Exception::class);
        $this->validator->validate(
            ['field' => 'value'],
            ['field' => 'does_not_exist']
        );
    }

    public function testInvalidFilterThrows(): void
    {
        $this->expectException(\Exception::class);
        $this->validator->filter(
            ['field' => 'value'],
            ['field' => 'does_not_exist']
        );
    }

    // -------------------------------------------
    //  __toString
    // -------------------------------------------

    public function testToStringReturnsErrors(): void
    {
        $v = new Validator();
        $v->validate(['email' => 'bad'], ['email' => 'valid_email']);
        $output = (string) $v;
        $this->assertStringContainsString('Email', $output);
    }

    // -------------------------------------------
    //  Edge cases
    // -------------------------------------------

    public function testEmptyDataReturnsTrue(): void
    {
        $result = $this->validator->validate([], []);
        $this->assertTrue($result);
    }

    public function testZeroIsNotEmpty(): void
    {
        $result = $this->validator->validate(
            ['field' => 0],
            ['field' => 'required']
        );
        $this->assertTrue($result);
    }

    public function testFalseIsNotEmpty(): void
    {
        $result = $this->validator->validate(
            ['field' => false],
            ['field' => 'required']
        );
        $this->assertTrue($result);
    }

    public function testUtf8Characters(): void
    {
        $result = $this->validator->validate(
            ['field' => 'ñ á é í ó ú'],
            ['field' => 'alpha_space']
        );
        $this->assertTrue($result);
    }

    public function testBetweenLenWithUtf8(): void
    {
        $result = $this->validator->validate(
            ['field' => 'ñññññ'],
            ['field' => 'between_len,4;6']
        );
        $this->assertTrue($result);
    }

    public function testIsEmptyReturnsTrueForNull(): void
    {
        $this->assertTrue(Validator::is_empty(null));
    }

    public function testIsEmptyReturnsTrueForEmptyString(): void
    {
        $this->assertTrue(Validator::is_empty(''));
    }

    public function testIsEmptyReturnsTrueForEmptyArray(): void
    {
        $this->assertTrue(Validator::is_empty([]));
    }

    public function testIsEmptyReturnsFalseForZero(): void
    {
        $this->assertFalse(Validator::is_empty(0));
    }

    public function testIsEmptyReturnsFalseForFalse(): void
    {
        $this->assertFalse(Validator::is_empty(false));
    }

    // -------------------------------------------
    //  Language support
    // -------------------------------------------

    public function testConstructorWithNonExistentLangThrows(): void
    {
        $this->expectException(\Exception::class);
        new Validator('xx');
    }

    // -------------------------------------------
    //  get_instance on Validator (boots unique)
    // -------------------------------------------

    public function testGetInstanceBootsOnce(): void
    {
        $v = Validator::get_instance();
        $this->assertInstanceOf(Validator::class, $v);
    }

    // -------------------------------------------
    //  check_fields via run with true
    // -------------------------------------------

    public function testRunCheckFieldsDetectsMismatch(): void
    {
        $v = new Validator();
        $v->validation_rules(['name' => 'required']);
        $v->run(['name' => 'John', 'extra' => 'field'], true);
        $errors = $v->errors();
        $this->assertCount(1, $errors);
        $this->assertSame('extra', $errors[0]['field']);
        $this->assertSame('mismatch', $errors[0]['rule']);
    }
}
