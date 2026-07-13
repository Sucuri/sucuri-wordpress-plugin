<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Regression test for a fatal error reported in production: some API error
 * responses return "messages" as a plain string instead of an array, and
 * handleResponse() used to implode() it directly. Under PHP 8 that raises an
 * uncaught TypeError ("implode(): Argument #2 ($array) must be of type
 * ?array, string given"), breaking any code path that calls it (e.g. saving
 * a post triggers SucuriScanFirewall::clearCacheHook() -> clearCache() ->
 * handleResponse()). normalizeMessages() guarantees implode() always
 * receives an array.
 */
final class ApiNormalizeMessagesTest extends TestCase
{
    /**
     * @param mixed $messages Raw "messages" value from an API response.
     * @return array Normalized value.
     */
    private function normalize($messages): array
    {
        $method = (new ReflectionClass('SucuriScanAPI'))->getMethod('normalizeMessages');
        $method->setAccessible(true);

        return $method->invoke(null, $messages);
    }

    public function testStringMessageIsWrappedInArray()
    {
        $this->assertSame(array('Something went wrong'), $this->normalize('Something went wrong'));
    }

    public function testArrayMessageIsReturnedUnchanged()
    {
        $messages = array('First error', 'Second error');

        $this->assertSame($messages, $this->normalize($messages));
    }

    public function testImplodingAStringMessageDoesNotThrow()
    {
        // Reproduces the exact crash: a malformed response with "messages" as
        // a string instead of an array must still be safely implode()-able.
        $msg = implode(".\x20", $this->normalize('Wrong API key'));

        $this->assertSame('Wrong API key', $msg);
    }
}
