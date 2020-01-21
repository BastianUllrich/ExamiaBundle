<?php

/*
 * This file is part of Baul Examia Bundle.
 *
 * (c) Bastian Ullrich
 *
 * @license LGPL-3.0-or-later
 */

namespace Baul\ExamiaBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Baul\ExamiaBundle\BaulExamiaBundle;

class Plugin implements BundlePluginInterface
{
    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(BaulExamiaBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
