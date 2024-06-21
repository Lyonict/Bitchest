<?php

namespace App\Form;

use App\Entity\Wallet;
use App\Entity\Cryptocurrencies;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class CryptoAmountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('crypto', EntityType::class, [
                'class' => Cryptocurrencies::class,
                'choice_label' => 'crypto_name',
                'label' => 'Cryptocurrency',
                'placeholder' => 'Select a cryptocurrency',
                'required' => true,
                'mapped' => false, // This field is not directly mapped to an entity property
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
            ])
            ->add('total_cost', NumberType::class, [
                'label' => 'Total Cost',
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Add Crypto Amount',
                'attr' => ['class' => 'btn btn-primary'],
            ]);

        // Add event listener to handle form submission
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $wallet = $event->getData();

            // Retrieve the selected cryptocurrency from the form
            $selectedCrypto = $form->get('crypto')->getData();

            // Set the crypto_id property of the Wallet entity
            $wallet->setCryptoId($selectedCrypto->getCryptoId());
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Wallet::class,
        ]);
    }
}
