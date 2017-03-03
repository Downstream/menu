<?php

namespace ElmDash\Menu\Tests\Cases;

use ElmDash\Menu\Tests\TestCase;
use ElmDash\Menu\Tests\Mock\MockMenu as Menu;
use ElmDash\Menu as RealMenu;

class MenuTest extends TestCase
{

    public function testItWorks()
    {
        $m = new Menu('some.route');
        $m->add('other.route', function (RealMenu $o) {
            $o->authenticated();
        });
    }
}
