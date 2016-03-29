<?php

namespace MiamBundle\Controller;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use MiamBundle\Entity\User;

class SecurityController extends MainController
{
	public function loginAction() {
        $authenticationUtils = $this->get('security.authentication_utils');

        return $this->render('MiamBundle:Security:login.html.twig', array(
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError()
        ));
	}

    public function registerCheckAction(Request $request) {
        $username = trim($request->get('username'));
        $password = $request->get('password');
        $passwordAgain = $request->get('password_again');

        $user = $this->getRepo("User")->findOneByUsername($username);
        if(!$user && $password == $passwordAgain) {
            $user = new User();
            $user->setUsername($username);

            $encoder = $this->get('security.password_encoder');
            $password = $encoder->encodePassword($user, $password);
            $user->setPassword($password);

            $em = $this->getEm();
            $em->persist($user);
            $em->flush();

            $this->login($user);
        }

        return $this->redirectToRoute('index');
    }
    
    private function login(User $user) {
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->get('security.token_storage')->setToken($token);
        $this->get('session')->set('_security_main', serialize($token));

        $user->setDateLogin(new \DateTime("now"));

        $em = $this->getEm();
        $em->persist($user);
        $em->flush();
    }
}
