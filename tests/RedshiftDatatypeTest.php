<?php
namespace Keboola\DataypeTest;

use Keboola\Datatype\Definition\Exception\InvalidCompressionException;
use Keboola\Datatype\Definition\Exception\InvalidLengthException;
use Keboola\Datatype\Definition\Exception\InvalidOptionException;
use Keboola\Datatype\Definition\Exception\InvalidTypeException;
use Keboola\Datatype\Definition\Redshift;

class RedshiftDatatypeTest extends \PHPUnit_Framework_TestCase
{
    public function testValid()
    {
        new Redshift("VARCHAR", ["length" => "50"]);
    }

    public function testInvalidType()
    {
        try {
            new Redshift("UNKNOWN");
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidTypeException::class, get_class($e));
        }
    }

    public function testValidNumericLengths()
    {
        new Redshift("numeric");
        new Redshift("NUMERIC");
        new Redshift("NUMERIC", ["length" => ""]);
        new Redshift("INT", ["length" => ""]);
        new Redshift("NUMERIC", ["length" => "37,0"]);
        new Redshift("NUMERIC", ["length" => "37,37"]);
        new Redshift("NUMERIC", ["length" => "37"]);
        new Redshift("REAL", ["length" => ""]);
        new Redshift("FLOAT4", ["length" => "4"]);
        new Redshift("FLOAT8", ["length" => ""]);
        new Redshift("DOUBLE PRECISION", ["length" => "8"]);
        new Redshift("FLOAT", ["length" => "8"]);
    }

    /**
     * @dataProvider invalidNumericLengths
     * @param $length
     * @param $type
     */
    public function testInvalidNumericLengths($type, $length)
    {
        try {
            new Redshift($type, ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testValidDateLengths()
    {
        new Redshift("date");
        new Redshift("DATE", ["length" => ""]);
        new Redshift("DATE", ["length" => "4"]);
        new Redshift("TIMESTAMP", ["length" => ""]);
        new Redshift("TIMESTAMPTZ", ["length" => ""]);
        new Redshift("TIMESTAMP WITHOUT TIME ZONE", ["length" => "8"]);
        new Redshift("TIMESTAMP WITH TIME ZONE", ["length" => "8"]);
    }

    /**
     * @dataProvider invalidDateLengths
     * @param $type
     * @param $length
     */
    public function testInvalidDateLengths($type, $length)
    {
        try {
            new Redshift($type, ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testValidVarcharLengths()
    {
        new Redshift("varchar");
        new Redshift("VARCHAR");
        new Redshift("VARCHAR", ["length" => ""]);
        new Redshift("VARCHAR", ["length" => "1"]);
        new Redshift("VARCHAR", ["length" => "65535"]);
    }

    /**
     * @dataProvider invalidVarcharLengths
     * @param $length
     */
    public function testInvalidVarcharLengths($length)
    {
        try {
            new Redshift("VARCHAR", ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testValidCharLengths()
    {
        new Redshift("char");
        new Redshift("CHAR");
        new Redshift("CHAR", ["length" => "1"]);
        new Redshift("CHAR", ["length" => "4096"]);
    }

    /**
     * @dataProvider invalidCharLengths
     * @param $length
     */
    public function testInvalidCharLengths($length)
    {
        try {
            new Redshift("CHAR", ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testValidBooleanLengths()
    {
        new Redshift("bool");
        new Redshift("BOOL");
        new Redshift("BOOLEAN", ["length" => "1"]);
    }

    /**
     * @dataProvider invalidBooleanLengths
     * @param $length
     */
    public function testInvalidBooleanLengths($length)
    {
        try {
            new Redshift("BOOL", ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testValidCompressions()
    {
        new Redshift("VARCHAR", ["compression" => "RAW"]);
        new Redshift("VARCHAR", ["compression" => "raw"]);
        new Redshift("VARCHAR", ["compression" => "BYTEDICT"]);
        new Redshift("INT", ["compression" => "DELTA"]);
        new Redshift("INT", ["compression" => "DELTA32K"]);
        new Redshift("VARCHAR", ["compression" => "LZO"]);
        new Redshift("BIGINT", ["compression" => "MOSTLY8"]);
        new Redshift("BIGINT", ["compression" => "MOSTLY16"]);
        new Redshift("BIGINT", ["compression" => "MOSTLY32"]);
        new Redshift("VARCHAR", ["compression" => "RUNLENGTH"]);
        new Redshift("VARCHAR", ["compression" => "TEXT255"]);
        new Redshift("VARCHAR", ["compression" => "TEXT32K"]);
        new Redshift("VARCHAR", ["compression" => "ZSTD"]);
    }

    /**
     * @dataProvider invalidCompressions
     * @param $type
     * @param $compression
     */
    public function testInvalidCompressions($type, $compression)
    {
        try {
            new Redshift($type, ["compression" => $compression]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidCompressionException::class, get_class($e));
        }
    }

    public function testInvalidOption()
    {
        try {
            new Redshift("NUMERIC", ["myoption" => "value"]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidOptionException::class, get_class($e));
        }
    }

    public function testSQLDefinition()
    {
        $datatype = new Redshift("VARCHAR", ["length" => "50", "nullable" => true, "compression" => "ZSTD"]);
        $this->assertEquals("VARCHAR(50) ENCODE ZSTD", $datatype->getSQLDefinition());

        $definition = new Redshift("NUMERIC", ["length" => ""]);
        $this->assertTrue($definition->getSQLDefinition() === "NUMERIC");

        $definition = new Redshift("TIMESTAMPTZ", ["length" => ""]);
        $this->assertTrue($definition->getSQLDefinition() === "TIMESTAMPTZ");

        $definition = new Redshift("TIMESTAMPTZ", ["length" => "8"]);
        $this->assertTrue($definition->getSQLDefinition() === "TIMESTAMPTZ");

        $definition = new Redshift("DATE", ["length" => "4"]);
        $this->assertTrue($definition->getSQLDefinition() === "DATE");

        $definition = new Redshift("DATE");
        $this->assertTrue($definition->getSQLDefinition() === "DATE");

        $definition = new Redshift("FLOAT8", ["length" => "8"]);
        $this->assertTrue($definition->getSQLDefinition() === "FLOAT8");

        $definition = new Redshift("REAL", ["length" => "4"]);
        $this->assertTrue($definition->getSQLDefinition() === "REAL");

        $definition = new Redshift("BOOLEAN", ["length" => "1"]);
        $this->assertTrue($definition->getSQLDefinition() === "BOOLEAN");
    }

    public function testToArray()
    {
        $datatype = new Redshift("VARCHAR");
        $this->assertEquals(
            ["type" => "VARCHAR", "length" => null, "nullable" => true, "compression" => null],
            $datatype->toArray()
        );

        $datatype = new Redshift("VARCHAR", ["length" => "50", "nullable" => false, "compression" => "ZSTD"]);
        $this->assertEquals(
            ["type" => "VARCHAR", "length" => "50", "nullable" => false, "compression" => "ZSTD"],
            $datatype->toArray()
        );
    }

    public function testToMetadata()
    {
        $datatype = new Redshift("VARCHAR", ["length" => "50", "nullable" => false, "default" => "", "compression" => "ZSTD"]);

        $md = $datatype->toMetadata();
        $hasCompression = false;
        foreach ($md as $mdat) {
            $this->assertArrayHasKey("key", $mdat);
            if ($mdat["key"] === "KBC.datatype.compression") {
                $this->assertEquals("ZSTD", $mdat["value"]);
                $hasCompression = true;
            }
        }
        if (!$hasCompression) {
            $this->fail("Redshift datatype metadata should produce compression data if present");
        }

        $datatype = new Redshift("VARCHAR");
        $md = $datatype->toMetadata();
        foreach ($md as $mdat) {
            $this->assertArrayHasKey("key", $mdat);
            if ($mdat["key"] === "KBC.datatyp.compression") {
                $this->fail("Redshift datatype should not produce compression metadata if compression is not set");
            }
        }
    }

    public function testBasetypes()
    {
        foreach (Redshift::TYPES as $type) {
            $basetype = (new Redshift($type))->getBasetype();
            switch ($type) {
                case "SMALLINT":
                case "INT2":
                case "INTEGER":
                case "INT":
                case "INT4":
                case "BIGINT":
                case "INT8":
                    $this->assertEquals("INTEGER", $basetype);
                    break;
                case "DECIMAL":
                case "NUMERIC":
                    $this->assertEquals("NUMERIC", $basetype);
                    break;
                case "REAL":
                case "FLOAT4":
                case "DOUBLE PRECISION":
                case "FLOAT8":
                case "FLOAT":
                    $this->assertEquals("FLOAT", $basetype);
                    break;
                case "BOOLEAN":
                case "BOOL":
                    $this->assertEquals("BOOLEAN", $basetype);
                    break;
                case "DATE":
                    $this->assertEquals("DATE", $basetype);
                    break;
                case "TIMESTAMP":
                case "TIMESTAMP WITHOUT TIME ZONE":
                case "TIMESTAMPTZ":
                case "TIMESTAMP WITH TIME ZONE":
                    $this->assertEquals("TIMESTAMP", $basetype);
                    break;
                default:
                    $this->assertEquals("STRING", $basetype);
                    break;
            }
        }
    }

    public function invalidNumericLengths()
    {
        return [
            ["NUMERIC", "notANumber"],
            ["NUMERIC", "0,0"],
            ["NUMERIC", "38,0"],
            ["NUMERIC", "-10,-5"],
            ["NUMERIC", "-5,-10"],
            ["NUMERIC", "37,a"],
            ["NUMERIC", "a,37"],
            ["NUMERIC", "a,a"],
            ["INT2", "notANumber"],
            ["INT2", "0,0"],
            ["INT2", "2,0"],
            ["INT2", "-10"],
            ["INT2", "4"],
            ["INTEGER", "notANumber"],
            ["INTEGER", "0,0"],
            ["INTEGER", "2,0"],
            ["INTEGER", "-10"],
            ["INTEGER", "8"],
            ["BIGINT", "notANumber"],
            ["BIGINT", "0,0"],
            ["BIGINT", "2,0"],
            ["BIGINT", "-10"],
            ["BIGINT", "4"],
            ["REAL", "notANumber"],
            ["REAL", "0,0"],
            ["REAL", "2,0"],
            ["REAL", "-10"],
            ["REAL", "8"],
            ["FLOAT", "notANumber"],
            ["FLOAT", "0,0"],
            ["FLOAT", "2,0"],
            ["FLOAT", "-10"],
            ["FLOAT", "4"]
        ];
    }

    public function invalidVarcharLengths()
    {
        return [
            ["a"],
            ["0"],
            ["65536"],
            ["-1"]
        ];
    }

    public function invalidCharLengths()
    {
        return [
            ["a"],
            ["0"],
            ["4097"],
            ["-1"]
        ];
    }

    public function invalidBooleanLengths()
    {
        return [
            ["a"],
            ["0"],
            ["4097"],
            ["-1"]
        ];
    }

    public function invalidDateLengths()
    {
        return [
            ["DATE", "a"],
            ["DATE", "-1"],
            ["DATE", "0"],
            ["DATE", "2"],
            ["DATE", "10"],
            ["DATE", "4,2"],
            ["TIMESTAMP", "a"],
            ["TIMESTAMP", "-1"],
            ["TIMESTAMP", "0"],
            ["TIMESTAMP", "2"],
            ["TIMESTAMP", "10"],
            ["TIMESTAMP", "8,2"]
        ];
    }

    public function invalidCompressions()
    {
        return [
            ["BOOLEAN", "BYTEDICT"],
            ["VARCHAR", "DELTA"],
            ["VARCHAR", "DELTA32K"],
            ["VARCHAR", "MOSTLY8"],
            ["VARCHAR", "MOSTLY16"],
            ["VARCHAR", "MOSTLY32"],
            ["NUMERIC", "TEXT255"],
            ["NUMERIC","TEXT32K"]
        ];
    }
}
