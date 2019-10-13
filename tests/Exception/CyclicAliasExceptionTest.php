<?php

declare(strict_types=1);

namespace LoomTest\Container\Exception;

use Loom\Container\Exception\CyclicAliasException;
use PHPUnit\Framework\TestCase;

class CyclicAliasExceptionTest extends TestCase
{
    /**
     * @dataProvider aliasesProvider
     *
     * @param string[] $aliases
     * @param string $expectedMessage
     *
     * @return void
     */
    public function testFromAliasesMap(array $aliases, $expectedMessage)
    {
        $exception = CyclicAliasException::fromAliasesMap($aliases);

        self::assertInstanceOf(CyclicAliasException::class, $exception);
        self::assertSame($expectedMessage, $exception->getMessage());
    }

    /**
     * @return string[][]|string[][][]
     */
    public function aliasesProvider()
    {
        return [
            'empty set' => [
                [],
                'A cycle was detected within the following aliases map:

[

]'
            ],
            'acyclic set' => [
                [
                    'b' => 'a',
                    'd' => 'c',
                ],
                'A cycle was detected within the following aliases map:

[
"b" => "a"
"d" => "c"
]'
            ],
            'acyclic self-referencing set' => [
                [
                    'b' => 'a',
                    'c' => 'b',
                    'd' => 'c',
                ],
                'A cycle was detected within the following aliases map:

[
"b" => "a"
"c" => "b"
"d" => "c"
]'
            ],
            'cyclic set' => [
                [
                    'b' => 'a',
                    'a' => 'b',
                ],
                'Cycles were detected within the provided aliases:

[
"b" => "a" => "b"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"a" => "b"
]'
            ],
            'cyclic set (indirect)' => [
                [
                    'b' => 'a',
                    'c' => 'b',
                    'a' => 'c',
                ],
                'Cycles were detected within the provided aliases:

[
"b" => "a" => "c" => "b"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"c" => "b"
"a" => "c"
]'
            ],
            'cyclic set + acyclic set' => [
                [
                    'b' => 'a',
                    'a' => 'b',
                    'd' => 'c',
                ],
                'Cycles were detected within the provided aliases:

[
"b" => "a" => "b"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"a" => "b"
"d" => "c"
]'
            ],
            'cyclic set + reference to cyclic set' => [
                [
                    'b' => 'a',
                    'a' => 'b',
                    'c' => 'a',
                ],
                'Cycles were detected within the provided aliases:

[
"b" => "a" => "b"
"c" => "a" => "b" => "c"
]

The cycle was detected in the following alias map:

[
"b" => "a"
"a" => "b"
"c" => "a"
]'
            ],
        ];
    }
}
