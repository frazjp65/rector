<?php

namespace Rector\DomainDrivenDesign\Tests\Rector\ValueObjectRemoverDocBlockRector\Wrong;

use Rector\DomainDrivenDesign\Tests\Rector\ValueObjectRemoverDocBlockRector\Source\SomeChildOfValueObject;

class FirstActionClass
{
    /**
     * @var SomeChildOfValueObject|null
     */
    private $someChildValueObject;

    public function someFunction()
    {
        $this->someChildValueObject = new SomeChildOfValueObject('value');

        $someChildValueObject = new SomeChildOfValueObject();
    }
}
