<?php

namespace App\Controller;

use App\Entity\LoginHistory;
use DeviceDetector\DeviceDetector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils,
        Request $request,
        ): Response
    {
        $userAgent = $request->headers->get('User-Agent');
        $deviceDetector = new DeviceDetector($userAgent);
        $deviceDetector->parse();
        $device = $deviceDetector->getDeviceName();
        $os = $deviceDetector->getOs();
        $browser = $deviceDetector->getClient();
        
        if ($this->getUser()) {
            $loginHistory = new LoginHistory();
            $loginHistory
                ->setUser($this->getUser())
                ->setIpAddress($request->getClientIp())
                ->setDevice($device)
                ->setOs($os['name'])
                ->setBrowser($browser['name'])
                ;
            dd($loginHistory);
        }


        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
