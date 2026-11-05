<?php

/**
 * FILE:      tests/TestNumber.php
 * CREATED:   2026-11-05 08:39 +03
 * TASK:      int-testnumber
 * AUTHOR:    M Alhassoun
 * WHAT:      Shared test helper trait: the test phone number and its format variants.
 * WHY:       Tests must not hardcode a phone number (sample numbers belong to real people).
 *            The number comes from the KWTSMS_TEST_NUMBER environment variable (an
 *            international Kuwait mobile, digits only, 965 plus 8 digits) and every test
 *            that needs one is skipped when it is unset or malformed.
 * USAGE:     KWTSMS_TEST_NUMBER=965XXXXXXXX vendor/bin/phpunit
 *            In a TestCase: `use TestNumber;` then $this->kwtTestNumber() or
 *            $this->kwtTestNumberVariant('kind'). Compare with assertSameNumber() so a
 *            failure never prints the number. Never log or echo the value.
 * DEPENDS:   phpunit/phpunit (TestCase::markTestSkipped, assertTrue).
 */

namespace KwtSMS\Tests;

trait TestNumber
{
    /** @var string Placeholder for fields only passed through unchanged (mocked echoes, webhooks). */
    public static $testNumberPlaceholder = '965XXXXXXXX';

    /**
     * KWTSMS_TEST_NUMBER, or skips the running test when it is unset or malformed.
     */
    protected function kwtTestNumber(): string
    {
        $n = trim((string) getenv('KWTSMS_TEST_NUMBER'));
        // [0-9], not \d: keep the check ASCII only.
        if (preg_match('/^965[0-9]{8}$/', $n) !== 1) {
            $this->markTestSkipped('KWTSMS_TEST_NUMBER not set (digits only, 965 plus 8 digits)');
        }
        return $n;
    }

    /**
     * A second, different number derived from the test number (last digit changed).
     * Mocked tests only: it must never reach the network.
     */
    protected function kwtOtherTestNumber(): string
    {
        $n = $this->kwtTestNumber();
        return substr($n, 0, -1) . (string) (((int) substr($n, -1) + 1) % 10);
    }

    /**
     * Latin digits to Arabic-Indic (U+0660-0669).
     */
    protected static function toArabicIndic(string $s): string
    {
        return strtr($s, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
            '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']);
    }

    /**
     * Latin digits to Extended Arabic-Indic / Persian (U+06F0-06F9).
     */
    protected static function toPersian(string $s): string
    {
        return strtr($s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /**
     * The test number written in the format named by $kind. Skips when unset.
     */
    protected function kwtTestNumberVariant(string $kind): string
    {
        $n = $this->kwtTestNumber();
        $cc = substr($n, 0, 3);
        $a = substr($n, 3, 4);
        $b = substr($n, 7);
        switch ($kind) {
            case 'plain':
                return $n;
            case 'plus':
                return '+' . $n;
            case '00':
                return '00' . $n;
            case '0':
                return '0' . $n;
            case 'padded':
                return '  ' . $n . '  ';
            case 'spaces':
                return $cc . ' ' . $a . ' ' . $b;
            case 'plus-spaces':
                return '+' . $cc . ' ' . $a . ' ' . $b;
            case 'plus-space-dash':
                return '+' . $cc . ' ' . $a . '-' . $b;
            case 'dashes':
                return $cc . '-' . $a . '-' . $b;
            case 'parens':
                return '(' . $cc . ') ' . $a . '-' . $b;
            case 'trunk0':
                return $cc . ' 0 ' . $a . ' ' . $b;
            case 'arabic':
                return self::toArabicIndic($n);
            case 'arabic-plus-space':
                return '+' . self::toArabicIndic($cc . ' ' . $a . $b);
            case 'persian':
                return self::toPersian($n);
            default:
                throw new \InvalidArgumentException('unknown variant kind: ' . $kind);
        }
    }

    /**
     * Assert equality without printing either value (they may hold the number).
     */
    protected function assertSameNumber(string $expected, ?string $actual): void
    {
        $this->assertTrue($expected === $actual, 'phone number does not match the expected value (values hidden)');
    }
}
