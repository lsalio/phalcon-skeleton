<?php
/**
 * This file is part of phalcon-skeleton
 *
 * @copyright Copyright (C) 2020 Jayson Wang
 * @license   MIT License
 * @link      https://github.com/lsalio/phalcon-skeleton
 */
namespace App\Library\Framework\Listener\Adapter;

use App\Library\Framework\Feature\Version;
use App\Library\Framework\Listener\AbstractListener;
use App\Library\Framework\Mvc\Controller\Tag\Deeper;
use Exception;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Text;
use Throwable;


/**
 * Class Dispatcher
 * @package App\Library\Framework\Listener\Adapter
 */
class Dispatcher extends AbstractListener {

    /**
     * Triggered before the dispatcher throws any exception
     *
     * @param Event $event
     * @param MvcDispatcher $dispatcher
     * @param Throwable $exception
     * @return bool|void
     * @throws Exception
     */
    public function beforeException(Event $event, MvcDispatcher $dispatcher, Throwable $exception) {
        /* @var Version $version */
        $version = container('versionFeature');
        if ($version->isVersionHandlerException($exception)) {
            do {
                if ($version->getNumber() !== 1) {
                    $config = container('config')->error;
                    if ($dispatcher->getControllerName() !== $config->controller) {
                        break;
                    }
                }

                if ($version->check($dispatcher->getModuleNameWithDefault())) {
                    $dispatcher->forward([
                        'namespace' => $version->namespaceFallback($dispatcher->getDefaultNamespace())
                    ]);

                    // automatically reset status code when version-feature forward
                    container('response')->setStatusCode(200);
                    return false;
                }
            } while (false);
        }

        $this->doErrorForward($dispatcher, $exception);
        // Returns false to dispatch loop again
        return false;
    }

    /**
     * Checks the loop can be deeper
     *
     * @param Event $event
     * @param MvcDispatcher $dispatcher
     */
    public function beforeNotFoundAction(Event $event, MvcDispatcher $dispatcher) {
        if (get_class($dispatcher->getActiveController()) === $dispatcher->getHandlerClass()) {
            if (container($dispatcher->getHandlerClass()) instanceof Deeper) {
                $this->doDeeperForward($dispatcher);
            }
        }
    }

    /**
     * Detect a corrected error forward and dispatch it
     *
     * @param MvcDispatcher $dispatcher
     * @param Throwable $exception
     */
    protected function doErrorForward(MvcDispatcher $dispatcher, Throwable $exception) {
        $error = container('config')->error;

        try {
            if ($dispatcher->getNamespaceName()) {
                return $dispatcher->forward([
                    'controller' => $error->controller,
                    'action' => $error->action,
                    'params' => [$exception]
                ]);
            }
        } catch (Throwable $e) {
            container('logger', 'sys')->error($e->getMessage());
        }

        if ($error->status_code) {
            container('response')->setStatusCode($error->status_code);
        }
        return $dispatcher->setReturnedValue($error->error_message);
    }

    /**
     * Deeper forward and dispatch it
     *
     * @param MvcDispatcher $dispatcher
     */
    protected function doDeeperForward(MvcDispatcher $dispatcher) {
        $params = $dispatcher->getParams();
        $dispatcher->forward([
            'namespace' => join('\\', [
                $dispatcher->getNamespaceName(),
                Text::camelize($dispatcher->getControllerName())
            ]),
            'controller' => $dispatcher->getActionName(),
            'action' => empty($params) ? $dispatcher->getDefaultAction() : array_shift($params),
            'params' => $params
        ]);
    }

}
