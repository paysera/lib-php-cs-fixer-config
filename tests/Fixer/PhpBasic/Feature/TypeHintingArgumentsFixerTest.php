<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingArgumentsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use SplFileInfo;

final class TypeHintingArgumentsFixerTest extends AbstractPayseraFixerTestCase
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

    /**
     * @param string $expected
     * @param SplFileInfo $file
     *
     * @dataProvider provideFileCases
     */
    public function testFixFiles($expected, SplFileInfo $file)
    {
        $this->doTest($expected, null, $file);
    }

    public function provideCases()
    {
        return [
            [
                '<?php
                class Sample
                {
                    /**
                     * @param int|null $constraint
                     */
                    public function validate($constraint = null)
                    {
                        if ($constraint === null) {
                            return $constraint;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param Limits $entity
                     * @param Constraint|LimitsConstraint $constraint
                     */
                    public function validate(Limits $entity, Constraint $constraint)
                    {
                        if ($entity === null) {
                            return;
                        }

                        foreach ($entity->getLimits() as $limit) {
                            $currency = $limit->getMaxPrice()->getCurrency();
                            if (!$this->currencyFilter->isCurrencyActive($currency)) {
                                $this->context->addViolation(
                                    $constraint->messageInvalidCurrency,
                                    array(\'%currency%\' => $currency),
                                    $currency,
                                    null,
                                    \'currency_unavailable\'
                                );
                            }
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param Limits $entity
                     * @param Constraint|LimitsConstraint $constraint
                     */
                    public function validate($entity, Constraint $constraint)
                    {
                        if ($entity === null) {
                            return;
                        }

                        foreach ($entity->getLimits() as $limit) {
                            $currency = $limit->getMaxPrice()->getCurrency();
                            if (!$this->currencyFilter->isCurrencyActive($currency)) {
                                $this->context->addViolation(
                                    $constraint->messageInvalidCurrency,
                                    array(\'%currency%\' => $currency),
                                    $currency,
                                    null,
                                    \'currency_unavailable\'
                                );
                            }
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Updates location
                     *
                     * @param $locationId
                     * @param ConcreteLocation $location
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     * @return Location
                     */
                    public function updateLocation($locationId, ConcreteLocation $location)
                    {
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Is valid OAuth scope
                     *
                     * @param $scope
                     * @param bool   $allowExtendable
                     *
                     * @return bool
                     */
                    protected function isValidOAuthScope($scope, $allowExtendable = false)
                    {
                        return (
                            in_array($scope, self::$validOAuthScopes)
                            || $allowExtendable && in_array($scope, self::$extendableOAuthScopes)
                        );
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param ValueClass|null $value
                     */
                    public function setValue(ValueClass $value = null)
                    {

                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param ValueClass|null $value
                     */
                    public function setValue($value)
                    {

                    }
                }'
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Handle recipient
                     *
                     * @param PushRecipient $recipient
                     * @param array         $parameters
                     *
                     * @return PushRecipient
                     */
                    public function persistRecipient(PushRecipient $recipient, array $parameters);
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param resource $resource
                     */
                    public function setResource($resource)
                    {
                    }
                }',
                null,
            ],
        ];
    }

    public function provideFileCases()
    {
        return [
            [
                '<?php
                
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

                class DummyChild extends DummyParent
                {
                    /**
                     * Sets application
                     *
                     * @param \ArrayAccess $application
                     */
                    public function setApplication($application)
                    {
                        $this->application = $application;
                    }
                }',
                new SplFileInfo(__DIR__ . '/Fixtures/DummyChild.php')
            ],
            [
                '<?php
                
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

                class ImplementingClass implements DenormalizerInterface
                {
                    /**
                     * @param \SplFileInfo $something
                     */
                    public function make($something)
                    {
                        $something->getType();
                    }
                }',
                new SplFileInfo(__DIR__ . '/Fixtures/ImplementingClass.php')
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new TypeHintingArgumentsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_type_hinting_arguments';
    }
}
