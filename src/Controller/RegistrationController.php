<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use App\Form\UserDeleteFormType;

// session
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class RegistrationController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('reply@symfoplaces.com', 'SymfoPlaces registration'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );
            // do anything else you need here, like send an email

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('home');
    }


    // unsubscribe user
    #[Route('/unsubscribe}', name: 'unsubscribe', methods: ['GET', 'POST'])]
    public function unsubscribe(Request $request, ManagerRegistry $doctrine, LoggerInterface $appUserInfoLogger, Session $session, TokenStorageInterface $token):Response{

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $usuario = $this->getUser(); //recuperamos usuario logeado
        
        $formulario = $this->createForm(UserDeleteFormType::class, $usuario);
        $formulario->handleRequest($request);

        if ($formulario->isSubmitted() && $formulario->isValid()) {
            
            // pone a NULL el user_id de las películas relacionadas
            foreach ($usuario->getPlaces() as $place) {
                $usuario->removePlace($place);
            }



            // $entityManager = $this->getDoctrine()->getManager();
            $entityManager = $doctrine->getManager();
            $entityManager->remove($usuario);
            $entityManager->flush();

            $token->setToken(NULL);
            $session->invalidate();


            // TODO: como cerrar sesión!!!
            // $session->get('security.token_storage')->setToken(null);
            // $session->get('session')->invalidate();

            // flashear el mensaje
            $message = 'Usuario '.$usuario->getDisplayname().' eliminado correctamente';
            $this->addFlash('success', $message);
            
            // loguear el message
            $message = 'Usuario '.$usuario->getDisplayname().' se ha dado de baja.';
            $appUserInfoLogger->warning($message);

            // redirigimos a portada
            return $this->redirectToRoute('app_login');
        }

        return $this->renderForm("user/delete.html.twig", [
            "formulario" => $formulario,
            "usuario" => $usuario
        ]);

    }
}
