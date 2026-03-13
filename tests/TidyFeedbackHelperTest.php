<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedback\Tests;

use ItkDev\TidyFeedback\TidyFeedbackHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[CoversClass(TidyFeedbackHelper::class)]
class TidyFeedbackHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $this->setStaticConfig([
            'cache_dir' => null,
            'database_url' => null,
            'debug' => false,
            'default_locale' => 'da',
            'dev_mode' => false,
            'disable' => false,
            'disable_pattern' => '@^/tidy-feedback$@',
            'users' => [],
        ]);
    }

    public function testAuthorizeAllowsAccessWhenNoUsersConfigured(): void
    {
        $helper = $this->createHelper();
        $request = Request::create('/tidy-feedback');

        $helper->authorize($request);
        $this->addToAssertionCount(1);
    }

    public function testAuthorizeThrowsWhenUsersConfiguredAndNoCredentials(): void
    {
        $this->setStaticConfigValue('users', ['admin' => 'secret']);

        $helper = $this->createHelper();
        $request = Request::create('/tidy-feedback');

        $this->expectException(UnauthorizedHttpException::class);
        $helper->authorize($request);
    }

    public function testAuthorizeThrowsOnWrongPassword(): void
    {
        $this->setStaticConfigValue('users', ['admin' => 'secret']);

        $helper = $this->createHelper();
        $request = Request::create('https://admin:wrong@localhost/tidy-feedback');

        $this->expectException(UnauthorizedHttpException::class);
        $helper->authorize($request);
    }

    public function testAuthorizeAllowsCorrectCredentials(): void
    {
        $this->setStaticConfigValue('users', ['admin' => 'secret']);

        $helper = $this->createHelper();
        $request = Request::create('https://admin:secret@localhost/tidy-feedback');

        $helper->authorize($request);
        $this->addToAssertionCount(1);
    }

    public function testAuthorizeThrowsWhenUserNotFound(): void
    {
        $this->setStaticConfigValue('users', ['admin' => 'secret']);

        $helper = $this->createHelper();
        $request = Request::create('https://unknown:secret@localhost/tidy-feedback');

        $this->expectException(UnauthorizedHttpException::class);
        $helper->authorize($request);
    }

    public function testAuthorizeThrowsWithEmptyPassword(): void
    {
        $this->setStaticConfigValue('users', ['admin' => 'secret']);

        $helper = $this->createHelper();
        $request = Request::create('https://admin:@localhost/tidy-feedback');

        $this->expectException(UnauthorizedHttpException::class);
        $helper->authorize($request);
    }

    public function testGetSubscribedEventsReturnsKernelResponse(): void
    {
        $events = TidyFeedbackHelper::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.response', $events);
        $this->assertSame(['onKernelResponse'], $events['kernel.response']);
    }

    public function testGenerateUrl(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('tidy_feedback_show', ['id' => 42])
            ->willReturn('/tidy-feedback/42');

        $helper = new TidyFeedbackHelper($urlGenerator);
        $result = $helper->generateUrl('tidy_feedback_show', ['id' => 42]);

        $this->assertSame('/tidy-feedback/42', $result);
    }

    private function createHelper(): TidyFeedbackHelper
    {
        return new TidyFeedbackHelper(
            $this->createMock(UrlGeneratorInterface::class),
        );
    }

    private function setStaticConfig(array $config): void
    {
        $property = new \ReflectionProperty(TidyFeedbackHelper::class, 'config');
        $property->setValue(null, $config);
    }

    private function setStaticConfigValue(string $key, mixed $value): void
    {
        $property = new \ReflectionProperty(TidyFeedbackHelper::class, 'config');
        $config = $property->getValue();
        $config[$key] = $value;
        $property->setValue(null, $config);
    }
}
