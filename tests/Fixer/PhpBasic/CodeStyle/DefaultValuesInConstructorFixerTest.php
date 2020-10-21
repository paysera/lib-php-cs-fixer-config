<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DefaultValuesInConstructorFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class DefaultValuesInConstructorFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @param string $expected
     * @param null|string $input
     *
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {

        $this->doTest($expected, $input);
    }

    public function provideCases()
    {
        $data = [
            [
                '<?php
                class Sample
                {
                    private $defaultArray;
                    private $value;
                    private $defaultInteger;
                
                    public function __construct()
                    {
                        $this->value = new ArrayCollection();
                        $this->defaultArray = [];
                        $this->defaultInteger = 1;
                    }
                }',
                '<?php
                class Sample
                {
                    private $defaultArray = [];
                    private $value;
                    private $defaultInteger = 1;
                
                    public function __construct()
                    {
                        $this->value = new ArrayCollection();
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private $defaultArray;
                    private $defaultInteger;
                    private $defaultVariable;
                
                    public function __construct()
                    {
                        $this->defaultArray = [];
                        $this->defaultInteger = 1;
                    }
                
                    public function doSomething()
                    {
                    }
                }',
                '<?php
                class Sample
                {
                    private $defaultArray = [];
                    private $defaultInteger = 1;
                    private $defaultVariable;
                
                    public function doSomething()
                    {
                    }
                }',
            ],
            [
                '<?php
                class Sample extends Parent
                {
                    private $defaultArray;
                    private $defaultInteger;
                    private $defaultVariable;
                
                    public function __construct()
                    {
                        parent::__construct();
                        $this->defaultArray = [];
                        $this->defaultInteger = 1;
                    }
                
                    public function doSomething()
                    {
                    }
                }',
                '<?php
                class Sample extends Parent
                {
                    private $defaultArray = [];
                    private $defaultInteger = 1;
                    private $defaultVariable;
                
                    public function doSomething()
                    {
                    }
                }',
            ],
            [
                '<?php
                class Sample extends Parent
                {
                    private $defaultArray;
                    private $defaultBool;
                
                    public function __construct()
                    {
                        parent::__construct();
                        $this->defaultArray = [];
                        $this->defaultBool = true;
                    }
                
                    public function doSomething()
                    {
                    }
                }',
                '<?php
                class Sample extends Parent
                {
                    private $defaultArray = [];
                    private $defaultBool = true;
                
                    public function doSomething()
                    {
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private $defaultArray;
                
                    public function __construct()
                    {
                        $this->defaultArray = [];
                    }
                
                    /**
                     * @var Something $something
                     */
                    public function doSomething(Something $something)
                    {
                        $something->do();
                    }
                }',
                '<?php
                class Sample
                {
                    private $defaultArray = [];
                
                    /**
                     * @var Something $something
                     */
                    public function doSomething(Something $something)
                    {
                        $something->do();
                    }
                }',
            ],
            [
                '<?php
                declare(strict_types=1);
                
                namespace App;
                
                use App\Service;
                
                class Sample
                {
                    private $defaultArray;
                
                    public function __construct()
                    {
                        $this->defaultArray = [];
                    }
                
                    /**
                     * @var Something $something
                     */
                    public function doSomething(Something $something)
                    {
                        $something->do();
                    }
                }',
                '<?php
                declare(strict_types=1);
                
                namespace App;
                
                use App\Service;
                
                class Sample
                {
                    private $defaultArray = [];
                
                    /**
                     * @var Something $something
                     */
                    public function doSomething(Something $something)
                    {
                        $something->do();
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private $defaultArray;
                
                    public function __construct()
                    {
                        $this->defaultArray = [];
                    }
                
                    public function doSomething()
                    {
                    }
                }',
                '<?php
                class Sample
                {
                    private $defaultArray = [];
                
                    public function doSomething()
                    {
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private $defaultArray;
                
                    public function __construct()
                    {
                        $this->defaultArray = [
                            \'key\' => \'value\',
                            \'key\' => \'value\',
                        ];
                    }
                
                    public function doSomething()
                    {
                    }
                }',
                '<?php
                class Sample
                {
                    private $defaultArray = [
                        \'key\' => \'value\',
                        \'key\' => \'value\',
                    ];
                
                    public function doSomething()
                    {
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    const DEFAULT_STATE = \'default\';
                    private $defaultArray;
                
                    public function __construct()
                    {
                        $this->defaultArray = [
                            \'key\' => \'value\',
                            \'key\' => \'value\',
                        ];
                    }
                
                    public static function getSomething()
                    {
                        return [];
                    }
                
                    public function doSomething()
                    {
                    }
                }',
                '<?php
                class Sample
                {
                    const DEFAULT_STATE = \'default\';
                    private $defaultArray = [
                        \'key\' => \'value\',
                        \'key\' => \'value\',
                    ];
                
                    public static function getSomething()
                    {
                        return [];
                    }
                
                    public function doSomething()
                    {
                    }
                }',
            ],
            [
                '<?php
                trait MyTrait
                {
                    private $text = \'abc\';
                    
                    private function getText()
                    {
                        return $this->text;
                    }
                }',
                null,
            ],
            [
                '<?php
                declare(strict_types=1);
                
                class A
                {
                    const CONSTANT = \'value\';
                }
                ',
                null,
            ],

        ];

        if(
            (PHP_MAJOR_VERSION === 7 && PHP_MINOR_VERSION >= 4)
            || PHP_MAJOR_VERSION > 7
        ) {
            $php74 = [
                [
                    '<?php
                declare(strict_types=1);
                
                class A
                {
                    public string $text;
                    protected int $number;
                    private array $list;
                    
                    public DateTimeInterface $date;
                    private SomeClass $someClass;
                }
                ',
                    null,
                ],
                [
                    '<?php
                declare(strict_types=1);
                
                class A
                {
                    public ?string $text;
                    
                    public function __construct()
                    {
                        $this->text = \'test\';
                    }
                }
                '
                ],
                [
                    '<?php
                declare(strict_types=1);
                
                class A
                {
                    public const PUBLIC_BAR=\'FOO\';
                    protected const PROTECTED_BAR=\'FOO\';
                    private const PRIVATE_BAR=\'FOO\';
                    
                    public function __construct()
                    {
                        $this->text = \'test\';
                    }
                }
                '
                ],

            ];

            $data = array_merge($data, $php74);

        }

        return $data;
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new DefaultValuesInConstructorFixer(),
        ]);

        return $fixerFactory;
    }

    protected function getFixerName()
    {
        return 'Paysera/php_basic_code_style_default_values_in_constructor';
    }
}
