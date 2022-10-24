<?php

namespace App\Service;


use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailService
{

    public function __construct(private readonly MailerInterface $mailer)
    {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $destination, string $subject, string $text, string $htmlTemplate = null, array $context = []): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('gurvan.quenot2021@campus-eni.fr', 'Sortir.com Mail bot'))
            ->to($destination)
            ->subject($subject)
            ->text($text)
            ->htmlTemplate($htmlTemplate)
            ->context($context);

        $this->mailer->send($email);
    }
}