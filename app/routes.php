<?php
/**
 * Application Routes
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use Slim\Container;
use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;
use Swift_Mailer;

// Index action.
$container[IndexAction::class] = function (Container $container) {
    return new IndexAction(
        $container[Messages::class],
        $container[Session::class],
        $container[Twig::class],
        $container[Swift_Mailer::class],
        $container['settings']['mail']['to'],
        $container['settings']['mail']['cc']
    );
};

$app->any('/', IndexAction::class);
