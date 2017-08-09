<?php
/**
 * Index Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\Flash\Messages;
use App\Session;
use Slim\Views\Twig;
use Swift_Mailer;

use App\Exception\RequestException;

/**
 * An class for the index action.
 */
class IndexAction extends AbstractAction
{
    /**
     * Email sender.
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * Address email should be sent to.
     * @var string
     */
    private $mailTo;

    /**
     * Address email should be carbon copied to.
     * @var string
     */
    private $mailCc;

    /**
     * Field names that have errors.
     * @var array
     */
    private $errors;

    /**
     * Construct the action with objects and configuration.
     * @param Messages $flash Flash messenger.
     * @param Session $session Session manager.
     * @param Twig $view View renderer.
     * @param Swift_Mailer $mailer Email sender.
     * @param string $mailTo Address email should be sent to.
     * @param string $mailCc Address email should be carbon copied to.
     */
    public function __construct(
        Messages $flash,
        Session $session,
        Twig $view,
        Swift_Mailer $mailer,
        $mailTo,
        $mailCc
    ) {
        parent::__construct($flash, $session, $view);
        $this->mailer = $mailer;
        $this->mailTo = $mailTo;
        $this->mailCc = $mailCc;
    }

    /**
     * Method called when class is invoked as an action.
     * @param Request $req The request for the action.
     * @param Response $res The response from the action.
     * @param array $args The arguments for the action.
     * @return Response The response from the action.
     */
    public function __invoke(Request $req, Response $res, array $args)
    {
        // Add flash messages to arguments.
        $args['messages'] = $this->messages();

        if ($req->getMethod() === 'POST') {
            // Retrieve any data posted by the user.
            $args['post'] = $req->getParsedBody();

            try {
                $this->validateCsrf($req);
                $this->validateRequest($args);
                $this->sendEmail($args);

                $this->flash->addMessage(
                    'success',
                    'Your application has been sent.'
                );

                return $res->withStatus(302)->withHeader(
                    'Location',
                    $req->getUri()->getBasePath()
                );
            } catch (RequestException $exception) {
                $args['messages'][] = [
                    'level' => 'danger',
                    'message' => $exception->getMessage()
                ];
            }
        }

        $args['errors'] = $this->errors;

        // Render form template.
        return $this->view->render($res, 'index.html.twig', $args);
    }

    private function validateRequest(array $args)
    {
        $required = [
            'graduation', 'name', 'id', 'email', 'rank', 'major', 'gpa',
            'award', 'address', 'phone'
        ];

        foreach ($required as $key) {
            if (empty($args['post'][$key])) {
                $this->errors[] = $key;
            }
        }

        if (!empty($this->errors)) {
            throw new RequestException(
                'You must specify all required fields.'
            );
        }

        if (!filter_var($args['post']['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'email';

            throw new RequestException(
                'You must specify a valid BGSU Email.'
            );
        }

        if (!preg_match('/@(?:bgnet\.)?bgsu\.edu$/', $args['post']['email'])) {
            $this->errors[] = 'email';

            throw new RequestException(
                'You must specify a valid BGSU Email.'
            );
        }
    }

    private function sendEmail(array $args)
    {
        try {
            $mailCc = [$args['post']['email']];

            if (!empty($this->mailCc)) {
                $mailCc[] = $this->mailCc;
            }

            $message = $this->mailer->createMessage()
                ->setSubject('Student Employment Application')
                ->setFrom($args['post']['email'])
                ->setTo($this->mailTo)
                ->setCc($mailCc)
                ->setBody(
                    $this->view->fetch('email.html.twig', $args),
                    'text/html'
                );

            if (!$this->mailer->send($message)) {
                throw new RequestException(
                    'Could not send email to the address specified.'
                );
            }
        } catch (\Swift_SwiftException $e) {
            throw new RequestException(
                'An unexpected error occurred. Please try again.'
            );
        }
    }
}
