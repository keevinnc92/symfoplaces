<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use App\Form\ContactFormType;


class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response{       
        return $this->render('home.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, MailerInterface $mailer): Response{

    	$form = $this->CreateForm(ContactFormType::class);
    	$form->handleRequest($request);

    	if ($form->isSubmitted() && $form->isValid()) {
    		$datos = $form->getData();

    		$email = new Email();
    		$email->from($datos['email'])
    			->to($this->getParameter('app.admin_email')) //viene de services.yaml
    			->subject($datos['subject'])
    			->text($datos['message']);

    		$mailer->send($email);
    		
    		$this->addFlash('success', 'Message sent successfully');
    		return $this->redirectToRoute('contact');
    	}
    	
    	return $this->renderForm("contact.html.twig", ["form"=>$form]);

    }



    #[Route('/places', name: 'places')]
    public function test(): Response{       
        return $this->render('test.html.twig');
    }

}
