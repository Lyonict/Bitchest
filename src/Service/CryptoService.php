<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Wallet;
use App\Entity\User;
use App\Repository\CryptocurrenciesRepository;

class CryptoService
{
    private $client;
    private $cryptoRepository;

    public function __construct(HttpClientInterface $client, CryptocurrenciesRepository $cryptoRepository)
    {
        $this->client = $client;
        $this->cryptoRepository = $cryptoRepository;
    }

    public function getCryptoData(array $wallets, $user): array
    {
        $cryptos = [
            'btcusdt' => 'Bitcoin',
            'ethusdt' => 'Ethereum',
            'xrpusdt' => 'Ripple',
            'xemusdt' => 'NEM',
            'iotausdt' => 'IOTA',
            'bchusdt' => 'Bitcoin Cash',
            'adausdt' => 'Cardano',
            'ltcusdt' => 'Litecoin',
            'xlmusdt' => 'Stellar',
            'dashusdt' => 'Dash'
        ];

        $cryptoData = [];
        foreach ($cryptos as $symbol => $alias) {
            $response = $this->client->request(
                'GET',
                "https://api.binance.com/api/v3/ticker/24hr?symbol=" . strtoupper($symbol)
            );
            
            $data = $response->toArray();

            // Find the wallet for this crypto (if exists) and get quantity
            $quantity = 0;
            foreach ($wallets as $wallet) {
                $idCryptoWallet = $wallet->getCryptoId();
                $cryptoName = $this->cryptoRepository->findCryptoNameById($idCryptoWallet);
                if (($cryptoName === $alias) && ($wallet->getUser()->getId() === $user->getId())) {
                    $quantity = $wallet->getQuantity();
                    break;
                }
            }

            $cryptoData[$symbol] = [
                'alias' => $alias,
                'price' => $data['lastPrice'],
                'changePercent' => $data['priceChangePercent'],
                'quantity' => $quantity
            ];
        }

        return $cryptoData;
    }
}
