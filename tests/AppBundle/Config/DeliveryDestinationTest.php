<?php


namespace Tests\AppBundle\Config;


use AppBundle\Config\DeliveryDestination;
use PHPUnit\Framework\TestCase;

class DeliveryDestinationTest extends TestCase
{
    public function testFilterNone()
    {
        $destination = DeliveryDestination::fromRawData([
            'source' => 'test',
            'destination' => 'test',
        ]);

        $this->assertTrue($destination->shouldFireForRawData(['anything' => 'here']));
    }

    public function testFilterIfValue()
    {
        $destination = DeliveryDestination::fromRawData([ 'source' => 'test', 'destination' => 'test',
            'filters' => [
                [ 'type' => 'include', 'field' => 'testField', 'ifValue' => 'testValue' ],
            ]
        ]);

        $this->assertTrue($destination->shouldFireForRawData(['testField' => 'testValue']));
        $this->assertFalse($destination->shouldFireForRawData(['nofield' => 'wrongvalue']));
    }
}