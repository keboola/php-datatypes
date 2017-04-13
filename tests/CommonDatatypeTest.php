<?php
namespace Keboola\DataypeTest;

use Keboola\Datatype\Definition\Common;

class CommonDatatypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $datatype = new Common("VARCHAR");
        $this->assertEquals("VARCHAR", $datatype->getType());
        $this->assertEquals("", $datatype->getLength());
        $this->assertEquals(false, $datatype->isNullable());

        $datatype = new Common("VARCHAR", "50", true);
        $this->assertEquals("50", $datatype->getLength());
        $this->assertEquals(true, $datatype->isNullable());
    }
}
