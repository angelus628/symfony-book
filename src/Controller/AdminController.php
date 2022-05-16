<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

/**
 * Class AdminController
 * @package App\Controller
 */
#[Route('/admin')]
class AdminController extends AbstractController
{
    private Environment $environment;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private KernelInterface $kernel;
    private StoreInterface $store;

    public function __construct(
        Environment $environment,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus,
        KernelInterface $kernel,
        StoreInterface $store
    ) {
        $this->environment = $environment;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
        $this->kernel = $kernel;
        $this->store = $store;
    }

    #[Route('/comment/review/{id}', name: 'review_comment')]
    public function reviewComment(Request $request, Comment $comment, Registry $registry): Response
    {
        $accepted = !$request->query->get('reject');
        $machine = $registry->get($comment);

        if ($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif ($machine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the right state');
        }

        $machine->apply($comment, $transition);
        $this->entityManager->flush();

        if ($accepted) {
            $this->messageBus->dispatch(new CommentMessage($comment->getId(), [
                'user_ip' => $request->getClientIp(),
                'referrer' => $request->headers->get('referrer'),
                'permalink' => $request->getUri(),
            ]));
        }

        return new Response($this->environment->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment
        ]));
    }

    #[Route('/http-cache/{uri<.*>}', methods: ['PURGE'])]
    public function purgeHttpCache(Request $request, string $uri): Response
    {
        if ($this->kernel->getEnvironment() === 'prod') {
            return new Response('KO', Response::HTTP_BAD_REQUEST);
        }

        $this->store->purge(sprintf('%s/%s', $request->getSchemeAndHttpHost(), $uri));
        return new Response('Done');
    }
}
