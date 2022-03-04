<?php

namespace WonderWp\Plugin\Contact\Email;

use WonderWp\Component\HttpFoundation\Result;
use WonderWp\Component\Mailing\MailerInterface;
use WonderWp\Plugin\Contact\Entity\ContactEntity;
use WonderWp\Plugin\Contact\Exception\EmailException;
use function WonderWp\Functions\array_merge_recursive_distinct;

abstract class AbstractContactEmail
{
    protected static $sendable = true;

    /** @var  MailerInterface */
    protected $mailer;

    /** @var string */
    protected $fromName;

    /** @var string */
    protected $fromMail;

    /** @var string */
    protected $toName;

    /** @var string */
    protected $toMail;

    /** @var string */
    protected $siteName;

    /** @var string */
    protected $textDomain;

    /** @var ContactEntity */
    protected $contactEntity;

    /**
     * @param MailerInterface $mailer
     * @param string $fromName
     * @param string $fromMail
     * @param string $toName
     * @param string $toMail
     * @param string $siteName
     * @param string $textDomain
     */
    public function __construct(
        MailerInterface $mailer,
        string          $fromName,
        string          $fromMail,
        string          $toName,
        string          $toMail,
        string          $siteName,
        string          $textDomain = ''
    )
    {
        if (empty($textDomain)) {
            $textDomain = WWP_CONTACT_TEXTDOMAIN;
        }

        $this->mailer     = $mailer;
        $this->fromName   = $fromName;
        $this->fromMail   = $fromMail;
        $this->toName     = $toName;
        $this->toMail     = $toMail;
        $this->siteName   = $siteName;
        $this->textDomain = $textDomain;
    }


    /**
     * @return ContactEntity
     */
    public function getContactEntity(): ContactEntity
    {
        return $this->contactEntity;
    }

    /**
     * @param ContactEntity $contactEntity
     * @return AbstractContactEmail
     */
    public function setContactEntity(ContactEntity $contactEntity): AbstractContactEmail
    {
        $this->contactEntity = $contactEntity;
        return $this;
    }

    /**
     * @return MailerInterface
     */
    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }

    /**
     * @return $this
     * @throws EmailException
     */
    public function fill()
    {
        if (empty($this->contactEntity)) {
            throw new EmailException("email.missing.contactEntity");
        }

        //Add to
        $to = $this->provideTo();
        $this->mailer->addTo($to['mail'], $to['name']);

        //Add from
        $from = $this->provideFrom();
        $this->mailer->setFrom($from['mail'], $from['name']);

        //Set subject
        $this->mailer->setSubject($this->provideSubject());

        //Set Body
        $this->mailer->setBody($this->provideBody());

        return $this;
    }

    /**
     * @return string
     */
    abstract public function provideSubject(): string;

    /**
     * @return string
     */
    abstract public function provideBody(): string;

    /**
     * @return array
     */
    abstract public function provideTo(): array;

    /**
     * @return array
     */
    public function provideFrom()
    {
        return [
            'mail' => $this->fromMail,
            'name' => $this->fromName
        ];
    }

    /**
     * @param array $opts
     * @return Result
     */
    public function send(array $opts = [])
    {
        $emailOptions    = $this->getOptions();
        $computedOptions = array_merge_recursive_distinct($emailOptions, $opts);

        $sent = $this->mailer->send($computedOptions);
        do_action('rgpd-mail.sent', $this, $sent);

        return $sent;
    }

    public function getOptions()
    {
        return apply_filters(static::Name . '.options', [], $this);
    }

    /**
     * @return bool
     */
    public static function isSendable(): bool
    {
        return static::$sendable;
    }

    /**
     * @param bool $sendable
     */
    public static function setSendable(bool $sendable): void
    {
        static::$sendable = $sendable;
    }
}
