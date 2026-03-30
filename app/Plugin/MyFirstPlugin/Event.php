<?php

namespace Plugin\MyFirstPlugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            \Symfony\Component\HttpKernel\KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(\Symfony\Component\HttpKernel\Event\ResponseEvent $event)
    {
        // Chỉ chạy cho giao diện người dùng (không chạy trong admin)
        $request = $event->getRequest();
        if (strpos($request->getPathInfo(), '/admin') === 0) {
            return;
        }

        $response = $event->getResponse();
        $content = $response->getContent();

        // Chèn đoạn text Hello World ngay sau mở tag <body>
        $snippet = '<div style="background: #ff5722; color: white; padding: 15px; text-align: center; font-size: 20px; font-weight: bold; z-index: 9999; position: sticky; top: 50px;">👋 Hello World! Đây là MyFirstPlugin đang chạy! (KernelEvents)</div>';

        if (strpos($content, '<body') !== false) {
            $content = preg_replace('/(<body[^>]*>)/', '$1' . $snippet, $content, 1);
            $response->setContent($content);
        }
    }
}
