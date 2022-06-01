<?php

namespace WonderWp\Plugin\Contact\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use WonderWp\Plugin\Core\Framework\EntityMapping\AbstractEntity;

/**
 * ContactEntity.
 *
 * @ORM\Table(name="contact")
 * @ORM\Entity(repositoryClass="WonderWp\Plugin\Contact\Repository\ContactRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 */
class ContactEntity extends AbstractEntity
{
    use TimestampableEntity;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post", type="integer", nullable=false)
     */
    protected $post;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=6, nullable=false)
     */
    protected $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="sentTo", type="string", length=45, nullable=true)
     */
    protected $sentto;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="array", nullable=true)
     */
    protected $data;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=20, nullable=true)
     */
    protected $ip;

    /**
     * @var ContactFormEntity
     *
     * @ORM\ManyToOne(targetEntity="ContactFormEntity")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="form_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $form;

    /**
     * Get id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set post.
     *
     * @param int $post
     *
     * @return ContactEntity
     */
    public function setPost($post)
    {
        $this->post = $post;

        return $this;
    }

    /**
     * Get post.
     *
     * @return integer
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return ContactEntity
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set sentto.
     *
     * @param string $sentto
     *
     * @return ContactEntity
     */
    public function setSentto($sentto)
    {
        $this->sentto = $sentto;

        return $this;
    }

    /**
     * Get sentto.
     *
     * @return string
     */
    public function getSentto()
    {
        return $this->sentto;
    }

    /**
     * @return ContactFormEntity
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param ContactFormEntity $form
     *
     * @return $this
     */
    public function setForm($form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @param string $index
     *
     * @return array|mixed|null
     */
    public function getData($index = '', $default = null)
    {
        if (!empty($index)) {
            return isset($this->data[$index]) ? $this->data[$index] : $default;
        }

        return $this->data;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return static
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

}
