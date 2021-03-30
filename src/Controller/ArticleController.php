<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\CreateArticleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleController extends AbstractController
{
    //ALL ARTICLES
    /**
     * @Route("/", name="home")
     */
    public function all()           //All Articles + Nav Categories
    {
        $articles = $this->getDoctrine()
            ->getRepository(Article::class)
            ->findAll();

        $categories = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findAll();

        return $this->render('article/index.html.twig', array(
            'articles' => $articles,
            'categories' => $categories,
        ));
    }

    //NAV CATEGORIES
    /**
     * @Route("/article/{category}", name="article_category")
     */
    public function category($category)
    {
        $articles = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findOneBy([
                'name' => $category,
            ]);

        $categories = $this->getDoctrine()
            ->getRepository(Category::class)
            ->findAll();

        return $this->render('article/index.html.twig', array(
            'articles' => $articles->getArticles(),
            'categories' => $categories,
        ));
    }


    // CREATION ARTICLE
    /**
     * @Route("/create/article", name="create_article")
     */
    public function index(Request $request, SluggerInterface $slugger)
    {

        if (!$this->denyAccessUnlessGranted('ROLE_ADMIN')) {    // Blocare acces Article Creation pt NON-Admin 
            $article = new Article();
            $form = $this->createForm(CreateArticleType::class, $article);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $ImageFile = $form->get('image')->getData();
                if ($ImageFile) {
                    $originalFilename = pathinfo($ImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $ImageFile->guessExtension();

                    // Move the file to the directory where brochures are stored
                    try {
                        $ImageFile->move(
                            $this->getParameter('upload_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // Handling exception if something happens during file upload
                    }
                    $article->setImage($newFilename);
                    $doctrine = $this->getDoctrine()->getManager();
                    $doctrine->persist($article);
                    $doctrine->flush();
                }
                return $this->redirectToRoute('home');
            }
            return $this->render('article/create.html.twig', ['form' => $form->createView(),
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }

    }
}
