<?php

namespace WonderWp\Plugin\Contact\Entity;

use Doctrine\ORM\Mapping as ORM;
use WonderWp\Plugin\Core\Framework\EntityMapping\AbstractEntity;

/**
 * Form
 *
 * @ORM\Table(name="contact_form")
 * @ORM\Entity(repositoryClass="WonderWp\Plugin\Core\Framework\Repository\BaseRepository")
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
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", length=65535, nullable=false)
     */
    private $data;

    /**
     * @var string
     *
     * @ORM\Column(name="sendTo", type="string", length=255, nullable=true)
     */
    private $sendTo;

    /**
     * @var bool
     * @ORM\Column(name="save_msg", type="boolean", nullable=true)
     */
    private $saveMsg;

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
     * @return boolean
     */
    public function getSaveMsg()
    {
        return $this->saveMsg;
    }

    /**
     * @param boolean $saveMsg
     *
     * @return static
     */
    public function setSaveMsg($saveMsg)
    {
        $this->saveMsg = $saveMsg;

        return $this;
    }

}
