<?php
namespace Keboola\DataypeTest;

use Keboola\Datatype\Definition\Exception\InvalidLengthException;
use Keboola\Datatype\Definition\Exception\InvalidOptionException;
use Keboola\Datatype\Definition\Exception\InvalidTypeException;
use Keboola\Datatype\Definition\Oracle;

class OracleDatatypeTest extends \PHPUnit_Framework_TestCase
{
    public function testValid()
    {
        new Oracle("VARCHAR", ["length" => "50"]);
    }

    public function testInvalidType()
    {
        try {
            new Oracle("UNKNOWN");
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidTypeException::class, get_class($e));
        }
    }

    public function testInvalidOption()
    {
        try {
            new Oracle("NUMBER", ["myoption" => "value"]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidOptionException::class, get_class($e));
        }
    }

    public function testValidNummberLengths()
    {
        new Oracle("number");
        new Oracle("NUMBER");
        new Oracle("NUMBER", ["length" => ""]);
        new Oracle("NUMBER", ["length" => "65,0"]);
        new Oracle("NUMBER", ["length" => "65"]);
        new Oracle("NUMBER", ["length" => "10,10"]);
    }

    /**
     * @dataProvider invalidNumericLengths
     * @param $length
     */
    public function testInvalidNumberLengths($length)
    {
        try {
            new Oracle("NUMBER", ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testValidVariableCharacterLengths()
    {
        new Oracle("varchar", ["length" => "1"]);
        new Oracle("VARCHAR", ["length" => "1"]);
        new Oracle("VARCHAR", ["length" => "4000"]);
    }

    public function testValidFixedCharacterLengths()
    {
        new Oracle("char");
        new Oracle("CHAR");
        new Oracle("CHAR", ["length" => ""]);
        new Oracle("CHAR", ["length" => "1"]);
        new Oracle("CHAR", ["length" => "255"]);
    }

    public function testVariableCharacterWithoutLength()
    {
        try {
            new Oracle("VARCHAR");
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    /**
     * @dataProvider invalidVariableCharacterLengths
     * @param $length
     */
    public function testInvalidVariableCharacterLengths($length)
    {
        try {
            new Oracle("VARCHAR", ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    /**
     * @dataProvider invalidFixedCharacterLengths
     * @param $length
     */
    public function testInvalidFixedCharacterLengths($length)
    {
        try {
            new Oracle("CHAR", ["length" => $length]);
            $this->fail("Exception not caught");
        } catch (\Exception $e) {
            $this->assertEquals(InvalidLengthException::class, get_class($e));
        }
    }

    public function testBasetypes()
    {
        foreach (Oracle::TYPES as $type) {
            if (in_array($type, ['VARCHAR', 'NVARCHAR', 'VARCHAR2', 'NVARCHAR2', 'CLOB', 'NCLOB', 'LONG'])) {
                $basetype = (new Oracle($type, ["length" => 255]))->getBasetype();
            } else {
                $basetype = (new Oracle($type))->getBasetype();
            }
            switch ($type) {
                case "NUMBER":
                    $this->assertEquals("NUMERIC", $basetype);
                    break;
                case "DATE":
                    $this->assertEquals("DATE", $basetype);
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
            ["notANumber"],
            ["0,0"],
            ["-10,-5"],
            ["-5,-10"],
            ["66,a"],
            ["a,66"],
            ["a,a"]
        ];
    }
    
    public function invalidIntegerLengths()
    {
        return [
            ["notANumber"],
            ["-1"],
            ["256"]
        ];
    }

    public function invalidFixedCharacterLengths()
    {
        return [
            ["a"],
            ["0"],
            ["256"],
            ["-1"]
        ];
    }


    public function invalidVariableCharacterLengths()
    {
        return [
            [""],
            ["a"],
            ["0"],
            ["4294967296"],
            ["-1"]
        ];
    }

    public function invalidVariableIntegerLengths()
    {
        return [
            ["-1"],
            ["256"],
            ["a"]
        ];
    }

    public function invalidFloatLengths()
    {
        return [
            ["notANumber"],
            ["0,0"],
            ["256,0"],
            ["-10,-5"],
            ["-5,-10"],
            ["256,a"],
            ["a,256"],
            ["a,a"],
            ["10,10"],
            ["256,256"]
        ];
    }
}
