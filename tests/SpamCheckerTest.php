<?php

declare(strict_types=1);

namespace App\Tests;

use App\Client\SpamChecker;
use App\Entity\Comment;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class SpamCheckerTest
 * @package App\Tests
 */
class SpamCheckerTest extends TestCase
{
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     */
    public function testSpamScoreWithInvalidRequest(): void
    {
        $comment = new Comment();
        $comment
            ->setAuthor('test user')
            ->setEmail('test@test.test')
            ->setText('test text')
            ->setCreatedAtValue();
        $context = [];

        $httpClient = new MockHttpClient([new MockResponse('invalid', ['response_headers' => ['x-akismet-debug-help: Invalid key']])]);
        $checker = new SpamChecker('abcde');
        $checker->setClient($httpClient);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid (Invalid key).');
        $checker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider getComments
     */
    public function testSpamScore(int $expectedScore, ResponseInterface $response, Comment $comment, array $context): void
    {
        $httpClient = new MockHttpClient([$response]);
        $checker = new SpamChecker('abcde');
        $checker->setClient($httpClient);

        $score = $checker->getSpamScore($comment, $context);
        self::assertSame($expectedScore, $score);
    }

    public function getComments(): iterable
    {
        $comment = new Comment();
        $comment
            ->setAuthor('test user')
            ->setEmail('test@test.test')
            ->setText('test text')
            ->setCreatedAtValue();
        $context = [];

        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatant_spam' => [2, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'ham' => [0, $response, $comment, $context];
    }
}
