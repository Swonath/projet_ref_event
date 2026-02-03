<?php

namespace App\Controller\CentreCommercial;

use App\Entity\CentreCommercial;
use App\Entity\Message;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/centre/messages', name: 'centre_messages_')]
#[IsGranted('ROLE_CENTRE')]
class MessageController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        ConversationRepository $conversationRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        // Récupérer toutes les conversations non archivées
        $conversations = $conversationRepo->findBy(
            ['centreCommercial' => $centre, 'estArchivee' => false],
            ['dernierMessageDate' => 'DESC']
        );

        // Compter les messages non lus pour chaque conversation
        $conversationsAvecStats = [];
        foreach ($conversations as $conversation) {
            $nbNonLus = $this->countUnreadMessages($conversation);
            $conversationsAvecStats[] = [
                'conversation' => $conversation,
                'nbNonLus' => $nbNonLus
            ];
        }

        return $this->render('centre_commercial/messagerie.html.twig', [
            'centre' => $centre,
            'conversations' => $conversationsAvecStats,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $conversation = $conversationRepo->find($id);

        if (!$conversation || $conversation->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        // Marquer les messages du locataire comme lus
        $this->markMessagesAsRead($conversation, $entityManager);

        return $this->render('centre_commercial/messages/show.html.twig', [
            'centre' => $centre,
            'conversation' => $conversation,
        ]);
    }

    #[Route('/{id}/envoyer', name: 'send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function send(
        int $id,
        Request $request,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $conversation = $conversationRepo->find($id);

        if (!$conversation || $conversation->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('send_message_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_messages_show', ['id' => $id]);
        }

        $contenu = $request->request->get('contenu');
        if (empty(trim($contenu))) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('centre_messages_show', ['id' => $id]);
        }

        // Créer le message
        $message = new Message();
        $message->setConversation($conversation);
        $message->setContenu($contenu);
        $message->setDateEnvoi(new \DateTime());
        $message->setTypeExpediteur('centre');
        $message->setEstLu(false);

        // Mettre à jour la date du dernier message
        $conversation->setDernierMessageDate(new \DateTime());

        $entityManager->persist($message);
        $entityManager->flush();

        $this->addFlash('success', 'Message envoyé avec succès.');

        return $this->redirectToRoute('centre_messages_show', ['id' => $id]);
    }

    #[Route('/archives', name: 'archives')]
    public function archives(
        ConversationRepository $conversationRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        // Récupérer toutes les conversations archivées
        $conversations = $conversationRepo->findBy(
            ['centreCommercial' => $centre, 'estArchivee' => true],
            ['dernierMessageDate' => 'DESC']
        );

        // Compter les messages non lus pour chaque conversation
        $conversationsAvecStats = [];
        foreach ($conversations as $conversation) {
            $nbNonLus = $this->countUnreadMessages($conversation);
            $conversationsAvecStats[] = [
                'conversation' => $conversation,
                'nbNonLus' => $nbNonLus
            ];
        }

        return $this->render('centre_commercial/messagerie_archives.html.twig', [
            'centre' => $centre,
            'conversations' => $conversationsAvecStats,
        ]);
    }

    #[Route('/{id}/archiver', name: 'archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archive(
        int $id,
        Request $request,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $conversation = $conversationRepo->find($id);

        if (!$conversation || $conversation->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('archive_conversation_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_messages_index');
        }

        $conversation->setEstArchivee(true);
        $entityManager->flush();

        $this->addFlash('success', 'Conversation archivée.');

        return $this->redirectToRoute('centre_messages_index');
    }

    #[Route('/{id}/desarchiver', name: 'unarchive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unarchive(
        int $id,
        Request $request,
        ConversationRepository $conversationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $conversation = $conversationRepo->find($id);

        if (!$conversation || $conversation->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Conversation non trouvée');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('unarchive_conversation_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_messages_archives');
        }

        $conversation->setEstArchivee(false);
        $entityManager->flush();

        $this->addFlash('success', 'Conversation restaurée.');

        return $this->redirectToRoute('centre_messages_archives');
    }

    private function countUnreadMessages($conversation): int
    {
        $count = 0;
        foreach ($conversation->getMessages() as $message) {
            if ($message->getTypeExpediteur() === 'locataire' && !$message->isEstLu()) {
                $count++;
            }
        }
        return $count;
    }

    private function markMessagesAsRead($conversation, EntityManagerInterface $entityManager): void
    {
        foreach ($conversation->getMessages() as $message) {
            if ($message->getTypeExpediteur() === 'locataire' && !$message->isEstLu()) {
                $message->setEstLu(true);
                $message->setDateLecture(new \DateTime());
            }
        }
        $entityManager->flush();
    }
}
