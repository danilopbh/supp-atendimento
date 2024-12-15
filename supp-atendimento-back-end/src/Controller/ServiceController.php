<?php

namespace App\Controller;

use App\Entity\Service;
use App\Service\ServiceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;



#[Route('/api/service')]
class ServiceController extends AbstractController
{
    public function __construct(private ServiceManager $serviceManager)
    {
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $service = $this->serviceManager->createService($data);
            
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $service->getId(),
                    'title' => $service->getTitle(),
                    'description' => $service->getDescription(),
                    'status' => $service->getStatus(),
                    'sector' => [
                        'id' => $service->getSector()->getId(),
                        'name' => $service->getSector()->getName()
                    ],
                    'requester' => [
                        'id' => $service->getRequester()->getId(),
                        'name' => $service->getRequester()->getName(),
                        'email' => $service->getRequester()->getEmail()
                    ],
                    'dates' => [
                        'created' => $service->getDateCreate()->format('Y-m-d H:i:s')
                    ]
                ]
            ], 201);
            
        } catch (BadRequestException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

   // In ServiceController.php
#[Route('/sector/{sector}', methods: ['GET'])]
public function listBySector(string $sector): JsonResponse
{
    try {
        // Get services for the specified sector
        
        $services = $this->serviceManager->getServicesBySector($sector);
    
        // Transform the services into a format suitable for JSON response
        $response = array_map(function ($service) {
            return [
                'id' => $service->getId(),
                'title' => $service->getTitle(),
                'description' => $service->getDescription(),
                'status' => $service->getStatus(),
                'requester' => [
                    'id' => $service->getRequester()?->getId(),
                    'name' => $service->getRequester()?->getName(),
                    'email' => $service->getRequester()?->getEmail(),
                ],
                'responsible' => [
                    'id' => $service->getReponsible()?->getId(),
                    'name' => $service->getReponsible()?->getName(),
                    'function' => $service->getReponsible()?->getFunction(),
                ],
                'dates' => [
                    'created' => $service->getDateCreate()?->format('Y-m-d H:i:s'),
                    'updated' => $service->getDateUpdate()?->format('Y-m-d H:i:s'),
                    'concluded' => $service->getDateConclusion()?->format('Y-m-d H:i:s'),
                ],
            ];
        }, $services);

        return new JsonResponse([
            'success' => true,
            'data' => $response,
            'count' => count($response)
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Error fetching services: ' . $e->getMessage()
        ], 500);
    }
}

#[Route('/attendant/{id}', methods: ['GET'])]
public function listByAttendant(int $id): JsonResponse
{
    try {
        $services = $this->serviceManager->getServicesByAttendant($id);
    
        $response = array_map(function ($service) {
            return [
                'id' => $service->getId(),
                'title' => $service->getTitle(),
                'description' => $service->getDescription(),
                'status' => $service->getStatus(),
                'requester' => [
                    'id' => $service->getRequester()?->getId(),
                    'name' => $service->getRequester()?->getName(),
                    'email' => $service->getRequester()?->getEmail(),
                ],
                'sector' => [
                    'id' => $service->getSector()?->getId(),
                    'name' => $service->getSector()?->getName(),
                ],
                'dates' => [
                    'created' => $service->getDateCreate()?->format('Y-m-d H:i:s'),
                    'updated' => $service->getDateUpdate()?->format('Y-m-d H:i:s'),
                    'concluded' => $service->getDateConclusion()?->format('Y-m-d H:i:s'),
                ],
            ];
        }, $services);

        return new JsonResponse([
            'success' => true,
            'data' => $response,
            'count' => count($response)
        ]);

    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Error fetching services: ' . $e->getMessage()
        ], 500);
    }
}

    #[Route('/{id}/status', methods: ['PUT'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        try {
            // Decodifica o corpo da requisição
            $data = json_decode($request->getContent(), true);
            
            // Validação básica dos dados recebidos
            if (!isset($data['status']) || !isset($data['comment'])) {
                throw new BadRequestException('Status and comment are required');
            }
    
            // Busca o serviço no ServiceManager
            $service = $this->serviceManager->findById($id);
            
            if (!$service) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }
    
            // Atualiza o status do serviço
            $this->serviceManager->updateServiceStatus(
                service: $service,
                newStatus: $data['status'],
                comment: $data['comment']
            );
    
            // Prepara a resposta com os dados atualizados
            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $service->getId(),
                    'title' => $service->getTitle(),
                    'status' => $service->getStatus(),
                    'dates' => [
                        'created' => $service->getDateCreate()->format('Y-m-d H:i:s'),
                        'updated' => $service->getDateUpdate()->format('Y-m-d H:i:s'),
                        'concluded' => $service->getDateConclusion()?->format('Y-m-d H:i:s')
                    ],
                    'responsible' => [
                        'id' => $service->getReponsible()?->getId(),
                        'name' => $service->getReponsible()?->getName()
                    ],
                    'history' => array_map(function($history) {
                        return [
                            'date' => $history->getDateHistory()->format('Y-m-d H:i:s'),
                            'status_prev' => $history->getStatusPrev(),
                            'status_post' => $history->getStatusPost(),
                            'comment' => $history->getComment(),
                            'responsible' => [
                                'id' => $history->getResponsible()?->getId(),
                                'name' => $history->getResponsible()?->getName()
                            ]
                        ];
                    }, $service->getHistories()->toArray())
                ]
            ]);
    
        } catch (BadRequestException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }


    // Em ServiceController.php

#[Route('/{id}/transfer', methods: ['PUT'])]
public function transferToAttendant(int $id, Request $request): JsonResponse
{
    try {
        $data = json_decode($request->getContent(), true);
        
        // Validação dos dados
        if (!isset($data['attendant_id']) || !isset($data['comment'])) {
            throw new BadRequestException('Attendant ID and comment are required');
        }

        // Buscar o serviço
        $service = $this->serviceManager->findById($id);
        
        if (!$service) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        }

        // Transferir o ticket
        $this->serviceManager->transferTicket(
            service: $service,
            newAttendantId: $data['attendant_id'],
            comment: $data['comment']
        );

        // Preparar resposta
        return new JsonResponse([
            'success' => true,
            'data' => [
                'id' => $service->getId(),
                'title' => $service->getTitle(),
                'status' => $service->getStatus(),
                'responsible' => [
                    'id' => $service->getReponsible()?->getId(),
                    'name' => $service->getReponsible()?->getName(),
                    'function' => $service->getReponsible()?->getFunction()
                ],
                'dates' => [
                    'created' => $service->getDateCreate()->format('Y-m-d H:i:s'),
                    'updated' => $service->getDateUpdate()->format('Y-m-d H:i:s')
                ],
                'history' => array_map(function($history) {
                    return [
                        'date' => $history->getDateHistory()->format('Y-m-d H:i:s'),
                        'status_prev' => $history->getStatusPrev(),
                        'status_post' => $history->getStatusPost(),
                        'comment' => $history->getComment(),
                        'responsible' => [
                            'id' => $history->getResponsible()?->getId(),
                            'name' => $history->getResponsible()?->getName()
                        ]
                    ];
                }, $service->getHistories()->toArray())
            ]
        ]);
        
    } catch (BadRequestException $e) {
        return new JsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ], 400);
    } catch (\Exception $e) {
        return new JsonResponse([
            'success' => false,
            'message' => 'Internal server error: ' . $e->getMessage()
        ], 500);
    }
}
}