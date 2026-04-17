<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class EquipmentImageController extends AbstractController
{
    #[Route('/uploads/equipements/{filename}', name: 'equipment_image_show', requirements: ['filename' => '[^/]+'], methods: ['GET'])]
    public function show(string $filename): BinaryFileResponse
    {
        $safeFilename = basename($filename);
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/equipements/' . $safeFilename;

        if ($safeFilename !== $filename || !is_file($path)) {
            throw new NotFoundHttpException('Image not found.');
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $safeFilename);

        return $response;
    }
}
