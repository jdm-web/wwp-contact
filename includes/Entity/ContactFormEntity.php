<?php

namespace WonderWp\Plugin\Contact\Entity;

use Doctrine\ORM\Mapping as ORM;
use WonderWp\Plugin\Core\Framework\EntityMapping\AbstractEntity;

/**
 * Form.
 *
 * @ORM\Table(name="contact_form")
 * @ORM\Entity(repositoryClass="WonderWp\Plugin\Contact\Repository\ContactFormRepository")
 */
class ContactFormEntity extends AbstractEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", length=65535, nullable=false)
     */
    protected $data;

    /**
     * @var string
     *
     * @ORM\Column(name="sendTo", type="string", length=255, nullable=true)
     */
    protected $sendTo;

    /**
     * @var string
     *
     * @ORM\Column(name="cc", type="string", length=255, nullable=true)
     */
    protected $cc;

    /**
     * @var bool
     * @ORM\Column(name="send_customer_email", type="boolean", nullable=true)
     */
    protected $sendCustomerEmail = false;

    /**
     * @var bool
     * @ORM\Column(name="save_msg", type="boolean", nullable=true)
     */
    protected $saveMsg;

    /**
     * @var integer
     * @ORM\Column(name="numberOfDaysBeforeRemove", type="integer", nullable=true)
     */
    private $numberOfDaysBeforeRemove;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getSendTo()
    {
        return $this->sendTo;
    }

    /**
     * @param string $sendTo
     *
     * @return $this
     */
    public function setSendTo($sendTo)
    {
        $this->sendTo = $sendTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param string $cc
     *
     * @return static
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSaveMsg()
    {
        return $this->saveMsg;
    }

    /**
     * @param bool $saveMsg
     *
     * @return static
     */
    public function setSaveMsg($saveMsg)
    {
        $this->saveMsg = $saveMsg;

        return $this;
    }

    /**
     * @return integer
     */
    public function getNumberOfDaysBeforeRemove()
    {
        return $this->numberOfDaysBeforeRemove;
    }

    /**
     * @param int $numberOfDaysBeforeRemove
     *
     * @return static
     */
    public function setNumberOfDaysBeforeRemove($numberOfDaysBeforeRemove)
    {
        $this->numberOfDaysBeforeRemove = $numberOfDaysBeforeRemove;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSendCustomerEmail(): bool
    {
        return $this->sendCustomerEmail ?: false;
    }

    /**
     * @param bool $sendCustomerEmail
     * @return ContactFormEntity
     */
    public function setSendCustomerEmail(bool $sendCustomerEmail): ContactFormEntity
    {
        $this->sendCustomerEmail = $sendCustomerEmail;
        return $this;
    }
}
