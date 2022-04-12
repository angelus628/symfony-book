<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ConferenceController extends AbstractController
{
    private Environment $environment;
    private ConferenceRepository $conferenceRepository;
    private CommentRepository $commentRepository;

    public function __construct(
        Environment $environment,
        ConferenceRepository $conferenceRepository,
        CommentRepository $commentRepository
    ) {
        $this->environment = $environment;
        $this->conferenceRepository = $conferenceRepository;
        $this->commentRepository = $commentRepository;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return new Response($this->environment->render('conference/index.html.twig', [
            'conferences' => $this->conferenceRepository->findAll()
        ]));
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(Request $request, Conference $conference): Response
    {
        $offset = max(0, $request->query->get('offset', 0));
        $paginator = $this->commentRepository->getCommentPaginator($conference, $offset);

        return new Response($this->environment->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min($paginator->count(), $offset + CommentRepository::PAGINATOR_PER_PAGE),
        ]));
    }
}
