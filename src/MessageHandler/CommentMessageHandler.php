<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

/**
 * Class CommentMessageHandler
 * @package App\MessageHandler
 */
final class CommentMessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private CommentRepository $commentRepository;

    public function __construct(EntityManagerInterface $entityManager, CommentRepository $commentRepository)
    {
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
    }

    public function __invoke(CommentMessage $commentMessage): void
    {
        $comment = $this->commentRepository->find($commentMessage->getId());

        if (!$comment) {
            return;
        }

        $comment->setState('published');
        $this->entityManager->flush();
    }
}
