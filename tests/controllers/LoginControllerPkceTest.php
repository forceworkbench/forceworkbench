<?php

use PHPUnit\Framework\TestCase;

/**
 * Minimal stand-in for WorkbenchConfig that only implements the methods
 * LoginController actually calls, so tests can control config values
 * without touching config/overrides.php or env vars.
 */
class FakeWorkbenchConfig {
    private $values;

    public function __construct($values) {
        $this->values = $values;
    }

    public function value($key) {
        return $this->values[$key] ?? null;
    }

    public function valueOrElse($key, $otherwise) {
        return $this->values[$key] ?? $otherwise;
    }
}

class LoginControllerPkceTest extends TestCase {

    private static $oauthConfigs = [
        'login.salesforce.com' => [
            'label' => 'Production',
            'key' => 'test-client-id',
            'secret' => 'test-client-secret',
        ],
    ];

    private function newController($overrides = []) {
        WorkbenchConfig::set(new FakeWorkbenchConfig(array_merge([
            'defaultLoginType' => 'ui',
            'defaultInstance' => 'login',
            'defaultApiVersion' => '66.0',
            'oauthConfigs' => self::$oauthConfigs,
            'oauthRequired' => false,
            'oauthPkceEnabled' => true,
            'termsFile' => '',
        ], $overrides)));

        return new LoginController();
    }

    protected function setUp(): void {
        $_REQUEST = [];
        $_SESSION = [];
        $_COOKIE = [];
        $_POST = [];
        $_SERVER['HTTP_HOST'] = 'workbench.example.com';
        $_SERVER['PHP_SELF'] = '/login.php';
        $_SERVER['SCRIPT_NAME'] = '/login.php';
    }

    protected function tearDown(): void {
        WorkbenchConfig::destroy();
    }

    private function invokePrivate($object, $method, $args = []) {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($object, $args);
    }

    public function testGeneratePkceVerifierIsUrlSafeAndWithinRfcLength() {
        $controller = $this->newController();
        $verifier = $this->invokePrivate($controller, 'generatePkceVerifier');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9\-_]+$/', $verifier);
        $this->assertGreaterThanOrEqual(43, strlen($verifier));
        $this->assertLessThanOrEqual(128, strlen($verifier));
    }

    public function testGeneratePkceVerifierIsRandomAcrossCalls() {
        $controller = $this->newController();
        $first = $this->invokePrivate($controller, 'generatePkceVerifier');
        $second = $this->invokePrivate($controller, 'generatePkceVerifier');

        $this->assertNotEquals($first, $second);
    }

    public function testDerivePkceChallengeMatchesKnownRfc7636Vector() {
        // Test vector from RFC 7636 Appendix B.
        $controller = $this->newController();
        $verifier = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
        $challenge = $this->invokePrivate($controller, 'derivePkceChallenge', [$verifier]);

        $this->assertSame('E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM', $challenge);
    }

    public function testBase64UrlEncodeHasNoPaddingOrUnsafeChars() {
        $controller = $this->newController();
        // Bytes chosen so base64 output includes both '+' and '/' and padding.
        $encoded = $this->invokePrivate($controller, 'base64UrlEncode', [base64_decode('/+8=')]);

        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    public function testGeneratePkceKeyIsUniquePerCall() {
        $controller = $this->newController();
        $first = $this->invokePrivate($controller, 'generatePkceKey');
        $second = $this->invokePrivate($controller, 'generatePkceKey');

        $this->assertNotEquals($first, $second);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $first);
    }

    public function testOauthRedirectStoresVerifierInSessionWhenPkceEnabled() {
        $controller = $this->newController(['oauthPkceEnabled' => true]);
        $pkceKey = $this->invokePrivate($controller, 'generatePkceKey');

        $this->invokePrivate($controller, 'oauthRedirect', ['login.salesforce.com', 'irrelevant-state', $pkceKey]);

        $this->assertArrayHasKey($pkceKey, $_SESSION['oauth']['pkceVerifiers']);
        $this->assertNotEmpty($_SESSION['oauth']['pkceVerifiers'][$pkceKey]);
    }

    public function testOauthRedirectDoesNotStoreVerifierWhenPkceDisabled() {
        $controller = $this->newController(['oauthPkceEnabled' => false]);
        $pkceKey = $this->invokePrivate($controller, 'generatePkceKey');

        $this->invokePrivate($controller, 'oauthRedirect', ['login.salesforce.com', 'irrelevant-state', $pkceKey]);

        $this->assertArrayNotHasKey('pkceVerifiers', $_SESSION['oauth'] ?? []);
    }

    public function testOauthProcessLoginThrowsWhenPkceKeyMissingAndPkceEnabled() {
        $controller = $this->newController(['oauthPkceEnabled' => true]);

        $this->expectException(WorkbenchAuthenticationException::class);
        $this->invokePrivate($controller, 'oauthProcessLogin', ['some-code', 'login.salesforce.com', '66.0', 'select.php', null]);
    }

    public function testOauthProcessLoginThrowsWhenPkceKeyUnknownAndPkceEnabled() {
        $controller = $this->newController(['oauthPkceEnabled' => true]);
        $_SESSION['oauth']['pkceVerifiers']['some-other-key'] = 'some-verifier';

        $this->expectException(WorkbenchAuthenticationException::class);
        $this->invokePrivate($controller, 'oauthProcessLogin', ['some-code', 'login.salesforce.com', '66.0', 'select.php', 'unknown-key']);
    }

}
