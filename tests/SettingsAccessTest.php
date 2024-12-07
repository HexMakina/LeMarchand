<?php

use PHPUnit\Framework\TestCase;
use HexMakina\LeMarchand\LeMarchand;
use HexMakina\LeMarchand\Factory;
use Psr\Container\ContainerInterface;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;


class SettingsAccessTest extends TestCase
{
    public function testConstructor()
    {
        $res = new LeMarchand(['key' => 'value']);
        $this->assertInstanceOf(LeMarchand::class, $res);
        $this->assertInstanceOf(ContainerInterface::class, $res);

        $this->assertTrue($res->has('settings.key'));
        $this->assertEquals('value', $res->get('settings.key'));
    }

    public function testEmptyConstructor()
    {
        $res = new LeMarchand([]);
        $this->assertInstanceOf(LeMarchand::class, $res);
        $this->assertInstanceOf(ContainerInterface::class, $res);

        $this->assertFalse($res->has('settings.0'));

        $this->expectException(NotFoundExceptionInterface::class);
        $res->get('settings.nothing');

        $this->assertFalse($res->has('settings.nothing'));
    }

    public function testConstructorWithNumericallyIndexedStrings()
    {
        $res = new LeMarchand(['invalid', 'key' => 'value2', ['value0', 'value1'], 'key_last' => 'value3']);
        $this->assertEquals('invalid', $res->get('settings.0'));
        $this->assertEquals('value0', $res->get('settings.1.0'));
        $this->assertEquals('value1', $res->get('settings.1.1'));

        $this->assertEquals('value2', $res->get('settings.key'));
        $this->assertEquals('value3', $res->get('settings.key_last'));
        

        $res = new LeMarchand([1]);
        $this->assertEquals(1, $res->get('settings.0'));

        $settings = [1, 2, 3, 4, 5];
        unset($settings[2]);
        $res = new LeMarchand($settings);
        $this->assertTrue($res->has('settings.0'));
        $this->assertTrue($res->has('settings.1'));
        $this->assertFalse($res->has('settings.2'));
        $this->assertTrue($res->has('settings.3'));
        $this->assertTrue($res->has('settings.4'));

        $this->assertEquals(1, $res->get('settings.0'));
        $this->assertEquals(2, $res->get('settings.1'));
        $this->assertEquals(4, $res->get('settings.3'));
        $this->assertEquals(5, $res->get('settings.4'));

        $this->expectException(NotFoundExceptionInterface::class);
        $res->get('settings.2');
        

        $res = new LeMarchand([true]);
        $this->assertEquals(true, $res->get('settings.0'));
    }

    public function testConstructorWithObject()
    {
        $res = new LeMarchand([new stdClass(), 'key' => new stdClass()]);
        $this->assertInstanceOf(stdClass::class, $res->get('settings.0'));
        $this->assertInstanceOf(stdClass::class, $res->get('settings.key'));
    }

    public function testConstructorWithBooleanValues()
    {
        $res = new LeMarchand([true, false, 'key_true' => true, 'key_false' => false]);

        $this->assertTrue($res->get('settings.0'));
        $this->assertFalse($res->get('settings.1'));

        $this->assertTrue($res->get('settings.key_true'));
        $this->assertFalse($res->get('settings.key_false'));
    }

    public function testConstructorWithNullValues()
    {
        $res = new LeMarchand(
            [
                null,
                'key' => null,
                'level' => [
                    null,
                    [
                        'key' => [
                            'value1',
                            null
                        ]
                    ]
                ]
            ]
        );

        foreach(['settings.0', 'settings.key', 'settings.level.0', 'settings.level.1.key.1'] as $lament){
            $this->expectException(NotFoundExceptionInterface::class);
            $res->get($lament);
            $this->assertFalse($res->has($lament));
        }

        $this->assertEquals('value1', $res->get('settings.level.1.key.0'));
        $this->expectException(NotFoundExceptionInterface::class);
    }

    // write a test for constructor with settings
    public function testConstructorWith5Levels()
    {
        $res = new LeMarchand($this->Settings5Levels());


        $this->assertFalse($res->has('settings.nothing'));
        $this->expectException(NotFoundExceptionInterface::class);
        $res->get('settings.nothing');


        $this->assertTrue($res->has('settings.key'));
        $this->assertEquals('value1', $res->get('settings.key'));

        $this->assertTrue($res->has('settings.2level.key'));
        $this->assertEquals('value2', $res->get('settings.2level.key'));

        $this->assertTrue($res->has('settings.3level.2level.key'));
        $this->assertEquals('value3', $res->get('settings.3level.2level.key'));

        $this->assertTrue($res->has('settings.3level.2level2.key'));
        $this->assertEquals('value4', $res->get('settings.3level.2level2.key'));

        $this->assertTrue($res->has('settings.4level.3level.2level.key'));
        $this->assertEquals('value5', $res->get('settings.4level.3level.2level.key'));

        $this->assertTrue($res->has('settings.4level.3level.2level2.key'));
        $this->assertEquals('value6', $res->get('settings.4level.3level.2level2.key'));

        $this->assertTrue($res->has('settings.5level.4level.3level.2level.key'));
        $this->assertEquals('value9', $res->get('settings.5level.4level.3level.2level.key'));

        $this->assertTrue($res->has('settings.5level.4level.3level.2level2.key'));
        $this->assertEquals('value10', $res->get('settings.5level.4level.3level.2level2.key'));

        $this->assertTrue($res->has('settings.5level.4level2.3level.2level.key'));
        $this->assertEquals('value13', $res->get('settings.5level.4level2.3level.2level.key'));

        $this->assertTrue($res->has('settings.5level.4level2.3level.2level2.key'));
        $this->assertEquals('value14', $res->get('settings.5level.4level2.3level.2level2.key'));

        $this->assertTrue($res->has('settings.5level.4level2.3level2.2level.key'));
        $this->assertEquals('value15', $res->get('settings.5level.4level2.3level2.2level.key'));

        $this->assertTrue($res->has('settings.5level.4level2.3level2.2level2.key'));
        $this->assertEquals('value16', $res->get('settings.5level.4level2.3level2.2level2.key'));
    }

    private function Settings5Levels()
    {
        return [
            'key' => 'value1',
            '2level' => [
                'key' => 'value2'
            ],
            '3level' => [
                '2level' => [
                    'key' => 'value3'
                ],
                '2level2' => [
                    'key' => 'value4'
                ]
            ],
            '4level' => [
                '3level' => [
                    '2level' => [
                        'key' => 'value5'
                    ],
                    '2level2' => [
                        'key' => 'value6'
                    ]
                ],
                '3level2' => [
                    '2level' => [
                        'key' => 'value7'
                    ],
                    '2level2' => [
                        'key' => 'value8'
                    ]
                ]
            ],
            '5level' => [
                '4level' => [
                    '3level' => [
                        '2level' => [
                            'key' => 'value9'
                        ],
                        '2level2' => [
                            'key' => 'value10'
                        ]
                    ],
                    '3level2' => [
                        '2level' => [
                            'key' => 'value11'
                        ],
                        '2level2' => [
                            'key' => 'value12'
                        ]
                    ]
                ],
                '4level2' => [
                    '3level' => [
                        '2level' => [
                            'key' => 'value13'
                        ],
                        '2level2' => [
                            'key' => 'value14'
                        ]
                    ],
                    '3level2' => [
                        '2level' => [
                            'key' => 'value15'
                        ],
                        '2level2' => [
                            'key' => 'value16'
                        ]
                    ]
                ]
            ]

        ];
    }
}
