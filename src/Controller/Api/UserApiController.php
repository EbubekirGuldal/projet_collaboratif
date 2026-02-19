<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class UserApiController extends AbstractController
{
    #[Route('/api/user')]
    public function index()
    {

    }
}