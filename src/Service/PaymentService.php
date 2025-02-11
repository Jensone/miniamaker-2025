<?php

namespace App\Service;

use Stripe\Price;
use Stripe\Stripe;
use App\Entity\User;
use App\Entity\Subscription;
use Stripe\Checkout\Session;
use App\Service\AbstractService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SubscriptionRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/*
 * Classe PaymentService dédiée à la gestion du 
 * paiement des abonnements des utilisateurs
*/

class PaymentService extends AbstractService
{
    public function __construct(
        private ParameterBagInterface $params,
        private SubscriptionRepository $sr,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
    ) {
        $this->params = $params;
        $this->sr = $sr;
        $this->em = $em;
        $this->httpClient = $httpClient;
    }

    public function setPayment(User $user, int $amount): void
    {
        Stripe::setApiKey($this->params->get('STRIPE_SK'));

        $subscription = new Subscription();
        $subscription
            ->setClient($user)
            ->setAmount($amount)
            ->setFrequency($amount > 99 ? $this->params->get('STRIPE_SUB_ANNUALLY') : $this->params->get('STRIPE_SUB_MONTHLY'))
        ;

        try {
            $checkout_session = Session::create([
                'line_items' => [[
                    'price' => $subscription->getFrequency(),
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $this->params->get('APP_URL') . '/subscription/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->params->get('APP_URL') . '/subscription/cancel',
            ]);

            $this->httpClient->request('GET', $checkout_session->url);
        } catch (\Throwable $th) {
            throw $th;
        }
        // dd($subscription);
    }
}
