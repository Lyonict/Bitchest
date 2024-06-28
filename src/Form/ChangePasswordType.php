<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('oldPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Current Password :',
                'required' => true,
                'attr' => ['autocomplete' => 'current-password'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('newPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'New Password :',
                'required' => true,
                'attr' => ['autocomplete' => 'new-password'],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Confirm New Password :',
                'required' => true,
                'attr' => ['autocomplete' => 'new-password'],
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
