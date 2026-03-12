<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_notice', methods: ['GET'])]
    public function legalNotice(): Response
    {
        return $this->render('legal/legal_notice.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'app_privacy_policy', methods: ['GET'])]
    public function privacyPolicy(): Response
    {
        return $this->render('legal/privacy_policy.html.twig');
    }

    #[Route('/donnees-personnelles', name: 'app_personal_data', methods: ['GET'])]
    public function personalData(): Response
    {
        return $this->render('legal/personal_data.html.twig');
    }
}
