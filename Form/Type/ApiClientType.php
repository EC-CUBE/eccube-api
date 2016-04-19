<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\EccubeApi\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ApiClientType extends AbstractType
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('app_name', 'text', array(
                'label' => 'アプリケーション名',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('max' => 255)),
                ),
            ))
            ->add('redirect_uri', 'text', array(
                'label' => 'redirect_uri',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('max' => 2000)),
                ),
            ))
            ->add('client_identifier', 'text', array(
                'label' => 'Client ID',
                'read_only' => true,
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 80,
                    ))
                ),
            ))
            ->add('client_secret', 'text', array(
                'label' => 'Client secret',
                'read_only' => true,
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 80,
                    ))
                ),
            ))
            ->add('Scopes', 'entity', array(
                'label' => 'scope',
                'choice_label' => 'label',
                'choice_value' => 'scope',
                'choice_name' => 'scope',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false,
                'required' => false,
                'class' => 'Plugin\EccubeApi\Entity\OAuth2\Scope'
            ))
            ->add('public_key', 'textarea', array(
                'label' => 'id_token 公開鍵',
                'read_only' => true,
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 2000,
                    ))
                ),
            ))
            ->add('encryption_algorithm', 'text', array(
                'label' => 'id_token 暗号化アルゴリズム',
                'read_only' => true,
                'required' => false,
                'constraints' => array(
                    new Assert\Length(array(
                        'max' => 100,
                    ))
                ),
            ))

            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\EccubeApi\Entity\OAuth2\Client',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'admin_api_client';
    }
}
