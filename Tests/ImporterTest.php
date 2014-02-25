<?php

namespace CCC\LinkedinImporterBundle\Tests;

use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use CCC\LinkedinImporterBundle\Importer\Importer;

/**
 * Basic tests for the Importer
 */
class ImporterTest extends \PHPUnit_Framework_TestCase {

    private $importer;

    public function setup() {
        $session = new Session(new MockArraySessionStorage());
        $this->importer = new Importer($session, array());
    }

    public function testState() {
        $this->assertEmpty($this->importer->getState());
        $this->assertFalse($this->importer->isStateValid('xyz'));
    }

}
