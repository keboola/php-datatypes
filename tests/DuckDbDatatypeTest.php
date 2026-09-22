<?php

declare(strict_types=1);

namespace Keboola\DatatypeTest;

use Generator;
use Keboola\Datatype\Definition\BaseType;
use Keboola\Datatype\Definition\DuckDb;
use Keboola\Datatype\Definition\Exception\InvalidLengthException;
use Keboola\Datatype\Definition\Exception\InvalidTypeException;
use PHPUnit\Framework\Attributes\DataProvider;

class DuckDbDatatypeTest extends BaseDatatypeTestCase
{
    public static function getTestedClass(): string
    {
        return DuckDb::class;
    }

    public function testTypeIsUppercasedSoMetadataIsComparable(): void
    {
        self::assertSame(DuckDb::TYPE_VARCHAR, (new DuckDb('varchar'))->getType());
    }

    public function testUnknownTypeIsRejected(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('"STRUCT" is not a valid type.');

        new DuckDb('STRUCT');
    }

    public function testSqlDefinitionCarriesLengthAndNullability(): void
    {
        $definition = new DuckDb(DuckDb::TYPE_DECIMAL, ['length' => '18,4', 'nullable' => false]);

        self::assertSame('DECIMAL(18,4) NOT NULL', $definition->getSQLDefinition());
    }

    public function testLengthOnATypeThatTakesNoneIsRejected(): void
    {
        $this->expectException(InvalidLengthException::class);
        $this->expectExceptionMessage('"BIGINT" does not take a length.');

        new DuckDb(DuckDb::TYPE_BIGINT, ['length' => '10']);
    }

    /**
     * @param string|int|null $length
     */
    #[DataProvider('provideInvalidDecimalLengths')]
    public function testInvalidDecimalLengthIsRejected($length): void
    {
        $this->expectException(InvalidLengthException::class);

        new DuckDb(DuckDb::TYPE_DECIMAL, ['length' => $length]);
    }

    /**
     * @return Generator<string, array{string}>
     */
    public static function provideInvalidDecimalLengths(): Generator
    {
        yield 'precision only' => ['18'];
        yield 'not numeric' => ['x,4'];
        yield 'precision above the DuckDB maximum' => ['39,4'];
        yield 'scale above precision' => ['4,18'];
        yield 'negative scale' => ['18,-1'];
    }

    public function testDecimalAtTheMaximumPrecisionIsAccepted(): void
    {
        $definition = new DuckDb(DuckDb::TYPE_DECIMAL, ['length' => '38,0']);

        self::assertSame('DECIMAL(38,0)', $definition->getSQLDefinition());
    }

    public function testBasetypeOfAnUnmappedTypeFallsBackToString(): void
    {
        self::assertSame(BaseType::STRING, (new DuckDb(DuckDb::TYPE_UUID))->getBasetype());
        self::assertSame(BaseType::STRING, (new DuckDb(DuckDb::TYPE_INTERVAL))->getBasetype());
    }

    public function testUnsignedIntegersMapToTheIntegerBasetype(): void
    {
        self::assertSame(BaseType::INTEGER, (new DuckDb(DuckDb::TYPE_UBIGINT))->getBasetype());
    }

    public static function provideTestGetTypeByBasetype(): Generator
    {
        yield ['basetype' => BaseType::BOOLEAN, 'expectedType' => DuckDb::TYPE_BOOLEAN];
        yield ['basetype' => BaseType::DATE, 'expectedType' => DuckDb::TYPE_DATE];
        yield ['basetype' => BaseType::FLOAT, 'expectedType' => DuckDb::TYPE_DOUBLE];
        yield ['basetype' => BaseType::INTEGER, 'expectedType' => DuckDb::TYPE_BIGINT];
        yield ['basetype' => BaseType::NUMERIC, 'expectedType' => DuckDb::TYPE_DECIMAL];
        yield ['basetype' => BaseType::STRING, 'expectedType' => DuckDb::TYPE_VARCHAR];
        yield ['basetype' => BaseType::TIMESTAMP, 'expectedType' => DuckDb::TYPE_TIMESTAMP];
        yield 'unknown' => ['basetype' => 'FOO', 'expectedType' => null, 'expectToFail' => true];
    }

    public static function provideTestGetDefinitionForBasetype(): Generator
    {
        yield [
            'basetype' => BaseType::BOOLEAN,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_BOOLEAN),
        ];
        yield [
            'basetype' => BaseType::DATE,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_DATE),
        ];
        yield [
            'basetype' => BaseType::FLOAT,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_DOUBLE),
        ];
        yield [
            'basetype' => BaseType::INTEGER,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_BIGINT),
        ];
        yield [
            'basetype' => BaseType::NUMERIC,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_DECIMAL, ['length' => '18,3']),
        ];
        yield [
            'basetype' => BaseType::STRING,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_VARCHAR),
        ];
        yield [
            'basetype' => BaseType::TIMESTAMP,
            'expectedColumnDefinition' => new DuckDb(DuckDb::TYPE_TIMESTAMP),
        ];
        yield 'unknown' => [
            'basetype' => 'FOO',
            'expectedColumnDefinition' => null,
            'expectToFail' => true,
        ];
    }
}
