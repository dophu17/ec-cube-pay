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
        $user = $this->security->getUser();
        if ($user instanceof Member && $user->isVendor()) {
            $filter = $this->em->getFilters()->enable('vendor_filter');
            $filter->setParameter('vendor_id', $user->getId());
        }
    }
}
