<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Model\City;
use App\Form\AgeCategoryType;
use App\Form\CityType;
use App\Model\CityCollection;
use App\Repository\AdresseRepository;
use App\Repository\CadeauRepository;
use App\Repository\PanierRepository;
use App\Repository\UserRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AdminController
 * @package App\Controller
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/pereNoel", name="admin_pereNoel")
     */
    public function index(Request $request, UserRepository $repository, PanierRepository $panierRepository, PaginatorInterface $paginator): Response
    {
        $number = 1;
        $users = $repository->findAll();

        $pagUsers = $paginator->paginate(
            $users,
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('admin/index.html.twig', [
            'users' => $pagUsers,
            "number" => $number,
        ]);
    }

    /**
     * @Route("/pereNoel/list/{id}", name="admin_pereNoel_list")
     */
    public function showList($id, UserRepository $userRepository, AdresseRepository $adresseRepository): Response
    {
        $user = $userRepository->findOneById($id);
        $userAdress = $userRepository->findBy(["adresses" => $user->getAdresses()]);

        return $this->render('admin/list-cadeaux.html.twig', [
            'user' => $user,
            'adress' => $userAdress
        ]);
    }

    /**
     *@Route("/pereNoel/adresses", name="pereNoel_adresses")
     */
    public function adressesUsers(Request $request, UserRepository $userRepository, AdresseRepository  $adresseRepository)
    {
        $allAddresses = $adresseRepository->findUsersAddresses();
        $cities = $adresseRepository->findUniqueAddresses();
        $citiesArray = [];

        foreach ($cities as $city) {
            if (!in_array($city['ville'], $citiesArray)) {
                $citiesArray[] = $city['ville'];
            }
        }
        $form = $this->createFormBuilder($citiesArray)
            ->add('city', ChoiceType::class, [
                'choices' => array_combine(array_values($citiesArray), array_values($citiesArray))
            ])
            ->add('submit', SubmitType::class, ['label' => 'Filtrer', 'attr' => ['class' => 'btn btn-success']])
            ->add('reset', SubmitType::class, ['label' => 'Réinitialiser', 'attr' => ['class' => 'btn btn-dark']])
        ->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            if ($form->get('submit')->isClicked()) {
                $allAddresses = $adresseRepository->findAddressByCity($form->get('city')->getData());
            }
        }



        return $this->render('admin/list_adresses.html.twig', [
            'addresses' => $allAddresses,
//            'cities' => array_unique($villes),
//            'adresses' => $adresses,
            'form' => $form->createView()
        ]);
    }

    /**
     *@Route("/pereNoel/stats", name="pereNoel_stats")
     */
    public function stats(CadeauRepository $cadeauRepository, PanierRepository $panierRepository)
    {
        $cadeauxCommandes = $cadeauRepository->findCadeauxCommandes();

        return $this->render('admin/stats.html.twig', [
            'cadeaux' => $cadeauxCommandes,
        ]);
    }

    /**
     *@Route("/pereNoel/cadeaux/cadeau/{id}", name="pereNoel_cadeaux_cadeau")
     */
    public function cadeauDansPanier($id, PanierRepository $panierRepository, CadeauRepository $cadeauRepository)
    {
        $cadeau = $cadeauRepository->find($id);
        $paniers = $cadeau->getPaniers(); // $panierRepository->findBy(["cadeau" => $cadeau]);
        $homme = 0;
        $femme = 0;

        foreach ($paniers as $item) {
            if($item->getPerson()->getSexe() == "Homme") {
                $homme ++;
            } else {
                $femme ++;
            }
        }
        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable(
            [['Task', 'Hours per Day'],
                ['homme',     $homme],
                ['femme',      $femme],
            ]
        );
        $pieChart->getOptions()->setTitle("Porcentage (h|f) qui ont commendés ".$cadeau->getDesignation());
        $pieChart->getOptions()->setHeight(500);
        $pieChart->getOptions()->setWidth(900);
        $pieChart->getOptions()->getTitleTextStyle()->setBold(true);
        $pieChart->getOptions()->getTitleTextStyle()->setColor('#009900');
        $pieChart->getOptions()->setBackgroundColor('#FEFBFB');
        $pieChart->getOptions()->getTitleTextStyle()->setItalic(true);
        $pieChart->getOptions()->getTitleTextStyle()->setFontName('Arial');
        $pieChart->getOptions()->getTitleTextStyle()->setFontSize(20);

        return $this->render('admin/cadeau_panier.html.twig', [
            'cadeau' => $cadeau,
            'paniers' => $paniers,
            'piechart' => $pieChart
        ]);
    }

    /**
     *@Route("/pereNoel/commandes", name="pereNoel_commandes")
     */
    public function commandes(PanierRepository $panierRepository)
    {
        $paniers = $panierRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/commandes.html.twig', [
            'paniers' => $paniers,
        ]);
    }

    /**
     *@Route("/pereNoel/categories", name="pereNoel_categories", methods={"GET", "POST"})
     */
    public function categories( Request $request, CadeauRepository $cadeauRepository)
    {
        $cadeaux = $cadeauRepository->findCadeauxCommandes();

        //dd($cadeaux);

        $form = $this->createForm(AgeCategoryType::class);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
           $cadeaux = $cadeauRepository->findCadeauxByAge($form->get('age')->getData());
        }

        return $this->render('admin/categories.html.twig', [
            'cadeaux' => $cadeaux,
            'form' => $form->createView()
        ]);
    }

    /**
     *@Route("/pereNoel/validerCommande/{id}", name="valider_commande", methods={"GET", "POST"})
     */
    public function validerCommande(PanierRepository $panierRepository ,$id, UserRepository $userRepository)
    {
        $panier = $panierRepository->find($id);

        $panier->setStatus(Panier::STATUS_VALIDATED);
        $panier->setValidatedAt(new \DateTime());

        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('pereNoel_commandes');
    }
}
