<?php

namespace KwtSMS\Tests;

use KwtSMS\PhoneUtils;
use PHPUnit\Framework\TestCase;

// 2026-11-05 08:39 +03: Kuwait sample numbers removed; tests that need one read
// KWTSMS_TEST_NUMBER through the TestNumber trait (skip when unset), providers yield the
// variant kind only so dataset names never hold the number, int-testnumber.
class PhoneUtilsTest extends TestCase
{
    use TestNumber;

    // -------------------------------------------------------------------------
    // normalize_phone
    // -------------------------------------------------------------------------

    /** @return array<string, array{string}> */
    public static function normalizeVariantProvider(): array
    {
        return [
            'plus space dash' => ['plus-space-dash'],
            'double zero'     => ['00'],
            'single zero'     => ['0'],
            'padded'          => ['padded'],
            'dashes'          => ['dashes'],
            'parens'          => ['parens'],
            'arabic-indic'    => ['arabic'],
            'persian'         => ['persian'],
        ];
    }

    /**
     * @dataProvider normalizeVariantProvider
     */
    public function testNormalizePhoneVariants(string $kind): void
    {
        $n = $this->kwtTestNumber();
        $this->assertSameNumber($n, PhoneUtils::normalize_phone($this->kwtTestNumberVariant($kind)));
    }

    public function testNormalizePhone(): void
    {
        // Edge cases
        $this->assertSame('', PhoneUtils::normalize_phone(''));
        $this->assertSame('', PhoneUtils::normalize_phone('abc@xyz.com'));
    }

    // -------------------------------------------------------------------------
    // find_country_code
    // -------------------------------------------------------------------------

    public function testFindCountryCode(): void
    {
        // 3-digit codes take priority
        $this->assertSame('965', PhoneUtils::find_country_code('96512345678'));
        $this->assertSame('966', PhoneUtils::find_country_code('966501234567'));
        $this->assertSame('880', PhoneUtils::find_country_code('8801712345678'));

        // 2-digit codes
        $this->assertSame('20', PhoneUtils::find_country_code('201012345678'));
        $this->assertSame('44', PhoneUtils::find_country_code('447911123456'));
        $this->assertSame('91', PhoneUtils::find_country_code('919876543210'));

        // 1-digit code
        $this->assertSame('1', PhoneUtils::find_country_code('12025551234'));

        // Unknown prefix returns null
        $this->assertNull(PhoneUtils::find_country_code('7654321'));    // Russia not in PHONE_RULES
        $this->assertNull(PhoneUtils::find_country_code('999999999'));
    }

    // -------------------------------------------------------------------------
    // validate_phone_format
    // -------------------------------------------------------------------------

    /** @return array<string, array{string, bool, string|null}> */
    public static function phoneFormatProvider(): array
    {
        return [
            // Valid GCC numbers
            // Kuwait valid: testValidatePhoneFormatKuwaitValid (needs KWTSMS_TEST_NUMBER)
            'Saudi valid'          => ['966512345678', true, null],
            'UAE valid'             => ['971501234567', true, null],
            'Bahrain valid'         => ['97333123456', true, null],
            'Qatar valid'           => ['97433123456', true, null],
            'Oman valid'            => ['96891234567', true, null],

            // Invalid: wrong local length
            'Kuwait too short'      => ['9656123456', false, 'Invalid Kuwait number'],
            'Kuwait too long'       => ['965112345678', false, 'Invalid Kuwait number'],

            // Invalid: wrong mobile prefix
            'Kuwait bad prefix'     => ['96510000000', false, 'Invalid Kuwait mobile number'],
            'Saudi bad prefix'      => ['966412345678', false, 'Invalid Saudi Arabia mobile number'],

            // Lebanon has two valid lengths (7 and 8)
            'Lebanon 7-digit'       => ['9613456789', true, null],
            'Lebanon 8-digit'       => ['96170123456', true, null],

            // Numbers with no matching country code pass through
            'No country code'       => ['7654321', true, null],
            'Unknown prefix'        => ['999123456', true, null],

            // Other regions
            'UK valid'              => ['447911123456', true, null],
            'India valid'           => ['919876543210', true, null],
            'Egypt valid'           => ['201012345678', true, null],
        ];
    }

    /**
     * @dataProvider phoneFormatProvider
     */
    public function testValidatePhoneFormat(string $normalized, bool $expectedValid, ?string $errorContains): void
    {
        [$valid, $error] = PhoneUtils::validate_phone_format($normalized);

        $this->assertSame($expectedValid, $valid);

        if ($errorContains !== null) {
            $this->assertIsString($error);
            $this->assertStringContainsString($errorContains, $error);
        } else {
            $this->assertNull($error);
        }
    }

    public function testValidatePhoneFormatKuwaitValid(): void
    {
        [$valid, $error] = PhoneUtils::validate_phone_format($this->kwtTestNumber());

        $this->assertTrue($valid);
        $this->assertNull($error);
    }

    // -------------------------------------------------------------------------
    // validate_phone_input
    // -------------------------------------------------------------------------

    public function testValidatePhoneInputBasicErrors(): void
    {
        // Empty / blank
        [$valid, $err] = PhoneUtils::validate_phone_input('');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('required', $err);

        [$valid, $err] = PhoneUtils::validate_phone_input('   ');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('required', $err);

        // Email
        [$valid, $err] = PhoneUtils::validate_phone_input('admin@test.com');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('email address', $err);

        // No digits
        [$valid, $err] = PhoneUtils::validate_phone_input('call me');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('no digits found', $err);
    }

    public function testValidatePhoneInputLengthBounds(): void
    {
        // Too short (6 digits)
        [$valid, $err] = PhoneUtils::validate_phone_input('123456');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('too short', $err);

        // Min length: 7 digits with no known country code (Russia prefix not in PHONE_RULES)
        [$valid, $err, $norm] = PhoneUtils::validate_phone_input('7654321');
        $this->assertTrue($valid);
        $this->assertNull($err);
        $this->assertSame('7654321', $norm);

        // Too long (16 digits)
        [$valid, $err] = PhoneUtils::validate_phone_input('7654321987654321');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('too long', $err);

        // Max length: 15 digits with no known country code
        [$valid, $err, $norm] = PhoneUtils::validate_phone_input('765432198765432');
        $this->assertTrue($valid);
        $this->assertNull($err);
        $this->assertSame('765432198765432', $norm);
    }

    /** @return array<string, array{string}> */
    public static function kuwaitInputVariantProvider(): array
    {
        return [
            'plus space dash'          => ['plus-space-dash'],
            'arabic-indic plus space'  => ['arabic-plus-space'],
            'trunk zero after code'    => ['trunk0'],
        ];
    }

    /**
     * Valid Kuwait number in several input formats (country-specific check, trunk 0 strip).
     *
     * @dataProvider kuwaitInputVariantProvider
     */
    public function testValidatePhoneInputKuwaitVariants(string $kind): void
    {
        $n = $this->kwtTestNumber();
        [$valid, $err, $norm] = PhoneUtils::validate_phone_input($this->kwtTestNumberVariant($kind));
        $this->assertTrue($valid);
        $this->assertNull($err);
        $this->assertSameNumber($n, $norm);
    }

    public function testValidatePhoneInputCountryRules(): void
    {
        // Invalid Kuwait: wrong length
        [$valid, $err] = PhoneUtils::validate_phone_input('96512345678901'); // 14 digits total
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('Invalid Kuwait number', $err);

        // Invalid Kuwait: wrong mobile prefix
        [$valid, $err] = PhoneUtils::validate_phone_input('965 1234 5678');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('Invalid Kuwait mobile number', $err);

        // Valid Saudi number
        [$valid, $err, $norm] = PhoneUtils::validate_phone_input('966501234567');
        $this->assertTrue($valid);
        $this->assertNull($err);
        $this->assertSame('966501234567', $norm);

        // Invalid Saudi: wrong prefix
        [$valid, $err] = PhoneUtils::validate_phone_input('966401234567');
        $this->assertFalse($valid);
        $this->assertIsString($err);
        $this->assertStringContainsString('Invalid Saudi Arabia mobile number', $err);

        // Trunk digit stripping: local '0' prefix removed after country code match
        [$valid, $err, $norm] = PhoneUtils::validate_phone_input('966 055 123 4567');  // "0" trunk before 55
        $this->assertTrue($valid);
        $this->assertNull($err);
        $this->assertSame('966551234567', $norm);
        // Kuwait with trunk 0: testValidatePhoneInputKuwaitVariants ('trunk0')
    }
}
