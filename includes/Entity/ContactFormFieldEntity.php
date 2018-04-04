<?php

namespace WonderWp\Plugin\Contact\Entity;

use Doctrine\ORM\Mapping as ORM;
use WonderWp\Component\Form\Field\InputField;
use WonderWp\Plugin\Core\Framework\EntityMapping\AbstractEntity;

/**
 * @ORM\Table(name="contact_form_fields")
 * @ORM\Entity(repositoryClass="WonderWp\Plugin\Core\Framework\Repository\BaseRepository")
 */
class ContactFormFieldEntity extends AbstractEntity
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="integer", unique=true)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $options;

    /**
     * @param string $type
     * @param array  $options
     */
    public function __construct($type = InputField::class, array $options = [])
    {
        if (array_key_exists('name', $options)) {
            $this->name = $options['name'];
            unset($options['name']);
        }

        $this->type    = $type;
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return static
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
     * @return static
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function isEnabled(array $options)
    {
        return array_key_exists('enabled', $options) && $options['enabled'];
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function isRequired(array $options)
    {
        return array_key_exists('required', $options) && $options['required'];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return static
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $option
     * @param mixed  $value
     *
     * @return static
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * @param string $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($option, $default = null)
    {
        return array_key_exists($option, $this->options) ? $this->options[$option] : $default;
    }

    /**
     * @param array $options
     *
     * @return static
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
