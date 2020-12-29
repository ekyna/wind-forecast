<?php

namespace App\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class KernelListener
 * @package App\Listener
 * @author  Étienne Dauvergne <contact@ekyna.com>
 */
class KernelListener implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $allowedOrigins;


    /**
     * Constructor.
     *
     * @param string|null $allowedOrigins
     */
    public function __construct(string $allowedOrigins = null)
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * Kernel response event handler.
     *
     * @param ResponseEvent $event
     */
    public function onResponse(ResponseEvent $event): void
    {
        if (empty($this->allowedOrigins)) {
            return;
        }

        $response = $event->getResponse();

        if ($response->headers->has('Access-Control-Allow-Origin')) {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $this->allowedOrigins);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }
}
