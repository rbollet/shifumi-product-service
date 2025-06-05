<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/create', name: 'create_product', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice((float) $data['price']);

        $em->persist($product);
        $em->flush();

        return $this->json(['message' => 'Product created'], Response::HTTP_CREATED);
    }

    #[Route('/list', name: 'list_products', methods: ['GET'])]
    public function getProducts(EntityManagerInterface $em): JsonResponse
    {
        // On vérifie si l'utilisateur est authentifié
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $products = $em->getRepository(Product::class)->findAll();

        $data = array_map(fn(Product $product) => [
            'id'          => $product->getId(),
            'name'        => $product->getName(),
            'description' => $product->getDescription(),
            'price'       => $product->getPrice(),
            'created_at'  => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at'  => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], $products);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'get_product', methods: ['GET'])]
    public function get(int $id, ProductRepository $repo): JsonResponse
    {
        // On vérifie si l'utilisateur est authentifié
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $product = $repo->find($id);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], 404);
        }

        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'description' => $product->getDescription(),
            'created_at' => $product->getCreatedAt()->format('c'),
            'updated_at' => $product->getUpdatedAt()->format('c'),
        ]);
    }
}
