<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Class CommentMessageHandler
 * @package App\MessageHandler
 */
final class CommentMessageHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private EntityManagerInterface $entityManager;
    private CommentRepository $commentRepository;
    private MessageBusInterface $messageBus;
    private WorkflowInterface $commentStateMachine;

    public function __construct(
        EntityManagerInterface $entityManager,
        CommentRepository $commentRepository,
        MessageBusInterface $messageBus,
        WorkflowInterface $commentStateMachine
    ) {
        $this->entityManager = $entityManager;
        $this->commentRepository = $commentRepository;
        $this->messageBus = $messageBus;
        $this->commentStateMachine = $commentStateMachine;
    }

    public function __invoke(CommentMessage $commentMessage): void
    {
        $comment = $this->commentRepository->find($commentMessage->getId());

        if (!$comment) {
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = 0;
            $transition = 'accept';

            if ($score === 2) {
                $transition = 'reject_spam';
            } elseif ($score === 1) {
                $transition = 'might_be_spam';
            }

            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            $this->messageBus->dispatch($commentMessage);
        } elseif ($this->commentStateMachine->can($comment, 'publish') || $this->commentStateMachine->can($comment, 'publish_ham')) {
            $this->commentStateMachine->apply($comment, $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham');
            $this->entityManager->flush();
        } else {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState()
            ]);
        }

        $comment->setState('published');
    }
}
