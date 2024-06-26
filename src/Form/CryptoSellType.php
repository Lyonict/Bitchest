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
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CryptoSellType extends AbstractType
{
    private $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('crypto', EntityType::class, [
                'class' => Cryptocurrencies::class,
                'choice_label' => 'crypto_name',
                'label' => 'Cryptocurrency',
                'placeholder' => 'Select a cryptocurrency',
                'required' => true,
                'mapped' => false,
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
            ])
            ->add('sell', SubmitType::class, [
                'label' => 'Sell',
                'attr' => ['class' => 'btn btn-danger'],
            ]);

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $wallet = $event->getData();

            $selectedCrypto = $form->get('crypto')->getData();
            $quantity = $form->get('quantity')->getData();

            // Fetch the current price for the selected cryptocurrency
            $symbol = strtolower($selectedCrypto->getCryptoSymbol()) . 'usdt';
            $response = $this->client->request('GET', "https://api.binance.com/api/v3/ticker/24hr?symbol=" . strtoupper($symbol));
            $data = $response->toArray();

            $price = $data['lastPrice'];
            $totalValue = $price * $quantity;

            $wallet->setCryptoId($selectedCrypto->getCryptoId());
            $wallet->setQuantity($quantity);
            $wallet->setTotalCost($totalValue); // Use totalCost to represent the value of the sale
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Wallet::class,
        ]);
    }
}
