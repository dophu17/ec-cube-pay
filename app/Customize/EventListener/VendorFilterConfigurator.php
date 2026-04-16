<?php

namespace Customize\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Member;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Security;

class VendorFilterConfigurator
{
    private $em;
    private $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: \Symfony\Component\HttpKernel\KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();

        // Only enable the filter for Admin routes
        $isAdminRoute = $request->get('_route') && (strpos($request->get('_route'), 'admin_') === 0);

        if ($isAdminRoute && $user instanceof Member && $user->isVendor()) {
            $filter = $this->em->getFilters()->enable('vendor_filter');
            $filter->setParameter('vendor_id', $user->getId());
        }
    }
}
