<?php

namespace Plugin\AmazonPayMini\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\PaymentRepository;
use Plugin\AmazonPayMini\Entity\Config;
use Plugin\AmazonPayMini\Form\Type\Admin\ConfigType;
use Plugin\AmazonPayMini\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(
        ConfigRepository $configRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->configRepository = $configRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/%eccube_admin_route%/amazon_pay_mini/config", name="amazon_pay_mini_admin_config")
     * @Template("@AmazonPayMini/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        if (!$Config) {
            $Config = new Config();
        }

        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush();

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('amazon_pay_mini_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
