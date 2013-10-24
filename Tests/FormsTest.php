<?php

namespace CCC\LinkedinImporterBundle\Tests;

use CCC\LinkedinImporterBundle\Form\RequestPrivate;
use CCC\LinkedinImporterBundle\Form\RequestPublic;

/**
 * Stubbing out some tentative tests
 */
class FormsTest extends \PHPUnit_Framework_TestCase
{
    public function testForms()
    {
        $privateForm = new RequestPrivate();
        $publicForm = new RequestPublic();
        $this->assertEquals('privaterequest', $privateForm->getName());
        $this->assertEquals('requestpublic', $publicForm->getName());
    }
}
