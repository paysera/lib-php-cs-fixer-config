<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocNecessityFixer;

final class PhpDocNecessityFixerTest extends AbstractPayseraFixerTestCase
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
}',
                '<?php

/**
* Constructs object
*/
class Sample
{
}',
            ],
            [
                '<?php

final class Sample
{
}',
                '<?php

/**
* Constructs object
*/
final class Sample
{
}',
            ],
            [
                '<?php
class Sample
{
    /**
     * Does things
     *
     * @param string $bar
     * @param string $baz
     */
    public function foo($bar, $baz)
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    /**
     * Does things
     */
    public function foo()
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    public function foo($bar)
    {
    }
}',
                '<?php

class Sample
{
    /**
     * Does things
     */
    public function foo($bar)
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    /**
     * @var string
     */
    private $bar;

    public function __construct(string $bar)
    {
        $this->bar = $bar;
    }
}',
                '<?php

class Sample
{
    /**
     * @var string
     */
    private $bar;

    /**
     * Does things
     */
    public function __construct(string $bar)
    {
        $this->bar = $bar;
    }
}',
            ],
            [
                '<?php
use Symfony\Component\Routing\Annotation\Route;

class Sample
{
    /**
     * Matches /blog exactly
     *
     * @Route("/blog", name="blog_list")
     */
    public function list(string $foo)
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    /**
     * Matches /blog exactly
     *
     * @Symfony\Component\Routing\Annotation\Route("/blog", name="blog_list")
     */
    public function list()
    {
    }
}',
            ],
            [
                '<?php
class Sample
{
    /**
     * Email data to foo@bar.baz
     */
    public function foo()
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    /**
     * Id
     *
     * @Soap\ComplexType("int", nillable=true)
     * @Expose
     */
    protected $id;
}',
            ],
            [
                '<?php

class Sample
{
}',
                '<?php

/**
 * Created by a b <c@d.e>
 * User: a
 * Date: 14.10.23
 * Time: 14.19
 */

class Sample
{
}',
            ],
            [
                '<?php
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="sample")
 */
class Sample
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue()
     */
    private $id;
}',
            ],
            [
                '<?php
class Sample
{
    /**
     * Does things
     *
     * @param string $bar
     * @param string $baz
     */
    public static final function foo($bar, $baz)
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    public static final function foo($bar)
    {
    }
}',
                '<?php

class Sample
{
    /**
     * Does things
     */
    public static final function foo($bar)
    {
    }
}',
            ],
            [
                '<?php

class Sample
{
    /**
     * Id
     *
     * @Soap\ComplexType("int", nillable=true)
     * @Expose
     */
    public static $id;
}',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new PhpDocNecessityFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_comment_php_doc_necessity';
    }
}
