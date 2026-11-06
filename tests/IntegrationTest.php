<?php

namespace KwtSMS\Tests;

use KwtSMS\KwtSMS;
use PHPUnit\Framework\TestCase;

// 2026-11-05 08:39 +03: live sends and validations use only KWTSMS_TEST_NUMBER via the
// TestNumber trait (skip when unset), never a hardcoded sample number, int-testnumber.
class IntegrationTest extends TestCase
{
    use TestNumber;

    /** @var KwtSMS */
    private $client;

    protected function setUp(): void
    {
        // Integration tests run if credentials provided, automatically setting test_mode=True
        $this->client = KwtSMS::from_env();
    }

    private function isConfigured(): bool
    {
        return !empty(getenv('KWTSMS_USERNAME'));
    }

    public function testVerifyRequiresCredentials(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided. Skipping live API validation.');
        }

        [$ok, $balance, $err] = $this->client->verify();

        $this->assertTrue($ok);
        $this->assertIsFloat($balance);
        $this->assertGreaterThanOrEqual(0.0, $balance);
        $this->assertSame('', $err);
    }

    public function testWrongCredentials(): void
    {
        $badClient = new KwtSMS('php_username', 'php_password_invalid', 'KWT-SMS', true);
        [$ok, $bal, $err] = $badClient->verify();

        $this->assertFalse($ok);
        $this->assertSame(0.0, $bal);
        $this->assertStringContainsString('These are your API credentials', $err);
    }

    public function testSendToValidKuwaitNumber(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        // Test mode is forced on via from_env/setup usually. Let's explicitly construct memory-first test mode here.
        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true // Hardcode true safety
        );

        $result = $testClient->send($this->kwtTestNumber(), 'Integration Test Hello API');

        $this->assertSame('OK', $result['result'], 'Send request failed: ' . json_encode($result));
        $this->assertArrayHasKey('msg-id', $result);
    }

    public function testSendInvalidNumberFormat(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->send('abcd', 'Bad letters everywhere');

        $this->assertSame('ERROR', $result['result']);
        $this->assertSame('ERR006', $result['code']);

        // Ensure invalid rejected array
        $this->assertArrayHasKey('invalid', $result);
        $this->assertSame('abcd', $result['invalid'][0]['number']);
        $this->assertStringContainsString('no digits', $result['invalid'][0]['error']);
    }

    public function testSendMixedNumbers(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        // Explicitly in test mode
        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true // Hardcode true safety
        );

        $result = $testClient->send([$this->kwtTestNumber(), 'admin@example.com', '123'], 'Testing partial success');

        $this->assertSame('OK', $result['result']);

        $this->assertArrayHasKey('invalid', $result);
        $this->assertCount(2, $result['invalid']);
        $this->assertSame('admin@example.com', $result['invalid'][0]['number']);
        $this->assertSame('123', $result['invalid'][1]['number']);
    }

    public function testSend7PageEnglishMessageSucceeds(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true
        );

        // 7 pages GSM-7: 153 chars/page * 7 = 1071 chars
        $message = str_repeat('A', 1071);
        $result = $testClient->send($this->kwtTestNumber(), $message);

        $this->assertSame('OK', $result['result'], '7-page English should succeed: ' . json_encode($result));
    }

    public function testSend8PageEnglishMessageFails(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true
        );

        // 8 pages GSM-7: 153 chars/page * 8 = 1224 chars
        $message = str_repeat('A', 1224);
        $result = $testClient->send($this->kwtTestNumber(), $message);

        $this->assertSame('ERROR', $result['result'], '8-page English should fail: ' . json_encode($result));
        $this->assertSame('ERR012', $result['code']);
    }

    public function testSend7PageArabicMessageSucceeds(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true
        );

        // 7 pages UCS-2: 67 chars/page * 7 = 469 Arabic chars
        $message = str_repeat("\xD8\xA7", 469); // Arabic letter alef repeated
        $result = $testClient->send($this->kwtTestNumber(), $message);

        $this->assertSame('OK', $result['result'], '7-page Arabic should succeed: ' . json_encode($result));
    }

    public function testSend8PageArabicMessageFails(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true
        );

        // 8 pages UCS-2: 67 chars/page * 8 = 536 Arabic chars
        $message = str_repeat("\xD8\xA7", 536); // Arabic letter alef repeated
        $result = $testClient->send($this->kwtTestNumber(), $message);

        $this->assertSame('ERROR', $result['result'], '8-page Arabic should fail: ' . json_encode($result));
        $this->assertSame('ERR012', $result['code']);
    }

    public function testSenderIdsList(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->senderids();
        $this->assertSame('OK', $result['result']);
        $this->assertIsArray($result['senderids']);
    }

    public function testCoverageList(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->coverage();
        $this->assertSame('OK', $result['result']);
        $this->assertIsArray($result['prefixes']);
    }

    // -----------------------------------------------------------------------
    // balance() and purchased()
    // -----------------------------------------------------------------------

    public function testBalanceReturnsAvailableCredits(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $balance = $this->client->balance();

        $this->assertIsFloat($balance);
        $this->assertGreaterThan(0.0, $balance);
    }

    public function testPurchasedReturnsTotalCredits(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        // balance() must be called first to populate purchased
        $this->client->balance();
        $purchased = $this->client->purchased();

        $this->assertIsFloat($purchased);
        $this->assertGreaterThan(0.0, $purchased);
    }

    public function testBalanceIsLessThanOrEqualToPurchased(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $balance = $this->client->balance();
        $purchased = $this->client->purchased();

        $this->assertLessThanOrEqual($purchased, $balance);
    }

    // -----------------------------------------------------------------------
    // validate()
    // -----------------------------------------------------------------------

    public function testValidateAllValid(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->validate([$this->kwtTestNumber(), '966551234567']);

        $this->assertSame(2, $result['nr']);
        $this->assertSame(2, $result['ok']);
        $this->assertSame(0, $result['er']);
        $this->assertEmpty($result['rejected']);
    }

    public function testValidateAllInvalid(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->validate(['abc', '123', 'admin@example.com']);

        $this->assertSame(3, $result['nr']);
        $this->assertSame(0, $result['ok']);
        $this->assertSame(3, $result['er']);
        $this->assertCount(3, $result['rejected']);
    }

    public function testValidateMixed(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->validate([$this->kwtTestNumber(), 'not-a-number', '966551234567']);

        $this->assertSame(3, $result['nr']);
        $this->assertSame(2, $result['ok']);
        $this->assertSame(1, $result['er']);
        $this->assertCount(1, $result['rejected']);
        $this->assertSame('not-a-number', $result['rejected'][0]['number']);
    }

    public function testValidateCommaString(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $result = $this->client->validate($this->kwtTestNumber() . ',abc');

        $this->assertSame(2, $result['nr']);
        $this->assertSame(1, $result['ok']);
        $this->assertSame(1, $result['er']);
    }

    public function testValidateResultsContainNormalizedNumber(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        $n = $this->kwtTestNumber();
        $result = $this->client->validate($n);

        $this->assertCount(1, $result['raw']);
        $this->assertTrue($result['raw'][0]['valid']);
        $this->assertSameNumber($n, $result['raw'][0]['normalized']);
    }

    // -----------------------------------------------------------------------
    // status() and dlr()
    // -----------------------------------------------------------------------

    public function testStatusWithTestModeMsgId(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        // Send a test-mode message to get a real msg-id
        $testClient = new KwtSMS(
            getenv('KWTSMS_USERNAME') ?: '',
            getenv('KWTSMS_PASSWORD') ?: '',
            'KWT-SMS',
            true
        );

        $sendResult = $testClient->send($this->kwtTestNumber(), 'Status test');
        $this->assertSame('OK', $sendResult['result'], 'Send failed: ' . json_encode($sendResult));

        $msgId = $sendResult['msg-id'];
        $status = $testClient->status($msgId);

        // Test-mode messages may return OK with a status, or an error code
        $this->assertArrayHasKey('result', $status);
    }

    public function testStatusWithFakeMsgId(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        // 2026-11-07 00:01 +03: a well-formed (32 hex) id that does not exist; a malformed id gets ERR002 from the API, which is not what this tests (fix-php-itests)
        $result = $this->client->status('ffffffffffffffffffffffffffffffff');

        $this->assertSame('ERROR', $result['result']);
        // ERR029 = message not found, or ERR030 = stuck in queue
        $this->assertContains($result['code'], ['ERR029', 'ERR030']);
    }

    public function testDlrWithFakeMsgId(): void
    {
        if (!$this->isConfigured()) {
            $this->markTestSkipped('No real credentials provided.');
        }

        // 2026-11-07 00:01 +03: a well-formed (32 hex) id that does not exist; a malformed id gets ERR002 from the API, which is not what this tests (fix-php-itests)
        $result = $this->client->dlr('ffffffffffffffffffffffffffffffff');

        $this->assertSame('ERROR', $result['result']);
        // ERR019 = no reports, ERR020 = not found, ERR021 = not available yet
        $this->assertContains($result['code'], ['ERR019', 'ERR020', 'ERR021']);
    }
}
