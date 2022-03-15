<?php

namespace OpenSpout\Common\Helper;

use OpenSpout\Common\Exception\EncodingConversionException;
use OpenSpout\TestUsingResource;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EncodingHelperTest extends TestCase
{
    use TestUsingResource;

    public function dataProviderForTestGetBytesOffsetToSkipBOM(): array
    {
        return [
            ['csv_with_utf8_bom.csv', EncodingHelper::ENCODING_UTF8, 3],
            ['csv_with_utf16be_bom.csv', EncodingHelper::ENCODING_UTF16_BE, 2],
            ['csv_with_utf32le_bom.csv', EncodingHelper::ENCODING_UTF32_LE, 4],
            ['csv_with_encoding_utf16le_no_bom.csv', EncodingHelper::ENCODING_UTF16_LE, 0],
            ['csv_standard.csv', EncodingHelper::ENCODING_UTF8, 0],
        ];
    }

    /**
     * @dataProvider dataProviderForTestGetBytesOffsetToSkipBOM
     */
    public function testGetBytesOffsetToSkipBOM(string $fileName, string $encoding, int $expectedBytesOffset)
    {
        $resourcePath = $this->getResourcePath($fileName);
        $filePointer = fopen($resourcePath, 'r');

        $encodingHelper = new EncodingHelper(false, false);
        $bytesOffset = $encodingHelper->getBytesOffsetToSkipBOM($filePointer, $encoding);

        static::assertSame($expectedBytesOffset, $bytesOffset);
    }

    public function dataProviderForIconvOrMbstringUsage(): array
    {
        return [
            'with-iconv' => [true],
            'with-mbstring' => [false],
        ];
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     */
    public function testAttemptConversionToUTF8ShouldThrowIfConversionFailed(bool $shouldUseIconv)
    {
        $encodingHelper = new EncodingHelper($shouldUseIconv, !$shouldUseIconv);

        $this->expectException(EncodingConversionException::class);

        $encodingHelper->attemptConversionToUTF8('input', uniqid('not_a_charset'));
    }

    public function testAttemptConversionToUTF8ShouldThrowIfConversionNotSupported()
    {
        $encodingHelper = new EncodingHelper(false, false);

        $this->expectException(EncodingConversionException::class);

        $encodingHelper->attemptConversionToUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     */
    public function testAttemptConversionToUTF8ShouldReturnReencodedString(bool $shouldUseIconv)
    {
        $encodingHelper = new EncodingHelper($shouldUseIconv, !$shouldUseIconv);

        $encodedString = iconv(EncodingHelper::ENCODING_UTF8, EncodingHelper::ENCODING_UTF16_LE, 'input');
        $decodedString = $encodingHelper->attemptConversionToUTF8($encodedString, EncodingHelper::ENCODING_UTF16_LE);

        static::assertSame('input', $decodedString);
    }

    public function testAttemptConversionToUTF8ShouldBeNoopWhenTargetIsUTF8()
    {
        $encodingHelper = new EncodingHelper(false, false);

        static::assertSame('input', $encodingHelper->attemptConversionToUTF8('input', EncodingHelper::ENCODING_UTF8));
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     */
    public function testAttemptConversionFromUTF8ShouldThrowIfConversionFailed(bool $shouldUseIconv)
    {
        $encodingHelper = new EncodingHelper($shouldUseIconv, !$shouldUseIconv);

        $this->expectException(EncodingConversionException::class);

        $encodingHelper->attemptConversionFromUTF8('input', uniqid('not_a_charset'));
    }

    public function testAttemptConversionFromUTF8ShouldThrowIfConversionNotSupported()
    {
        $encodingHelper = new EncodingHelper(false, false);

        $this->expectException(EncodingConversionException::class);

        $encodingHelper->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
    }

    /**
     * @dataProvider dataProviderForIconvOrMbstringUsage
     */
    public function testAttemptConversionFromUTF8ShouldReturnReencodedString(bool $shouldUseIconv)
    {
        $encodingHelper = new EncodingHelper($shouldUseIconv, !$shouldUseIconv);

        $encodedString = $encodingHelper->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF16_LE);
        $encodedStringWithIconv = iconv(EncodingHelper::ENCODING_UTF8, EncodingHelper::ENCODING_UTF16_LE, 'input');

        static::assertSame($encodedStringWithIconv, $encodedString);
    }

    public function testAttemptConversionFromUTF8ShouldBeNoopWhenTargetIsUTF8()
    {
        $encodingHelper = new EncodingHelper(false, false);

        static::assertSame('input', $encodingHelper->attemptConversionFromUTF8('input', EncodingHelper::ENCODING_UTF8));
    }
}
