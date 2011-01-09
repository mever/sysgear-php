<?php

/*
 * This file is part of the Sysgear package.
 *
 * (c) Martijn Evers <martijn4evers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../src/Sysgear/UniversalClassLoader.php';

$loader = new Sysgear\UniversalClassLoader();
$loader->registerNamespaces(array(
	'Sysgear\Tests' => __DIR__,
    'Sysgear' => __DIR__ . '/../src',
));
$loader->register();