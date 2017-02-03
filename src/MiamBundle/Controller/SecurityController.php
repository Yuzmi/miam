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

        $error = $authenticationUtils->getLastAuthenticationError();
        if($error) {
            $this->addFm($error->getMessageKey(), "error", $error->getMessageData(), "security");
        }

        return $this->render('MiamBundle:Security:login.html.twig', array(
            'last_username' => $authenticationUtils->getLastUsername()
        ));
	}

    public function registerCheckAction(Request $request) {
        $error = false;

        $username = trim($request->get('username'));
        if(mb_strlen($username) > 255) {
            $this->addFm("Username is too long", "error");
            $error = true;
        } else {
            $username = mb_substr($username, 0, 255);
        }

        $password = $request->get('password');
        $passwordAgain = $request->get('password_again');
        if(!$error && $password !== $passwordAgain) {
            $this->addFm("Passwords are different", "error");
            $error = true;
        }

        $user = $this->getRepo("User")->findOneByUsername($username);
        if(!$error && $user) {
            $this->addFm("Username already exists", "error");
            $error = true;
        }

        if(!$error) {
            $user = new User();
            $user->setUsername($username);

            $encoder = $this->get('security.password_encoder');
            $password = $encoder->encodePassword($user, $password);
            $user->setPassword($password);

            $em = $this->getEm();
            $em->persist($user);
            $em->flush();

            $this->login($user);

            $this->addFm("Welcome!", "success");
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
