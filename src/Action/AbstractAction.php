<?php
/**
 * Abstract Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use App\Exception\RequestException;

use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * An abstract action class.
 */
abstract class AbstractAction
{
    /**
     * Flash messenger.
     * @var Messages
     */
    protected $flash;

    /**
     * Session manager.
     * @var Session
     */
    protected $session;

    /**
     * View renderer.
     * @var Twig
     */
    protected $view;

    /**
     * Construct the action with objects and configuration.
     * @param Messages $flash Flash messenger.
     * @param Session $session Session manager.
     * @param Twig $view View renderer.
     */
    public function __construct(
        Messages $flash,
        Session $session,
        Twig $view
    ) {
        $this->flash = $flash;
        $this->session = $session;
        $this->view = $view;
    }

    /**
     * Method called when class is invoked as an action.
     * @param Request $req The request for the action.
     * @param Response $res The response from the action.
     * @param array $args The arguments for the action.
     * @return Response The response from the action.
     */
    abstract public function __invoke(Request $req, Response $res, array $args);

    protected function messages()
    {
        $result = [];

        foreach (['success', 'danger'] as $level) {
            $messages = $this->flash->getMessage($level);

            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $result[] = [
                        'level' => $level,
                        'message' => $message
                    ];
                }
            }
        }

        return $result;
    }

    protected function validateCsrf(Request $req)
    {
        if ($req->getAttribute('csrf_failed')) {
            throw new RequestException(
                'Your request timed out. Please try again.'
            );
        }
    }
}
