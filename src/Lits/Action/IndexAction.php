<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Action;
use Lits\Exception\FailedSendingException;
use Lits\Exception\InvalidDataException;
use Lits\Mail;
use Lits\Service\ActionService;
use Safe\Exceptions\PcreException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

use function Safe\preg_match;

final class IndexAction extends Action
{
    private const FIELDS = [
        'date' => false,
        'graduation' => true,
        'name' => true,
        'id' => true,
        'email' => true,
        'rank' => true,
        'major' => true,
        'gpa' => true,
        'award' => true,
        'award_amount' => false,
        'hours' => false,
        'address' => true,
        'phone' => true,
        'current_continue' => false,
        'current_hours' => false,
        'current_employer' => false,
        'current_dates' => false,
        'current_supervisor' => false,
        'current_title' => false,
        'current_phone' => false,
        'current_duties' => false,
        'previous1_employer' => false,
        'previous1_dates' => false,
        'previous1_supervisor' => false,
        'previous1_title' => false,
        'previous1_phone' => false,
        'previous1_duties' => false,
        'previous1_leaving' => false,
        'previous2_employer' => false,
        'previous2_dates' => false,
        'previous2_supervisor' => false,
        'previous2_title' => false,
        'previous2_phone' => false,
        'previous2_duties' => false,
        'previous2_leaving' => false,
        'additional' => false,
        'skills_previous' => false,
        'skills_music' => false,
        'skills_language' => false,
        'skills_extra' => false,
        'depot' => false,
        'manager' => true,
    ];

    /** @var array<string> $errors */
    private array $errors = [];

    public function __construct(ActionService $service, private Mail $mail)
    {
        parent::__construct($service);
    }

    /** @throws HttpInternalServerErrorException */
    public function action(): void
    {
        try {
            $this->render(
                $this->template(),
                [
                    'errors' => $this->session->get('errors'),
                    'post' => $this->session->get('post'),
                ],
            );

            $this->session->remove('errors');
            $this->session->remove('post');
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @param array<string, string> $data
     * @throws HttpInternalServerErrorException
     */
    public function post(
        ServerRequest $request,
        Response $response,
        array $data,
    ): Response {
        $this->setup($request, $response, $data);

        try {
            $context = $this->context();

            $this->validate($context['post']);
            $this->send($context);
        } catch (InvalidDataException $exception) {
            $this->message('failure', $exception->getMessage());
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not process posted data',
                $exception,
            );
        }

        $this->session->set('post', $this->request->getParsedBody());
        $this->session->set('errors', $this->errors);
        $this->redirect();

        return $this->response;
    }

    /**
     * @return array{post: array<string, ?string>}
     * @throws InvalidDataException
     */
    private function context(): array
    {
        $context = ['post' => []];

        foreach (self::FIELDS as $key => $required) {
            $context['post'][$key] = $this->value($key, true);

            if ($required && \is_null($context['post'][$key])) {
                $this->errors[] = $key;
            }
        }

        if ($this->errors !== []) {
            throw new InvalidDataException(
                'You must specify all required fields.',
            );
        }

        return $context;
    }

    /**
     * @param array<string, ?string> $post
     * @throws InvalidDataException
     * @throws PcreException
     */
    private function validate(array $post): void
    {
        if (
            !isset($post['email']) ||
            \filter_var($post['email'], \FILTER_VALIDATE_EMAIL) === false ||
            preg_match('/@(?:bgnet\.)?bgsu\.edu$/', $post['email']) === 0
        ) {
            $this->errors[] = 'email';

            throw new InvalidDataException(
                'You must specify a valid BGSU Email.',
            );
        }

        if (
            !isset($post['manager']) ||
            \filter_var($post['manager'], \FILTER_VALIDATE_EMAIL) === false
        ) {
            $this->errors[] = 'manager';

            throw new InvalidDataException(
                'You must specify a valid Hiring Manager Email.',
            );
        }
    }

    /**
     * @param array{post: array<string, ?string>} $context
     * @throws HttpInternalServerErrorException
     */
    private function send(array $context): void
    {
        if (
            !isset($context['post']['email']) ||
            !isset($context['post']['manager'])
        ) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not address mail',
            );
        }

        $message = $this->mail->message()
            ->from($context['post']['email'])
            ->to($context['post']['manager'])
            ->cc($context['post']['email'])
            ->subject('Student Employment Application')
            ->htmlTemplate('mail.html.twig')
            ->context($context);

        try {
            $this->mail->send($message);
        } catch (FailedSendingException $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                'Could not send mail',
                $exception,
            );
        }

        $this->message(
            'success',
            'Your application has been sent.',
        );
    }
}
