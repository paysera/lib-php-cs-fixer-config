<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

class ResultNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function methodA()
    {
        return true;
    }

    public function methodB()
    {
        return true;
    }

    public function methodC()
    {
        return true;
    }
}
