<?php

/*
 * This file is part of the CRUD Admin Generator project.
 *
 * Author: Jon Segador <jonseg@gmail.com>
 * Web: http://crud-admin-generator.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../src/app.php';


require_once __DIR__.'/Session/index.php';
require_once __DIR__.'/authors/index.php';
require_once __DIR__.'/bookreqs/index.php';
require_once __DIR__.'/books/index.php';
require_once __DIR__.'/categorys/index.php';
require_once __DIR__.'/coupones/index.php';
require_once __DIR__.'/faqs/index.php';
require_once __DIR__.'/featuredbooks/index.php';
require_once __DIR__.'/languages/index.php';
require_once __DIR__.'/notifications/index.php';
require_once __DIR__.'/profile/index.php';
require_once __DIR__.'/shippinhadds/index.php';
require_once __DIR__.'/users/index.php';



$app->match('/', function () use ($app) {

    return $app['twig']->render('ag_dashboard.html.twig', array());
        
})
->bind('dashboard');


$app->run();