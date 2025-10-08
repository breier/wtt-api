<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Adds CORS headers for API responses and handles preflight OPTIONS requests.
 */
final class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly array $allowedOrigins = ['*'],
        private readonly array $allowedMethods = ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],
        private readonly array $allowedHeaders = ['Content-Type','Authorization','X-Requested-With'],
        private readonly bool $allowCredentials = true,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2048],
            KernelEvents::RESPONSE => ['onKernelResponse', -2048],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== Request::METHOD_OPTIONS) {
            return;
        }

        if (!$request->headers->has('Origin') || !$request->headers->has('Access-Control-Request-Method')) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if (!$this->isOriginAllowed($origin)) {
            return;
        }

        $response = new Response();
        $this->applyHeaders($response, $origin, isPreflight: true);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $origin = $request->headers->get('Origin');
        if (!$origin || !$this->isOriginAllowed($origin)) {
            return;
        }

        $response = $event->getResponse();
        $this->applyHeaders($response, $origin, isPreflight: false);
    }

    private function isOriginAllowed(?string $origin): bool
    {
        if ($origin === null) {
            return false;
        }
        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }
        return in_array($origin, $this->allowedOrigins, true);
    }

    private function applyHeaders(Response $response, string $origin, bool $isPreflight): void
    {
        $allowOrigin = in_array('*', $this->allowedOrigins, true)
            ? ($this->allowCredentials ? $origin : '*')
            : $origin;

        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);

        if ($this->allowCredentials) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        $response->headers->set('Vary', 'Origin');

        if ($isPreflight) {
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods));
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders));
            $response->headers->set('Access-Control-Max-Age', '86400');
            $response->setStatusCode(Response::HTTP_NO_CONTENT);
        }
    }
}
