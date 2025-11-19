<?php

namespace App\Controller\Locataire;

use App\Entity\Locataire;
use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de messagerie pour les locataires
 * Gère les conversations et l'envoi de messages
 */
#[Route('/dashboard/locataire/messages', name: 'locataire_messages_')]
#[IsGranted('ROLE_LOCATAIRE')]
class MessageController extends AbstractController
{
    /**
     * Liste de toutes les conversations du locataire (boîte de réception)
     * Les conversations sont triées par date du dernier message (les plus récentes en premier)
     */
    #[Route('', name: 'index')]
    public function index(ConversationRepository $conversationRepo): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer toutes les conversations du locataire, triées par date du dernier message
        $conversations = $conversationRepo->createQueryBuilder('c')
            ->where('c.locataire = :locataire')
            ->andWhere('c.estArchivee = false')
            ->setParameter('locataire', $locataire)
            ->orderBy('c.dernierMessageDate', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Compter le nombre de messages non lus pour chaque conversation
        $messagesNonLus = [];
        foreach ($conversations as $conversation) {
            $count = $this->countUnreadMessages($conversation, $locataire);
            $messagesNonLus[$conversation->getId()] = $count;
        }
        
        // Compter le total de messages non lus
        $totalNonLus = array_sum($messagesNonLus);
        
        return $this->render('locataire/messages/index.html.twig', [
            'conversations' => $conversations,
            'messagesNonLus' => $messagesNonLus,
            'totalNonLus' => $totalNonLus,
        ]);
    }
    
    /**
     * Afficher une conversation spécifique avec tous ses messages
     * Marque automatiquement les messages comme lus
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer la conversation
        $conversation = $conversationRepo->find($id);
        
        // Vérifier que la conversation existe et appartient au locataire
        if (!$conversation || $conversation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }
        
        // Marquer tous les messages non lus comme lus
        $this->markMessagesAsRead($conversation, $locataire, $entityManager);
        
        // Récupérer tous les messages de la conversation, triés par date d'envoi
        $messages = $conversation->getMessages()->toArray();
        usort($messages, function($a, $b) {
            return $a->getDateEnvoi() <=> $b->getDateEnvoi();
        });
        
        return $this->render('locataire/messages/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }
    
    /**
     * Envoyer un nouveau message dans une conversation
     */
    #[Route('/{id}/envoyer', name: 'send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(
        int $id,
        Request $request,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer la conversation
        $conversation = $conversationRepo->find($id);
        
        // Vérifier que la conversation existe et appartient au locataire
        if (!$conversation || $conversation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }
        
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('send_message_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('locataire_messages_show', ['id' => $id]);
        }
        
        // Récupérer le contenu du message
        $contenu = trim($request->request->get('contenu', ''));
        
        // Valider que le message n'est pas vide
        if (empty($contenu)) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('locataire_messages_show', ['id' => $id]);
        }
        
        // Créer le nouveau message
        $message = new Message();
        $message->setContenu($contenu);
        $message->setDateEnvoi(new \DateTime());
        $message->setEstLu(false);
        $message->setTypeExpediteur('locataire');
        $message->setConversation($conversation);
        
        // Mettre à jour la date du dernier message de la conversation
        $conversation->setDernierMessageDate(new \DateTime());
        
        // Sauvegarder
        $entityManager->persist($message);
        $entityManager->flush();
        
        $this->addFlash('success', 'Message envoyé avec succès !');
        
        return $this->redirectToRoute('locataire_messages_show', ['id' => $id]);
    }
    
    /**
     * Archiver une conversation
     */
    #[Route('/{id}/archiver', name: 'archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archive(
        int $id,
        Request $request,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer la conversation
        $conversation = $conversationRepo->find($id);
        
        // Vérifier que la conversation existe et appartient au locataire
        if (!$conversation || $conversation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }
        
        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('archive_conversation_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('locataire_messages_index');
        }
        
        // Archiver la conversation
        $conversation->setEstArchivee(true);
        $entityManager->flush();
        
        $this->addFlash('success', 'Conversation archivée avec succès !');
        
        return $this->redirectToRoute('locataire_messages_index');
    }
    
    /**
     * Compter le nombre de messages non lus dans une conversation
     * (uniquement les messages envoyés par le centre commercial)
     */
    private function countUnreadMessages(Conversation $conversation, Locataire $locataire): int
    {
        $count = 0;
        
        foreach ($conversation->getMessages() as $message) {
            // Un message est non lu pour le locataire si :
            // 1. Il n'est pas marqué comme lu (!estLu)
            // 2. Il a été envoyé par le centre commercial (typeExpediteur = 'centre')
            if (!$message->isEstLu() && $message->getTypeExpediteur() === 'centre') {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Marquer tous les messages non lus d'une conversation comme lus
     * (uniquement les messages envoyés par le centre commercial)
     */
    private function markMessagesAsRead(
        Conversation $conversation,
        Locataire $locataire,
        EntityManagerInterface $entityManager
    ): void {
        $hasChanges = false;
        
        foreach ($conversation->getMessages() as $message) {
            // Marquer comme lu uniquement les messages du centre commercial
            if (!$message->isEstLu() && $message->getTypeExpediteur() === 'centre') {
                $message->setEstLu(true);
                $message->setDateLecture(new \DateTime());
                $hasChanges = true;
            }
        }
        
        // Sauvegarder si des changements ont été effectués
        if ($hasChanges) {
            $entityManager->flush();
        }
    }
}