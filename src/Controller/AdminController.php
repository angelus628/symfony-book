<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

/**
 * Class AdminController
 * @package App\Controller
 */
class AdminController extends AbstractController
{
    private Environment $environment;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;

    public function __construct(
        Environment $environment,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus
    ) {
        $this->environment = $environment;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    #[Route('/admin/comment/review/{id}', name: 'review_comment')]
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
}
