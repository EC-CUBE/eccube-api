<?php

namespace Plugin\EccubeApi\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class OAuth2AuthorizationType extends AbstractType
{
    protected $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // TODO
        $builder
            ->add('client_id', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('redirect_uri', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('response_type', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('state', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('scope', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('nonce', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('authorized', 'hidden', array(
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ));
    }

    public function getName()
    {
        return 'oauth_authorization';
    }
}
