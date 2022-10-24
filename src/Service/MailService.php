<?php

namespace App\Service;


use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{

    public function __construct(private readonly MailerInterface $mailer)
    {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $destination, string $subject, string $text, string $html = null): void
    {
        $email = (new Email())
            ->from('gurvan.quenot2021@campus-eni.fr')
            ->to($destination)
            ->subject($subject)
            ->text($text)
            ->html($html);

        $this->mailer->send($email);
    }
}