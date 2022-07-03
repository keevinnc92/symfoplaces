<?php

namespace App\Controller;

use App\Entity\Place;
use App\Form\PlaceType;
use App\Repository\PlaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Form\SearchFormType;

use Psr\Log\LoggerInterface;
use App\Service\SimpleSearchService;
use App\Service\PaginatorService;



#[Route('/place')]
class PlaceController extends AbstractController
{

    #[Route('/new', name: 'app_place_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PlaceRepository $placeRepository, LoggerInterface $appInfoLogger): Response
    {
        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // add user 
            if ($this->getUser()) {
                $place->setUser($this->getUser());
                $placeRepository->add($place, true);
                
                $message = 'Place created successfully';
                $this->addFlash('success', $message);

            }else{
            // $this->addFlash('success', 'Your email address has been verified.');
                $message = 'User could not be retrieved';

                $this->addFlash('danger', $message);
            }
            
            // log
            $appInfoLogger->info($message);

            return $this->redirectToRoute('app_place_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('place/new.html.twig', [
            'place' => $place,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'app_place_show', methods: ['GET'])]
    public function show(Place $place): Response
    {
        return $this->render('place/show.html.twig', [
            'place' => $place,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'app_place_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Place $place, PlaceRepository $placeRepository): Response
    {
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $placeRepository->add($place, true);

            return $this->redirectToRoute('app_place_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('place/edit.html.twig', [
            'place' => $place,
            'form' => $form,
        ]);
    }

    #[Route('delete/{id<\d+>}', name: 'app_place_delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, Place $place, PlaceRepository $placeRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$place->getId(), $request->request->get('_token'))) {
            $placeRepository->remove($place, true);
        }
        return $this->redirectToRoute('app_place_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/search', name: 'place_search', methods: ['GET', 'POST'])]
    public function search(Request $request, SimpleSearchService $busqueda):Response{

        // crea el formulario
        $formulario = $this->createForm(SearchFormType::class, $busqueda, [
            'field_choices' => [
                'Name' => 'name',
                'Valoration' => 'valoration',
                'Country' => 'country',
                'Type' => 'type'
            ],
            'order_choices' => [
                'ID' => 'id',
                'Name' => 'name',
                'Valoration' => 'valoration',
                'Country' => 'country',
                'Type' => 'type'
            ]
        ]);

        $formulario->get('campo')->setData($busqueda->campo);
        $formulario->get('orden')->setData($busqueda->orden);

        // gestiona el formulario y recupera los valores de búsqueda
        $formulario->handleRequest($request);

        //realiza la búsqueda
        $places = $busqueda->search('App\Entity\Place');

        //retorna la vista con los resultados
        return $this->renderForm("place/search.html.twig", [
            "formulario" => $formulario,
            "places" => $places
        ]);

    }

    #[Route("/page/{pagina}", defaults: ["pagina"=>1], name: 'app_place_index', methods: ['GET'])]

    public function index(PlaceRepository $placeRepositoryint, $pagina, PaginatorService $paginator): Response
    {
        // le indicamos al paginador que tabajaremos con Pelicula
        $paginator->setEntityType('App\Entity\Place');

        // le pedimos que nos recupere todas las películas con paginación
        $places = $paginator->findAllEntities($pagina);

        // carga la visa del listado de películas, pasándole toda la información
        return $this->renderForm("place/index.html.twig", [
            "places" => $places,
            "paginator" => $paginator
        ]);

    }



}
