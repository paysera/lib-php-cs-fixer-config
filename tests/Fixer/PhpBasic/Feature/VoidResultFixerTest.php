<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\VoidResultFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class VoidResultFixerTest extends AbstractPayseraFixerTestCase
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
        return [
            [
                '<?php
                class Sample
                {
                    public function sampleFunction()
                    {
                        foreach ($webSession->getEvents() as $event) {
                            if ($event->getUserId() !== null) {
                                $this->deferredRemoteJobPublisher->publishJob(
                                    ActionWorker::JOB_KEY,
                                    function () use ($event) {
                                        return ["user_id" => $event->getUserId()];
                                    }
                                );
                                return;
                            }
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @return string|null
                     */
                    public function getScope()
                    {
                        if ($this->getGeneratedCode() !== null) {
                            return implode(\' \', $this->getGeneratedCode()->getAvailableScopes());
                        }
                
                        return null;
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    public function sampleFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } else {
                            return; // TODO: PhpBasic convention 3.17.5: We always return something or return nothing
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function sampleFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } else {
                            return;
                        }
                    }
                }'
            ],
            [
                '<?php
                class Sample
                {
                    public function sampleFunction()
                    {
                        if ($rm && $rm->isProtected() && !$this->_mockery_allowMockingProtectedMethods) {
                            if ($rm->isAbstract()) {
                                return; // TODO: PhpBasic convention 3.17.5: We always return something or return nothing
                            }
                
                            try {
                                $prototype = $rm->getPrototype();
                                if ($prototype->isAbstract()) {
                                    return; // TODO: PhpBasic convention 3.17.5: We always return something or return nothing
                                }
                            } catch (\ReflectionException $re) {
                                // noop - there is no hasPrototype method
                            }
                
                            return call_user_func_array("parent::$method", $args);
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function sampleFunction()
                    {
                        if ($rm && $rm->isProtected() && !$this->_mockery_allowMockingProtectedMethods) {
                            if ($rm->isAbstract()) {
                                return;
                            }
                
                            try {
                                $prototype = $rm->getPrototype();
                                if ($prototype->isAbstract()) {
                                    return;
                                }
                            } catch (\ReflectionException $re) {
                                // noop - there is no hasPrototype method
                            }
                
                            return call_user_func_array("parent::$method", $args);
                        }
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new VoidResultFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_void_result';
    }
}
