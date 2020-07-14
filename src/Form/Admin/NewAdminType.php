<?php

namespace App\Form\Admin;

use App\Entity\Admin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                null,
                [
                    'attr' => ['placeholder' => 'Username'],
                    'label' => false,
                ]
            )
            ->add(
                'password',
                PasswordType::class,
                [
                    'label' => false,
                    'attr' => ['placeholder' => 'Password'],
                ]
            )
            ->add(
                'roles',
                ChoiceType::class,
                [
                'choices' => ['Admin' => 'ROLE_ADMIN', 'Moderator' => 'ROLE_USER'],
                'required'   => true,
                'expanded' => false,
                'multiple' => true,
                'label' => false,
            ]
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Admin::class,
        ]);
    }
}
