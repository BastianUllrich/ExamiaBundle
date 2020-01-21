<?php

/*
 * This file is part of ExamiaBundle.
 *
 * (c) Bastian Ullrich
 *
 * @license LGPL-3.0-or-later
 */

namespace Baul\ExamiaBundle\Tests;

use Baul\ExamiaBundle\BaulExamiaBundle;
use PHPUnit\Framework\TestCase;

class BaulExamiaBundleTest extends TestCase
{
    public function testCanBeInstantiated()
    {
        $bundle = new BaulExamiaBundle();

        $this->assertInstanceOf('Baul\ExamiaBundle\BaulExamiaBundle', $bundle);
    }
}
