<?php
/**
 * This file is part of bops-example
 *
 * @copyright Copyright (C) 2020 Jayson Wang
 * @license   MIT License
 * @link      https://github.com/wjiec/php-bops-example
 */
namespace App\Frontend\Controller;

use App\Frontend\Library\Mvc\Controller\AbstractController;
use Bops\Http\Request\Middleware\MiddlewareInterface;
use Throwable;


/**
 * Class ErrorController
 * @package App\Frontend\Controller
 */
class ErrorController extends AbstractController {

    public function internalAction(Throwable $e) {
        $this->view->setVar('error_message', $e->getMessage());
    }

    public function notFoundAction() {}

    public function middlewareAction(MiddlewareInterface $middleware) {
        $this->view->setVar('middleware_name', get_class($middleware));
    }

}
