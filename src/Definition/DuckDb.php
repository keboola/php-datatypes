<?php

declare(strict_types=1);

namespace Keboola\Datatype\Definition;

use Keboola\Datatype\Definition\Exception\InvalidLengthException;
use Keboola\Datatype\Definition\Exception\InvalidOptionException;
use Keboola\Datatype\Definition\Exception\InvalidTypeException;

/**
 * DuckDB column types, as DuckLake tables carry them.
 *
 * The list is the subset of DuckDB's logical types that survives a round trip through a
 * DuckLake catalog: nested and engine-internal types (STRUCT, MAP, UNION, ENUM, BIT) are
 * deliberately absent because Storage has no column metadata to describe them and the
 * driver's CREATE grammar accepts only a bare type name with an optional numeric
 * parameter list. Aliases DuckDB resolves itself (INT4, INT8, …) are not offered either;
 * one spelling per type keeps the metadata the driver reports back comparable.
 */
class DuckDb extends Common
{
    public const TYPE_BOOLEAN = 'BOOLEAN';

    public const TYPE_TINYINT = 'TINYINT';
    public const TYPE_SMALLINT = 'SMALLINT';
    public const TYPE_INTEGER = 'INTEGER';
    public const TYPE_BIGINT = 'BIGINT';
    public const TYPE_HUGEINT = 'HUGEINT';
    public const TYPE_UTINYINT = 'UTINYINT';
    public const TYPE_USMALLINT = 'USMALLINT';
    public const TYPE_UINTEGER = 'UINTEGER';
    public const TYPE_UBIGINT = 'UBIGINT';

    public const TYPE_DECIMAL = 'DECIMAL';
    public const TYPE_REAL = 'REAL';
    public const TYPE_DOUBLE = 'DOUBLE';

    public const TYPE_VARCHAR = 'VARCHAR';
    public const TYPE_BLOB = 'BLOB';
    public const TYPE_UUID = 'UUID';

    public const TYPE_DATE = 'DATE';
    public const TYPE_TIME = 'TIME';
    public const TYPE_TIMESTAMP = 'TIMESTAMP';
    public const TYPE_TIMESTAMPTZ = 'TIMESTAMPTZ';
    public const TYPE_INTERVAL = 'INTERVAL';

    public const TYPES = [
        self::TYPE_BOOLEAN,
        self::TYPE_TINYINT,
        self::TYPE_SMALLINT,
        self::TYPE_INTEGER,
        self::TYPE_BIGINT,
        self::TYPE_HUGEINT,
        self::TYPE_UTINYINT,
        self::TYPE_USMALLINT,
        self::TYPE_UINTEGER,
        self::TYPE_UBIGINT,
        self::TYPE_DECIMAL,
        self::TYPE_REAL,
        self::TYPE_DOUBLE,
        self::TYPE_VARCHAR,
        self::TYPE_BLOB,
        self::TYPE_UUID,
        self::TYPE_DATE,
        self::TYPE_TIME,
        self::TYPE_TIMESTAMP,
        self::TYPE_TIMESTAMPTZ,
        self::TYPE_INTERVAL,
    ];

    /** Only DECIMAL takes a parameter list; every other type here is fixed width. */
    public const TYPES_WITH_LENGTH = [
        self::TYPE_DECIMAL,
    ];

    public const MAX_DECIMAL_PRECISION = 38;

    private const DEFAULT_DECIMAL_LENGTH = '18,3';

    /**
     * @param array{length?:string|int|null, nullable?:bool, default?:string|null, description?:string|null} $options
     *
     * @throws InvalidTypeException
     * @throws InvalidLengthException
     * @throws InvalidOptionException
     */
    public function __construct(string $type, array $options = [])
    {
        $type = strtoupper($type);
        $this->validateType($type);
        $this->validateLength($type, $options['length'] ?? null);

        parent::__construct($type, $options);
    }

    public function getSQLDefinition(): string
    {
        $definition = $this->getType();
        if (!$this->isEmpty($this->getLength())) {
            $definition .= sprintf('(%s)', $this->getLength());
        }
        if (!$this->isNullable()) {
            $definition .= ' NOT NULL';
        }
        if ($this->getDefault() !== null) {
            $definition .= ' DEFAULT ' . $this->getDefault();
        }

        return $definition;
    }

    /**
     * @return array{type:string,length:string|null,nullable:bool,description?:string}
     */
    public function toArray(): array
    {
        $result = [
            'type' => $this->getType(),
            'length' => $this->getLength(),
            'nullable' => $this->isNullable(),
        ];
        if ($this->getDescription() !== null) {
            $result['description'] = $this->getDescription();
        }

        return $result;
    }

    public function getBasetype(): string
    {
        return match ($this->getType()) {
            self::TYPE_TINYINT,
            self::TYPE_SMALLINT,
            self::TYPE_INTEGER,
            self::TYPE_BIGINT,
            self::TYPE_HUGEINT,
            self::TYPE_UTINYINT,
            self::TYPE_USMALLINT,
            self::TYPE_UINTEGER,
            self::TYPE_UBIGINT => BaseType::INTEGER,
            self::TYPE_DECIMAL => BaseType::NUMERIC,
            self::TYPE_REAL, self::TYPE_DOUBLE => BaseType::FLOAT,
            self::TYPE_BOOLEAN => BaseType::BOOLEAN,
            self::TYPE_DATE => BaseType::DATE,
            self::TYPE_TIMESTAMP, self::TYPE_TIMESTAMPTZ => BaseType::TIMESTAMP,
            default => BaseType::STRING,
        };
    }

    public static function getTypeByBasetype(string $basetype): string
    {
        $basetype = strtoupper($basetype);

        return match ($basetype) {
            BaseType::BOOLEAN => self::TYPE_BOOLEAN,
            BaseType::DATE => self::TYPE_DATE,
            BaseType::FLOAT => self::TYPE_DOUBLE,
            BaseType::INTEGER => self::TYPE_BIGINT,
            BaseType::NUMERIC => self::TYPE_DECIMAL,
            BaseType::STRING => self::TYPE_VARCHAR,
            BaseType::TIMESTAMP => self::TYPE_TIMESTAMP,
            default => throw new InvalidTypeException(sprintf('Base type "%s" is not valid.', $basetype)),
        };
    }

    public static function getDefinitionForBasetype(string $basetype): DefinitionInterface
    {
        $type = self::getTypeByBasetype($basetype);

        return new self(
            $type,
            $type === self::TYPE_DECIMAL ? ['length' => self::DEFAULT_DECIMAL_LENGTH] : [],
        );
    }

    /**
     * @throws InvalidTypeException
     */
    private function validateType(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidTypeException(sprintf('"%s" is not a valid type.', $type));
        }
    }

    /**
     * @param string|int|null $length
     *
     * @throws InvalidLengthException
     */
    private function validateLength(string $type, $length = null): void
    {
        if ($this->isEmpty($length)) {
            return;
        }

        if (!in_array($type, self::TYPES_WITH_LENGTH, true)) {
            throw new InvalidLengthException(sprintf('"%s" does not take a length.', $type));
        }

        // DECIMAL(precision, scale); DuckDB caps precision at 38 and requires scale <= precision.
        $parts = array_map('trim', explode(',', (string) $length));
        if (count($parts) !== 2) {
            throw new InvalidLengthException('DECIMAL length must be "precision,scale".');
        }

        [$precision, $scale] = $parts;
        if (!is_numeric($precision) || !is_numeric($scale)) {
            throw new InvalidLengthException('DECIMAL precision and scale must be numeric.');
        }

        $precision = (int) $precision;
        $scale = (int) $scale;
        if ($precision < 1 || $precision > self::MAX_DECIMAL_PRECISION) {
            throw new InvalidLengthException(
                sprintf('DECIMAL precision must be between 1 and %d.', self::MAX_DECIMAL_PRECISION),
            );
        }
        if ($scale < 0 || $scale > $precision) {
            throw new InvalidLengthException('DECIMAL scale must be between 0 and the precision.');
        }
    }
}
