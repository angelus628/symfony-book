<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ConferenceController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Environment $environment;
    private ConferenceRepository $conferenceRepository;
    private CommentRepository $commentRepository;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;

    public function __construct(
        Environment $environment,
        ConferenceRepository $conferenceRepository,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus
    ) {
        $this->environment = $environment;
        $this->conferenceRepository = $conferenceRepository;
        $this->commentRepository = $commentRepository;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    #[Route('/conference_header', name: 'conference_header')]
    public function conferenceHeader(): Response
    {
        $response = new Response($this->environment->render('conference/header.html.twig', [
            'conferences' => $this->conferenceRepository->findAll()
        ]));

        return $response->setSharedMaxAge(3600);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        $response = new Response($this->environment->render('conference/index.html.twig', [
            'conferences' => $this->conferenceRepository->findAll()
        ]));

        $response->setSharedMaxAge(3600);
        return $response;
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(Request $request, Conference $conference, string $photoDir): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            /** @var UploadedFile $photo */
            if ($photo = $form['photo']->getData()) {
                $filename = sprintf('%s.%s', bin2hex(random_bytes(6)), $photo->guessExtension());

                try {
                    $photo->move($photoDir, $filename);
                    $comment->setPhotoFilename($filename);
                } catch (FileException $fileException) {
                    $this->logger->notice(sprintf('Could not upload file: %s', $fileException->getMessage()));
                }
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'referrer' => $request->headers->get('referrer'),
                'permalink' => $request->getUri(),
            ];

            $this->messageBus->dispatch(new CommentMessage($comment->getId(), $context));

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, (int) $request->query->get('offset', 0));
        $paginator = $this->commentRepository->getCommentPaginator($conference, $offset);

        return new Response($this->environment->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min($paginator->count(), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comments_form' => $form->createView(),
        ]));
    }
}
