<?php

namespace App\DataFixtures;

use App\Entity\Administrateur;
use App\Entity\CentreCommercial;
use App\Entity\Locataire;
use App\Entity\Emplacement;
use App\Entity\Reservation;
use App\Entity\Paiement;
use App\Entity\Document;
use App\Entity\Avis;
use App\Entity\Photo;
use App\Entity\PeriodeIndisponibilite;
use App\Entity\Conversation;
use App\Entity\Favori;
use App\Entity\Message;
use App\Entity\Parametre;
use App\Enum\StatutReservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // ========================================
        // 1. CRÃ‰ER L'ADMINISTRATEUR
        // ========================================
        $admin = new Administrateur();
        $admin->setNom('Audet');
        $admin->setPrenom('Nathan');
        $admin->setEmail('naudet2003@gmail.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setTelephone('0781794524');
        $manager->persist($admin);

        // ========================================
        // 2. CRÃ‰ER LES CENTRES COMMERCIAUX
        // ========================================
        $centres = [];

        // Centre 1 - ValidÃ©
        $centre1 = new CentreCommercial();
        $centre1->setNomCentre('Carrefour Belle Ã‰pine');
        $centre1->setEmail('contact@belleepine.fr');
        $centre1->setPassword($this->passwordHasher->hashPassword($centre1, 'centre123'));
        $centre1->setSiret('12345678901234');
        $centre1->setNumeroTva('FR12345678901');
        $centre1->setAdresse('2 Avenue du Luxembourg');
        $centre1->setCodePostal('94320');
        $centre1->setVille('Thiais');
        $centre1->setTelephone('0149806060');
        $centre1->setDescription('Grand centre commercial au sud de Paris avec plus de 200 boutiques');
        $centre1->setIban('FR7612345678901234567890123');
        $centre1->setStatutCompte('actif');
        $centre1->setAdminValidateur($admin);
        $manager->persist($centre1);
        $centres[] = $centre1;

        // Centre 2 - ValidÃ©
        $centre2 = new CentreCommercial();
        $centre2->setNomCentre('Les Quatre Temps');
        $centre2->setEmail('contact@4temps.fr');
        $centre2->setPassword($this->passwordHasher->hashPassword($centre2, 'centre123'));
        $centre2->setSiret('98765432109876');
        $centre2->setNumeroTva('FR98765432109');
        $centre2->setAdresse('15 Parvis de la DÃ©fense');
        $centre2->setCodePostal('92092');
        $centre2->setVille('Paris La DÃ©fense');
        $centre2->setTelephone('0141020000');
        $centre2->setDescription('Centre commercial emblÃ©matique de la DÃ©fense, cÅ“ur du quartier d\'affaires');
        $centre2->setIban('FR7698765432109876543210987');
        $centre2->setStatutCompte('actif');
        $centre2->setAdminValidateur($admin);
        $manager->persist($centre2);
        $centres[] = $centre2;

        // Centre 3 - En attente de validation
        $centre3 = new CentreCommercial();
        $centre3->setNomCentre('So Ouest');
        $centre3->setEmail('contact@so-ouest.com');
        $centre3->setPassword($this->passwordHasher->hashPassword($centre3, 'centre123'));
        $centre3->setSiret('11223344556677');
        $centre3->setAdresse('15 Rue du DÃ´me');
        $centre3->setCodePostal('92100');
        $centre3->setVille('Boulogne-Billancourt');
        $centre3->setTelephone('0155200000');
        $centre3->setDescription('Centre commercial moderne dans l\'ouest parisien');
        $centre3->setStatutCompte('en_attente');
        $manager->persist($centre3);
        $centres[] = $centre3;

        // ========================================
        // 3. CRÃ‰ER LES LOCATAIRES
        // ========================================
        $locataires = [];

        // Locataire 1 - Particulier
        $locataire1 = new Locataire();
        $locataire1->setNom('Martin Sophie');
        $locataire1->setEmail('sophie.martin@email.fr');
        $locataire1->setPassword($this->passwordHasher->hashPassword($locataire1, 'locataire123'));
        $locataire1->setAdresseFacturation('12 Rue de la Paix');
        $locataire1->setCodePostal('75002');
        $locataire1->setVille('Paris');
        $locataire1->setTelephone('0612345678');
        $locataire1->setStatutCompte('actif');
        $locataire1->setTypeActivite('Artisanat - Bijoux faits main');
        $manager->persist($locataire1);
        $locataires[] = $locataire1;

        // Locataire 2 - Entreprise
        $locataire2 = new Locataire();
        $locataire2->setNom('TechStore SARL');
        $locataire2->setEmail('contact@techstore.fr');
        $locataire2->setPassword($this->passwordHasher->hashPassword($locataire2, 'locataire123'));
        $locataire2->setSiret('55566677788899');
        $locataire2->setTypeActivite('Ã‰lectronique et informatique');
        $locataire2->setAdresseFacturation('45 Avenue des Champs-Ã‰lysÃ©es');
        $locataire2->setCodePostal('75008');
        $locataire2->setVille('Paris');
        $locataire2->setTelephone('0145678901');
        $locataire2->setStatutCompte('actif');
        $manager->persist($locataire2);
        $locataires[] = $locataire2;

        // Locataire 3 - Entreprise
        $locataire3 = new Locataire();
        $locataire3->setNom('Bio & Vous');
        $locataire3->setEmail('contact@bioevous.fr');
        $locataire3->setPassword($this->passwordHasher->hashPassword($locataire3, 'locataire123'));
        $locataire3->setSiret('99988877766655');
        $locataire3->setTypeActivite('Alimentation bio et produits naturels');
        $locataire3->setAdresseFacturation('78 Boulevard Voltaire');
        $locataire3->setCodePostal('75011');
        $locataire3->setVille('Paris');
        $locataire3->setTelephone('0156789012');
        $locataire3->setStatutCompte('actif');
        $manager->persist($locataire3);
        $locataires[] = $locataire3;

        // Locataire 4 - Particulier
        $locataire4 = new Locataire();
        $locataire4->setNom('Dubois Pierre');
        $locataire4->setEmail('pierre.dubois@email.fr');
        $locataire4->setPassword($this->passwordHasher->hashPassword($locataire4, 'locataire123'));
        $locataire4->setAdresseFacturation('23 Rue du Commerce');
        $locataire4->setCodePostal('92100');
        $locataire4->setVille('Boulogne-Billancourt');
        $locataire4->setTelephone('0623456789');
        $locataire4->setStatutCompte('actif');
        $locataire4->setTypeActivite('Mode - VÃªtements vintage');
        $manager->persist($locataire4);
        $locataires[] = $locataire4;

        // Locataire 5 - Suspendu
        $locataire5 = new Locataire();
        $locataire5->setNom('Express Food');
        $locataire5->setEmail('contact@expressfood.fr');
        $locataire5->setPassword($this->passwordHasher->hashPassword($locataire5, 'locataire123'));
        $locataire5->setSiret('44433322211100');
        $locataire5->setTypeActivite('Restauration rapide');
        $locataire5->setAdresseFacturation('90 Rue de Rivoli');
        $locataire5->setCodePostal('75004');
        $locataire5->setVille('Paris');
        $locataire5->setTelephone('0167890123');
        $locataire5->setStatutCompte('suspendu');
        $manager->persist($locataire5);
        $locataires[] = $locataire5;

        // ========================================
        // 4. CRÃ‰ER LES EMPLACEMENTS
        // ========================================
        $emplacements = [];

        // Emplacement 1 - Centre 1
        $emplacement1 = new Emplacement();
        $emplacement1->setTitreAnnonce('Stand central idÃ©al pour pop-up store');
        $emplacement1->setDescription('Emplacement stratÃ©gique en plein cÅ“ur du centre, forte affluence toute la journÃ©e. Parfait pour lancement de produit ou vente Ã©vÃ©nementielle.');
        $emplacement1->setSurface('15.50');
        $emplacement1->setLocalisationPrecise('Niveau 0 - Galerie principale, face aux escalators');
        $emplacement1->setTypeEmplacement('stand');
        $emplacement1->setEquipements('Ã‰lectricitÃ©, Ã©clairage LED, comptoir modulable');
        $emplacement1->setTarifJour('180.00');
        $emplacement1->setTarifSemaine('950.00');
        $emplacement1->setTarifMois('3200.00');
        $emplacement1->setCaution('500.00');
        $emplacement1->setDureeMinLocation(1);
        $emplacement1->setDureeMaxLocation(90);
        $emplacement1->setStatutAnnonce('publiee');
        $emplacement1->setDateCreation(new \DateTime('-60 days'));
        $emplacement1->setDateModification(new \DateTime('-10 days'));
        $emplacement1->setNombreVues(234);
        $emplacement1->setCentreCommercial($centre1);
        $manager->persist($emplacement1);
        $emplacements[] = $emplacement1;

        // Emplacement 2 - Centre 1
        $emplacement2 = new Emplacement();
        $emplacement2->setTitreAnnonce('Kiosque lumineux - Zone restauration');
        $emplacement2->setDescription('Petit kiosque idÃ©al pour vente de boissons, snacks ou accessoires. SituÃ© dans la zone de restauration avec fort passage.');
        $emplacement2->setSurface('8.00');
        $emplacement2->setLocalisationPrecise('Niveau 1 - Food court, entrÃ©e principale');
        $emplacement2->setTypeEmplacement('kiosque');
        $emplacement2->setEquipements('Point d\'eau, Ã©lectricitÃ©, vitrine rÃ©frigÃ©rÃ©e possible');
        $emplacement2->setTarifJour('120.00');
        $emplacement2->setTarifSemaine('650.00');
        $emplacement2->setTarifMois('2200.00');
        $emplacement2->setCaution('400.00');
        $emplacement2->setDureeMinLocation(7);
        $emplacement2->setDureeMaxLocation(180);
        $emplacement2->setStatutAnnonce('publiee');
        $emplacement2->setDateCreation(new \DateTime('-45 days'));
        $emplacement2->setNombreVues(156);
        $emplacement2->setCentreCommercial($centre1);
        $manager->persist($emplacement2);
        $emplacements[] = $emplacement2;

        // Emplacement 3 - Centre 1
        $emplacement3 = new Emplacement();
        $emplacement3->setTitreAnnonce('Boutique Ã©phÃ©mÃ¨re 30mÂ² - Galerie mode');
        $emplacement3->setDescription('VÃ©ritable boutique avec vitrine sur rue, systÃ¨me de fermeture sÃ©curisÃ©. Proche des enseignes de mode reconnues.');
        $emplacement3->setSurface('30.00');
        $emplacement3->setLocalisationPrecise('Niveau 2 - Galerie mode, entre Zara et H&M');
        $emplacement3->setTypeEmplacement('boutique');
        $emplacement3->setEquipements('Vitrine, rideau mÃ©tallique, Ã©clairage, cabine d\'essayage, stockage arriÃ¨re');
        $emplacement3->setTarifJour('250.00');
        $emplacement3->setTarifSemaine('1400.00');
        $emplacement3->setTarifMois('4800.00');
        $emplacement3->setCaution('1000.00');
        $emplacement3->setDureeMinLocation(30);
        $emplacement3->setDureeMaxLocation(365);
        $emplacement3->setStatutAnnonce('publiee');
        $emplacement3->setDateCreation(new \DateTime('-30 days'));
        $emplacement3->setNombreVues(89);
        $emplacement3->setCentreCommercial($centre1);
        $manager->persist($emplacement3);
        $emplacements[] = $emplacement3;

        // Emplacement 4 - Centre 2
        $emplacement4 = new Emplacement();
        $emplacement4->setTitreAnnonce('Corner premium - Hall d\'entrÃ©e');
        $emplacement4->setDescription('Emplacement d\'exception dans le hall principal. VisibilitÃ© maximale, idÃ©al pour marques premium ou lancement produit.');
        $emplacement4->setSurface('20.00');
        $emplacement4->setLocalisationPrecise('Niveau 0 - Hall principal, entrÃ©e mÃ©tro');
        $emplacement4->setTypeEmplacement('corner');
        $emplacement4->setEquipements('Ã‰lectricitÃ© triple phase, Ã©clairage design, mobilier haut de gamme fourni');
        $emplacement4->setTarifJour('350.00');
        $emplacement4->setTarifSemaine('2000.00');
        $emplacement4->setTarifMois('7000.00');
        $emplacement4->setCaution('1500.00');
        $emplacement4->setDureeMinLocation(3);
        $emplacement4->setDureeMaxLocation(90);
        $emplacement4->setStatutAnnonce('publiee');
        $emplacement4->setDateCreation(new \DateTime('-20 days'));
        $emplacement4->setNombreVues(312);
        $emplacement4->setCentreCommercial($centre2);
        $manager->persist($emplacement4);
        $emplacements[] = $emplacement4;

        // Emplacement 5 - Centre 2
        $emplacement5 = new Emplacement();
        $emplacement5->setTitreAnnonce('Stand modulable - Zone loisirs');
        $emplacement5->setDescription('Stand adaptable selon vos besoins, proche du cinÃ©ma et de l\'espace enfants. ClientÃ¨le familiale.');
        $emplacement5->setSurface('12.00');
        $emplacement5->setLocalisationPrecise('Niveau 3 - Galerie loisirs, face au cinÃ©ma');
        $emplacement5->setTypeEmplacement('stand');
        $emplacement5->setEquipements('Ã‰lectricitÃ©, tables pliantes, chaises disponibles');
        $emplacement5->setTarifJour('150.00');
        $emplacement5->setTarifSemaine('800.00');
        $emplacement5->setTarifMois('2800.00');
        $emplacement5->setCaution('500.00');
        $emplacement5->setDureeMinLocation(1);
        $emplacement5->setDureeMaxLocation(60);
        $emplacement5->setStatutAnnonce('publiee');
        $emplacement5->setDateCreation(new \DateTime('-15 days'));
        $emplacement5->setNombreVues(178);
        $emplacement5->setCentreCommercial($centre2);
        $manager->persist($emplacement5);
        $emplacements[] = $emplacement5;

        // Emplacement 6 - Centre 2 (temporairement indisponible)
        $emplacement6 = new Emplacement();
        $emplacement6->setTitreAnnonce('Boutique 25mÂ² - Secteur beautÃ©');
        $emplacement6->setDescription('Boutique avec vitrine, idÃ©ale pour cosmÃ©tiques, parfumerie ou accessoires beautÃ©. ClientÃ¨le fÃ©minine ciblÃ©e.');
        $emplacement6->setSurface('25.00');
        $emplacement6->setLocalisationPrecise('Niveau 1 - Galerie beautÃ©, prÃ¨s de Sephora');
        $emplacement6->setTypeEmplacement('boutique');
        $emplacement6->setEquipements('Vitrine, miroirs, Ã©clairage adaptÃ© beautÃ©, point d\'eau');
        $emplacement6->setTarifJour('220.00');
        $emplacement6->setTarifSemaine('1200.00');
        $emplacement6->setTarifMois('4200.00');
        $emplacement6->setCaution('800.00');
        $emplacement6->setDureeMinLocation(14);
        $emplacement6->setDureeMaxLocation(180);
        $emplacement6->setStatutAnnonce('publiee');
        $emplacement6->setDateCreation(new \DateTime('-50 days'));
        $emplacement6->setNombreVues(267);
        $emplacement6->setCentreCommercial($centre2);
        $manager->persist($emplacement6);
        $emplacements[] = $emplacement6;

        // Emplacement 7 - Centre 3
        $emplacement7 = new Emplacement();
        $emplacement7->setTitreAnnonce('Kiosque d\'angle - Passage central');
        $emplacement7->setDescription('Petit emplacement stratÃ©gique Ã  l\'angle de deux galeries. Parfait pour vente de petits articles.');
        $emplacement7->setSurface('6.00');
        $emplacement7->setLocalisationPrecise('Niveau 0 - Intersection galerie A et B');
        $emplacement7->setTypeEmplacement('kiosque');
        $emplacement7->setEquipements('Ã‰lectricitÃ©, comptoir fixe');
        $emplacement7->setTarifJour('90.00');
        $emplacement7->setTarifSemaine('500.00');
        $emplacement7->setTarifMois('1700.00');
        $emplacement7->setCaution('300.00');
        $emplacement7->setDureeMinLocation(3);
        $emplacement7->setDureeMaxLocation(90);
        $emplacement7->setStatutAnnonce('brouillon');
        $emplacement7->setDateCreation(new \DateTime('-5 days'));
        $emplacement7->setNombreVues(12);
        $emplacement7->setCentreCommercial($centre3);
        $manager->persist($emplacement7);
        $emplacements[] = $emplacement7;

        // Emplacement 8 - Centre 1 (archivÃ©)
        $emplacement8 = new Emplacement();
        $emplacement8->setTitreAnnonce('Stand temporaire - FÃªtes de fin d\'annÃ©e');
        $emplacement8->setDescription('Stand saisonnier pour les fÃªtes. Location courte durÃ©e uniquement.');
        $emplacement8->setSurface('10.00');
        $emplacement8->setLocalisationPrecise('Niveau 0 - PrÃ¨s du sapin de NoÃ«l');
        $emplacement8->setTypeEmplacement('stand');
        $emplacement8->setEquipements('Ã‰lectricitÃ©, dÃ©coration fournie');
        $emplacement8->setTarifJour('200.00');
        $emplacement8->setTarifSemaine('1100.00');
        $emplacement8->setCaution('400.00');
        $emplacement8->setDureeMinLocation(1);
        $emplacement8->setDureeMaxLocation(30);
        $emplacement8->setStatutAnnonce('archivee');
        $emplacement8->setDateCreation(new \DateTime('-90 days'));
        $emplacement8->setNombreVues(423);
        $emplacement8->setCentreCommercial($centre1);
        $manager->persist($emplacement8);
        $emplacements[] = $emplacement8;

        // Emplacement 9 - Centre 2
        $emplacement9 = new Emplacement();
        $emplacement9->setTitreAnnonce('Espace dÃ©gustation - Zone alimentaire');
        $emplacement9->setDescription('Espace Ã©quipÃ© pour dÃ©monstrations culinaires et dÃ©gustations. Normes alimentaires respectÃ©es.');
        $emplacement9->setSurface('18.00');
        $emplacement9->setLocalisationPrecise('Niveau 1 - MarchÃ© gourmand');
        $emplacement9->setTypeEmplacement('corner');
        $emplacement9->setEquipements('Point d\'eau, Ã©lectricitÃ©, plan de travail inox, rÃ©frigÃ©ration');
        $emplacement9->setTarifJour('280.00');
        $emplacement9->setTarifSemaine('1600.00');
        $emplacement9->setTarifMois('5500.00');
        $emplacement9->setCaution('1000.00');
        $emplacement9->setDureeMinLocation(7);
        $emplacement9->setDureeMaxLocation(120);
        $emplacement9->setStatutAnnonce('publiee');
        $emplacement9->setDateCreation(new \DateTime('-12 days'));
        $emplacement9->setNombreVues(94);
        $emplacement9->setCentreCommercial($centre2);
        $manager->persist($emplacement9);
        $emplacements[] = $emplacement9;

        // Emplacement 10 - Centre 1
        $emplacement10 = new Emplacement();
        $emplacement10->setTitreAnnonce('Mini-boutique 15mÂ² - Galerie sport');
        $emplacement10->setDescription('Petit espace commercial dans la galerie dÃ©diÃ©e au sport et loisirs actifs.');
        $emplacement10->setSurface('15.00');
        $emplacement10->setLocalisationPrecise('Niveau 2 - Galerie sport, prÃ¨s de Decathlon');
        $emplacement10->setTypeEmplacement('boutique');
        $emplacement10->setEquipements('Vitrine, Ã©tagÃ¨res murales, Ã©clairage, rideau de fer');
        $emplacement10->setTarifJour('170.00');
        $emplacement10->setTarifSemaine('950.00');
        $emplacement10->setTarifMois('3300.00');
        $emplacement10->setCaution('650.00');
        $emplacement10->setDureeMinLocation(14);
        $emplacement10->setDureeMaxLocation(180);
        $emplacement10->setStatutAnnonce('publiee');
        $emplacement10->setDateCreation(new \DateTime('-25 days'));
        $emplacement10->setNombreVues(143);
        $emplacement10->setCentreCommercial($centre1);
        $manager->persist($emplacement10);
        $emplacements[] = $emplacement10;

        // ========================================
        // Ajouter 12 emplacements supplémentaires (emplacements 11 à 22)
        // ========================================
        
        $typesEmplacement = ['stand', 'kiosque', 'boutique', 'corner'];
        $centresDisponibles = [$centre1, $centre2];
        
        for ($i = 11; $i <= 22; $i++) {
            $emplacement = new Emplacement();
            $type = $typesEmplacement[array_rand($typesEmplacement)];
            
            $emplacement->setTitreAnnonce("Emplacement #{$i} - " . ucfirst($type) . " moderne");
            $emplacement->setDescription("Espace commercial idéal pour votre activité. Très bien situé avec un bon passage.");
            $emplacement->setSurface(number_format(rand(8, 40), 2, '.', ''));
            $emplacement->setLocalisationPrecise("Niveau " . rand(0, 2) . " - Galerie principale");
            $emplacement->setTypeEmplacement($type);
            $emplacement->setEquipements('Électricité, éclairage, comptoir');
            $emplacement->setTarifJour(number_format(rand(100, 300), 2, '.', ''));
            $emplacement->setTarifSemaine(number_format(rand(600, 1800), 2, '.', ''));
            $emplacement->setTarifMois(number_format(rand(2000, 6000), 2, '.', ''));
            $emplacement->setCaution(number_format(rand(300, 1000), 2, '.', ''));
            $emplacement->setDureeMinLocation(rand(1, 14));
            $emplacement->setDureeMaxLocation(rand(60, 365));
            $emplacement->setStatutAnnonce('publiee');
            $emplacement->setDateCreation(new \DateTime('-' . rand(5, 60) . ' days'));
            $emplacement->setNombreVues(rand(50, 300));
            $emplacement->setCentreCommercial($centresDisponibles[array_rand($centresDisponibles)]);
            
            $manager->persist($emplacement);
            $emplacements[] = $emplacement;
            
            // Stocker dans des variables pour les utiliser plus tard
            ${'emplacement' . $i} = $emplacement;
        }

        // ========================================
        // 5. CRÉER LES FAVORIS
        // ========================================
        
        // 20 Favoris pour Sophie Martin (locataire1)
        // Utiliser les 22 emplacements disponibles (emplacement1 à emplacement22)
        
        $emplacementsPourFavoris = [
            $emplacement1, $emplacement2, $emplacement3, $emplacement4, 
            $emplacement5, $emplacement6, $emplacement9, $emplacement10,
            $emplacement11, $emplacement12, $emplacement13, $emplacement14,
            $emplacement15, $emplacement16, $emplacement17, $emplacement18,
            $emplacement19, $emplacement20, $emplacement21, $emplacement22
        ];
        
        // Créer 20 favoris (1 par emplacement, sans doublon)
        for ($i = 0; $i < 20; $i++) {
            $favori = new Favori();
            $favori->setLocataire($locataire1); // Sophie Martin
            $favori->setEmplacement($emplacementsPourFavoris[$i]);
            
            // Date d'ajout variable (étalée sur les 80 derniers jours)
            $joursAgo = 80 - ($i * 4);
            $dateAjout = new \DateTimeImmutable("-$joursAgo days");
            $favori->setDateAjout($dateAjout);
            
            $manager->persist($favori);
        }
        
        // Ajouter quelques favoris pour d'autres locataires
        $favoriLocataire2 = new Favori();
        $favoriLocataire2->setLocataire($locataire2);
        $favoriLocataire2->setEmplacement($emplacement21);
        $manager->persist($favoriLocataire2);
        
        $favoriLocataire3 = new Favori();
        $favoriLocataire3->setLocataire($locataire3);
        $favoriLocataire3->setEmplacement($emplacement22);
        $manager->persist($favoriLocataire3);

        // ========================================
        // 6. CRÉER DES PHOTOS POUR LES EMPLACEMENTS
        // ========================================
        
        // Photos pour emplacement 1
        $photo1 = new Photo();
        $photo1->setCheminFichier('/uploads/emplacements/emplacement1_vue1.jpg');
        $photo1->setLegende('Vue gÃ©nÃ©rale du stand');
        $photo1->setOrdreAffichage(1);
        $photo1->setDateUpload(new \DateTime('-60 days'));
        $photo1->setEmplacement($emplacement1);
        $manager->persist($photo1);

        $photo2 = new Photo();
        $photo2->setCheminFichier('/uploads/emplacements/emplacement1_vue2.jpg');
        $photo2->setLegende('Vue depuis l\'entrÃ©e principale');
        $photo2->setOrdreAffichage(2);
        $photo2->setDateUpload(new \DateTime('-60 days'));
        $photo2->setEmplacement($emplacement1);
        $manager->persist($photo2);

        // Photos pour emplacement 4
        $photo3 = new Photo();
        $photo3->setCheminFichier('/uploads/emplacements/emplacement4_vue1.jpg');
        $photo3->setLegende('Corner dans le hall principal');
        $photo3->setOrdreAffichage(1);
        $photo3->setDateUpload(new \DateTime('-20 days'));
        $photo3->setEmplacement($emplacement4);
        $manager->persist($photo3);

        // ========================================
        // 7. CRÉER DES PÉRIODES D'INDISPONIBILITÃ‰
        // ========================================
        
        // IndisponibilitÃ© pour emplacement 6 (travaux)
        $periode1 = new PeriodeIndisponibilite();
        $periode1->setDateDebut(new \DateTime('+5 days'));
        $periode1->setDateFin(new \DateTime('+12 days'));
        $periode1->setMotif('Travaux de rÃ©novation de la galerie');
        $periode1->setEmplacement($emplacement6);
        $manager->persist($periode1);

        // IndisponibilitÃ© pour emplacement 1 (Ã©vÃ©nement privÃ©)
        $periode2 = new PeriodeIndisponibilite();
        $periode2->setDateDebut(new \DateTime('+30 days'));
        $periode2->setDateFin(new \DateTime('+33 days'));
        $periode2->setMotif('Ã‰vÃ©nement privÃ© du centre commercial');
        $periode2->setEmplacement($emplacement1);
        $manager->persist($periode2);

        // ========================================
        // 8. CRÉER LES RÉSERVATIONS - CORRECTION ICI âœ…
        // ========================================
        $reservations = [];

        // RÃ©servation 1 - TerminÃ©e avec avis
        $reservation1 = new Reservation();
        $reservation1->setDateDebut(new \DateTime('-45 days'));
        $reservation1->setDateFin(new \DateTime('-38 days'));
        $reservation1->setMontantLocation('950.00');
        $reservation1->setMontantCommission('95.00');
        $reservation1->setMontantTotal('1045.00');
        $reservation1->setCautionVersee('500.00');
        $reservation1->setStatut(StatutReservation::TERMINEE);  // âœ… Utiliser l'enum
        $reservation1->setDateDemande(new \DateTime('-50 days'));
        $reservation1->setDateValidation(new \DateTime('-48 days'));
        $reservation1->setDatePaiement(new \DateTime('-47 days'));
        $reservation1->setLocataire($locataire1);
        $reservation1->setEmplacement($emplacement1);
        $manager->persist($reservation1);
        $reservations[] = $reservation1;

        // RÃ©servation 2 - En cours
        $reservation2 = new Reservation();
        $reservation2->setDateDebut(new \DateTime('-3 days'));
        $reservation2->setDateFin(new \DateTime('+4 days'));
        $reservation2->setMontantLocation('180.00');
        $reservation2->setMontantCommission('18.00');
        $reservation2->setMontantTotal('198.00');
        $reservation2->setCautionVersee('500.00');
        $reservation2->setStatut(StatutReservation::EN_COURS);  // âœ… Utiliser l'enum
        $reservation2->setDateDemande(new \DateTime('-10 days'));
        $reservation2->setDateValidation(new \DateTime('-8 days'));
        $reservation2->setDatePaiement(new \DateTime('-7 days'));
        $reservation2->setLocataire($locataire2);
        $reservation2->setEmplacement($emplacement1);
        $manager->persist($reservation2);
        $reservations[] = $reservation2;

        // RÃ©servation 3 - En attente de validation
        $reservation3 = new Reservation();
        $reservation3->setDateDebut(new \DateTime('+15 days'));
        $reservation3->setDateFin(new \DateTime('+44 days'));
        $reservation3->setMontantLocation('4800.00');
        $reservation3->setMontantCommission('480.00');
        $reservation3->setMontantTotal('5280.00');
        $reservation3->setCautionVersee('1000.00');
        $reservation3->setStatut(StatutReservation::EN_ATTENTE);  // âœ… Utiliser l'enum
        $reservation3->setDateDemande(new \DateTime('-2 days'));
        $reservation3->setLocataire($locataire3);
        $reservation3->setEmplacement($emplacement3);
        $manager->persist($reservation3);
        $reservations[] = $reservation3;

        // RÃ©servation 4 - ValidÃ©e, en attente de paiement
        $reservation4 = new Reservation();
        $reservation4->setDateDebut(new \DateTime('+20 days'));
        $reservation4->setDateFin(new \DateTime('+26 days'));
        $reservation4->setMontantLocation('2000.00');
        $reservation4->setMontantCommission('200.00');
        $reservation4->setMontantTotal('2200.00');
        $reservation4->setCautionVersee('1500.00');
        $reservation4->setStatut(StatutReservation::VALIDEE);  // âœ… Utiliser l'enum
        $reservation4->setDateDemande(new \DateTime('-5 days'));
        $reservation4->setDateValidation(new \DateTime('-3 days'));
        $reservation4->setLocataire($locataire2);
        $reservation4->setEmplacement($emplacement4);
        $manager->persist($reservation4);
        $reservations[] = $reservation4;

        // RÃ©servation 5 - RefusÃ©e
        $reservation5 = new Reservation();
        $reservation5->setDateDebut(new \DateTime('+10 days'));
        $reservation5->setDateFin(new \DateTime('+17 days'));
        $reservation5->setMontantLocation('800.00');
        $reservation5->setMontantCommission('80.00');
        $reservation5->setMontantTotal('880.00');
        $reservation5->setStatut(StatutReservation::REFUSEE);  // âœ… Utiliser l'enum
        $reservation5->setDateDemande(new \DateTime('-4 days'));
        $reservation5->setDateValidation(new \DateTime('-2 days'));
        $reservation5->setMotifRefus('Dates indisponibles en raison d\'un Ã©vÃ©nement planifiÃ©');
        $reservation5->setLocataire($locataire4);
        $reservation5->setEmplacement($emplacement5);
        $manager->persist($reservation5);
        $reservations[] = $reservation5;

        // RÃ©servation 6 - AnnulÃ©e par le locataire
        $reservation6 = new Reservation();
        $reservation6->setDateDebut(new \DateTime('+25 days'));
        $reservation6->setDateFin(new \DateTime('+32 days'));
        $reservation6->setMontantLocation('1200.00');
        $reservation6->setMontantCommission('120.00');
        $reservation6->setMontantTotal('1320.00');
        $reservation6->setStatut(StatutReservation::ANNULEE);  // âœ… Utiliser l'enum
        $reservation6->setDateDemande(new \DateTime('-15 days'));
        $reservation6->setDateValidation(new \DateTime('-13 days'));
        $reservation6->setDatePaiement(new \DateTime('-12 days'));
        $reservation6->setAnnuleePar('locataire');
        $reservation6->setDateAnnulation(new \DateTime('-5 days'));
        $reservation6->setLocataire($locataire1);
        $reservation6->setEmplacement($emplacement6);
        $manager->persist($reservation6);
        $reservations[] = $reservation6;

        // RÃ©servation 7 - ValidÃ©e (payÃ©e, Ã  venir)
        // Note: J'ai changÃ© 'confirmee' en 'validee' car c'est le statut qui existe dans l'enum
        $reservation7 = new Reservation();
        $reservation7->setDateDebut(new \DateTime('+40 days'));
        $reservation7->setDateFin(new \DateTime('+46 days'));
        $reservation7->setMontantLocation('1600.00');
        $reservation7->setMontantCommission('160.00');
        $reservation7->setMontantTotal('1760.00');
        $reservation7->setCautionVersee('1000.00');
        $reservation7->setStatut(StatutReservation::VALIDEE);  // âœ… Utiliser l'enum
        $reservation7->setDateDemande(new \DateTime('-8 days'));
        $reservation7->setDateValidation(new \DateTime('-6 days'));
        $reservation7->setDatePaiement(new \DateTime('-5 days'));
        $reservation7->setLocataire($locataire3);
        $reservation7->setEmplacement($emplacement9);
        $manager->persist($reservation7);
        $reservations[] = $reservation7;

        // ========================================
        // CRÉER 30 RÉSERVATIONS POUR SOPHIE MARTIN (locataire1)
        // ========================================
        
        $statuts = [
            StatutReservation::EN_ATTENTE,
            StatutReservation::VALIDEE,
            StatutReservation::EN_COURS,
            StatutReservation::TERMINEE,
            StatutReservation::REFUSEE,
            StatutReservation::ANNULEE,
        ];
        
        $emplacementsDisponibles = [$emplacement1, $emplacement2, $emplacement3, $emplacement4, $emplacement5, $emplacement6, $emplacement9, $emplacement10];
        
        for ($i = 1; $i <= 30; $i++) {
            $reservation = new Reservation();
            
            // Dates variables
            $joursDebut = -365 + ($i * 10); // Étalé sur l'année passée et future
            $duree = rand(3, 30); // Entre 3 et 30 jours
            
            $reservation->setDateDebut(new \DateTime("$joursDebut days"));
            $reservation->setDateFin(new \DateTime(($joursDebut + $duree) . " days"));
            
            // Montants variables
            $montantLocation = rand(500, 5000);
            $commission = $montantLocation * 0.10;
            $total = $montantLocation + $commission;
            
            $reservation->setMontantLocation(number_format($montantLocation, 2, '.', ''));
            $reservation->setMontantCommission(number_format($commission, 2, '.', ''));
            $reservation->setMontantTotal(number_format($total, 2, '.', ''));
            $reservation->setCautionVersee(number_format(rand(300, 1500), 2, '.', ''));
            
            // Statut aléatoire
            $statut = $statuts[array_rand($statuts)];
            $reservation->setStatut($statut);
            
            // Dates de processus
            $reservation->setDateDemande(new \DateTime(($joursDebut - 5) . " days"));
            
            if ($statut !== StatutReservation::EN_ATTENTE && $statut !== StatutReservation::REFUSEE) {
                $reservation->setDateValidation(new \DateTime(($joursDebut - 3) . " days"));
            }
            
            if ($statut === StatutReservation::VALIDEE || $statut === StatutReservation::EN_COURS || $statut === StatutReservation::TERMINEE) {
                $reservation->setDatePaiement(new \DateTime(($joursDebut - 2) . " days"));
            }
            
            if ($statut === StatutReservation::REFUSEE) {
                $reservation->setDateValidation(new \DateTime(($joursDebut - 2) . " days"));
                $reservation->setMotifRefus('Dates non disponibles');
            }
            
            if ($statut === StatutReservation::ANNULEE) {
                $reservation->setDateValidation(new \DateTime(($joursDebut - 3) . " days"));
                $reservation->setDatePaiement(new \DateTime(($joursDebut - 2) . " days"));
                $reservation->setAnnuleePar('locataire');
                $reservation->setDateAnnulation(new \DateTime(($joursDebut - 1) . " days"));
            }
            
            $reservation->setLocataire($locataire1); // Sophie Martin
            $reservation->setEmplacement($emplacementsDisponibles[array_rand($emplacementsDisponibles)]);
            
            $manager->persist($reservation);
            $reservations[] = $reservation;
        }

        // ========================================
        // 9. CRÉER LES PAIEMENTS
        // ========================================
        
        // Paiement pour rÃ©servation 1
        $paiement1 = new Paiement();
        $paiement1->setMontant('1045.00');
        $paiement1->setDatePaiement(new \DateTime('-47 days'));
        $paiement1->setMethodePaiement('carte_bancaire');
        $paiement1->setStatut('accepte');
        $paiement1->setTransactionId('TRX_' . uniqid());
        $paiement1->setReservation($reservation1);
        $manager->persist($paiement1);

        // Paiement pour rÃ©servation 2
        $paiement2 = new Paiement();
        $paiement2->setMontant('198.00');
        $paiement2->setDatePaiement(new \DateTime('-7 days'));
        $paiement2->setMethodePaiement('carte_bancaire');
        $paiement2->setStatut('accepte');
        $paiement2->setTransactionId('TRX_' . uniqid());
        $paiement2->setReservation($reservation2);
        $manager->persist($paiement2);

        // Paiement pour rÃ©servation 6 (remboursÃ© suite annulation)
        $paiement3 = new Paiement();
        $paiement3->setMontant('1320.00');
        $paiement3->setDatePaiement(new \DateTime('-12 days'));
        $paiement3->setMethodePaiement('virement');
        $paiement3->setStatut('rembourse');
        $paiement3->setTransactionId('TRX_' . uniqid());
        $paiement3->setDateRemboursement(new \DateTime('-4 days'));
        $paiement3->setMontantRembourse('1200.00'); // Frais de 120â‚¬ retenus
        $paiement3->setReservation($reservation6);
        $manager->persist($paiement3);

        // Paiement pour rÃ©servation 7
        $paiement4 = new Paiement();
        $paiement4->setMontant('1760.00');
        $paiement4->setDatePaiement(new \DateTime('-5 days'));
        $paiement4->setMethodePaiement('carte_bancaire');
        $paiement4->setStatut('accepte');
        $paiement4->setTransactionId('TRX_' . uniqid());
        $paiement4->setReservation($reservation7);
        $manager->persist($paiement4);

        // ========================================
        // 10. CRÉER LES DOCUMENTS
        // ========================================
        
        // Documents pour rÃ©servation 1
        $document1 = new Document();
        $document1->setTypeDocument('contrat');
        $document1->setNumeroDocument('CONT-2024-001');
        $document1->setDateGeneration(new \DateTime('-48 days'));
        $document1->setCheminFichier('/uploads/documents/contrat_001.pdf');
        $document1->setStatut('signe');
        $document1->setReservation($reservation1);
        $manager->persist($document1);

        $document2 = new Document();
        $document2->setTypeDocument('facture');
        $document2->setNumeroDocument('FACT-2024-001');
        $document2->setDateGeneration(new \DateTime('-47 days'));
        $document2->setCheminFichier('/uploads/documents/facture_001.pdf');
        $document2->setStatut('payee');
        $document2->setReservation($reservation1);
        $manager->persist($document2);

        // Documents pour rÃ©servation 2
        $document3 = new Document();
        $document3->setTypeDocument('contrat');
        $document3->setNumeroDocument('CONT-2024-002');
        $document3->setDateGeneration(new \DateTime('-8 days'));
        $document3->setCheminFichier('/uploads/documents/contrat_002.pdf');
        $document3->setStatut('signe');
        $document3->setReservation($reservation2);
        $manager->persist($document3);

        $document4 = new Document();
        $document4->setTypeDocument('facture');
        $document4->setNumeroDocument('FACT-2024-002');
        $document4->setDateGeneration(new \DateTime('-7 days'));
        $document4->setCheminFichier('/uploads/documents/facture_002.pdf');
        $document4->setStatut('payee');
        $document4->setReservation($reservation2);
        $manager->persist($document4);

        // Document pour rÃ©servation 4 (en attente de signature)
        $document5 = new Document();
        $document5->setTypeDocument('contrat');
        $document5->setNumeroDocument('CONT-2024-004');
        $document5->setDateGeneration(new \DateTime('-3 days'));
        $document5->setCheminFichier('/uploads/documents/contrat_004.pdf');
        $document5->setStatut('en_attente');
        $document5->setReservation($reservation4);
        $manager->persist($document5);

        // ========================================
        // 11. CRÉER LES AVIS
        // ========================================
        
        // Avis du locataire 1 sur rÃ©servation 1
        $avis1 = new Avis();
        $avis1->setNoteGlobale(5);
        $avis1->setNotePropreteConformite(5);
        $avis1->setNoteEmplacement(5);
        $avis1->setNoteQualitePrix(4);
        $avis1->setNoteCommunication(5);
        $avis1->setCommentaire('Emplacement parfait pour mon pop-up store de bijoux ! TrÃ¨s bon passage, personnel du centre trÃ¨s aidant. Je recommande vivement cet emplacement.');
        $avis1->setTypeAuteur('locataire');
        $avis1->setDateCreation(new \DateTime('-36 days'));
        $avis1->setDatePublication(new \DateTime('-35 days'));
        $avis1->setEstPublie(true);
        $avis1->setReservation($reservation1);
        $manager->persist($avis1);

        // RÃ©ponse du centre Ã  l'avis 1
        $avis1->setReponse('Merci beaucoup Sophie pour votre retour positif ! Nous sommes ravis que votre expÃ©rience se soit bien dÃ©roulÃ©e. Au plaisir de vous accueillir Ã  nouveau.');
        $avis1->setDateReponse(new \DateTime('-34 days'));
        
        // Avis du centre sur locataire 1
        $avis2 = new Avis();
        $avis2->setNoteGlobale(5);
        $avis2->setNoteCommunication(5);
        $avis2->setCommentaire('Locataire idÃ©ale, professionnelle et respectueuse des lieux. Stand trÃ¨s bien tenu, belle prÃ©sentation. Nous serions heureux de la revoir.');
        $avis2->setTypeAuteur('centre');
        $avis2->setDateCreation(new \DateTime('-35 days'));
        $avis2->setDatePublication(new \DateTime('-35 days'));
        $avis2->setEstPublie(true);
        $avis2->setReservation($reservation1);
        $manager->persist($avis2);

        // Avis non publiÃ© (modÃ©ration en cours)
        $avis3 = new Avis();
        $avis3->setNoteGlobale(3);
        $avis3->setNotePropreteConformite(3);
        $avis3->setNoteEmplacement(4);
        $avis3->setNoteQualitePrix(2);
        $avis3->setNoteCommunication(3);
        $avis3->setCommentaire('Emplacement correct mais prix un peu Ã©levÃ© pour la durÃ©e. Manque de prises Ã©lectriques supplÃ©mentaires.');
        $avis3->setTypeAuteur('locataire');
        $avis3->setDateCreation(new \DateTime('-2 days'));
        $avis3->setEstPublie(false);
        $avis3->setReservation($reservation2);
        $manager->persist($avis3);

        // ========================================
        // 11. CRÉER LES CONVERSATIONS ET MESSAGES
        // ========================================
        
        // Conversation 1 - Entre locataire 1 et centre 1
        $conversation1 = new Conversation();
        $conversation1->setSujet('Question sur équipements stand central');
        $conversation1->setDateCreation(new \DateTime('-52 days'));
        $conversation1->setDernierMessageDate(new \DateTime('-49 days'));
        $conversation1->setEstArchivee(false);
        $conversation1->setLocataire($locataire1);
        $conversation1->setCentreCommercial($centre1);
        $conversation1->setReservation($reservation1);
        $manager->persist($conversation1);

        // Messages de la conversation 1
        $message1 = new Message();
        $message1->setContenu('Bonjour, je souhaiterais savoir si le stand dispose d\'assez de prises électriques pour mon matériel de présentation ?');
        $message1->setDateEnvoi(new \DateTime('-52 days'));
        $message1->setEstLu(true);
        $message1->setDateLecture(new \DateTime('-52 days'));
        $message1->setTypeExpediteur('locataire');
        $message1->setConversation($conversation1);
        $manager->persist($message1);

        $message2 = new Message();
        $message2->setContenu('Bonjour Sophie, le stand dispose de 2 prises classiques et 1 prise triphasée. Si vous avez besoin de plus, nous pouvons installer une multiprise. Combien de prises vous faut-il ?');
        $message2->setDateEnvoi(new \DateTime('-51 days'));
        $message2->setEstLu(true);
        $message2->setDateLecture(new \DateTime('-51 days'));
        $message2->setTypeExpediteur('centre');
        $message2->setConversation($conversation1);
        $manager->persist($message2);

        $message3 = new Message();
        $message3->setContenu('Parfait, 3 prises suffiront largement. Merci pour votre réactivité !');
        $message3->setDateEnvoi(new \DateTime('-49 days'));
        $message3->setEstLu(true);
        $message3->setDateLecture(new \DateTime('-49 days'));
        $message3->setTypeExpediteur('locataire');
        $message3->setConversation($conversation1);
        $manager->persist($message3);

        // Conversation 2 - Entre locataire 3 et centre 1
        $conversation2 = new Conversation();
        $conversation2->setSujet('Demande d\'informations boutique 30m²');
        $conversation2->setDateCreation(new \DateTime('-3 days'));
        $conversation2->setDernierMessageDate(new \DateTime('-2 hours'));
        $conversation2->setEstArchivee(false);
        $conversation2->setLocataire($locataire3);
        $conversation2->setCentreCommercial($centre1);
        $conversation2->setReservation($reservation3);
        $manager->persist($conversation2);

        $message4 = new Message();
        $message4->setContenu('Bonjour, je suis intéressée par votre boutique de 30m². Serait-il possible de la visiter avant de confirmer ma réservation ?');
        $message4->setDateEnvoi(new \DateTime('-3 days'));
        $message4->setEstLu(true);
        $message4->setDateLecture(new \DateTime('-3 days'));
        $message4->setTypeExpediteur('locataire');
        $message4->setConversation($conversation2);
        $manager->persist($message4);

        $message5 = new Message();
        $message5->setContenu('Bonjour, bien sûr ! Nous pouvons organiser une visite. Seriez-vous disponible cette semaine ? Nous proposons des créneaux mardi et jeudi entre 10h et 16h.');
        $message5->setDateEnvoi(new \DateTime('-2 days'));
        $message5->setEstLu(true);
        $message5->setDateLecture(new \DateTime('-2 days'));
        $message5->setTypeExpediteur('centre');
        $message5->setConversation($conversation2);
        $manager->persist($message5);

        $message6 = new Message();
        $message6->setContenu('Jeudi 14h serait parfait pour moi. Merci !');
        $message6->setDateEnvoi(new \DateTime('-1 day'));
        $message6->setEstLu(true);
        $message6->setDateLecture(new \DateTime('-1 day'));
        $message6->setTypeExpediteur('locataire');
        $message6->setConversation($conversation2);
        $manager->persist($message6);

        // ⭐ MESSAGE NON LU du centre
        $message6b = new Message();
        $message6b->setContenu('Parfait ! Jeudi 14h c\'est noté. Rendez-vous à l\'accueil principal, demandez Caroline qui vous accompagnera pour la visite. N\'hésitez pas si vous avez des questions !');
        $message6b->setDateEnvoi(new \DateTime('-2 hours'));
        $message6b->setEstLu(false);
        $message6b->setTypeExpediteur('centre');
        $message6b->setConversation($conversation2);
        $manager->persist($message6b);

        // Conversation 3 - Question générale (sans réservation)
        $conversation3 = new Conversation();
        $conversation3->setSujet('Renseignement sur les modalités de location');
        $conversation3->setDateCreation(new \DateTime('-7 days'));
        $conversation3->setDernierMessageDate(new \DateTime('-6 days'));
        $conversation3->setEstArchivee(false);
        $conversation3->setLocataire($locataire4);
        $conversation3->setCentreCommercial($centre2);
        $manager->persist($conversation3);

        $message7 = new Message();
        $message7->setContenu('Bonjour, quelles sont les modalités de paiement ? Faut-il payer l\'intégralité à la réservation ?');
        $message7->setDateEnvoi(new \DateTime('-7 days'));
        $message7->setEstLu(true);
        $message7->setDateLecture(new \DateTime('-7 days'));
        $message7->setTypeExpediteur('locataire');
        $message7->setConversation($conversation3);
        $manager->persist($message7);

        $message8 = new Message();
        $message8->setContenu('Bonjour Pierre, le paiement se fait en ligne une fois votre réservation validée par nos équipes. La caution est débitée séparément et vous sera restituée dans les 7 jours suivant la fin de la location si tout est conforme.');
        $message8->setDateEnvoi(new \DateTime('-6 days'));
        $message8->setEstLu(true);
        $message8->setDateLecture(new \DateTime('-6 days'));
        $message8->setTypeExpediteur('centre');
        $message8->setConversation($conversation3);
        $manager->persist($message8);

        // ==========================================
        // CONVERSATION 4 - Conversation très active (historique complet)
        // Entre TechStore et Les Quatre Temps - Suivi événement tech
        // ==========================================
        $conversation4 = new Conversation();
        $conversation4->setSujet('Lancement produit tech - Suivi projet');
        $conversation4->setDateCreation(new \DateTime('-20 days'));
        $conversation4->setDernierMessageDate(new \DateTime('-1 hour'));
        $conversation4->setEstArchivee(false);
        $conversation4->setLocataire($locataire2);
        $conversation4->setCentreCommercial($centre2);
        $conversation4->setReservation($reservation4);
        $manager->persist($conversation4);

        // Échange 1 : Demande initiale
        $msg4_1 = new Message();
        $msg4_1->setContenu('Bonjour, nous préparons le lancement d\'un nouveau produit tech et votre corner premium nous intéresse beaucoup. Pouvez-vous nous en dire plus sur les possibilités d\'aménagement ?');
        $msg4_1->setDateEnvoi(new \DateTime('-20 days'));
        $msg4_1->setEstLu(true);
        $msg4_1->setDateLecture(new \DateTime('-20 days'));
        $msg4_1->setTypeExpediteur('locataire');
        $msg4_1->setConversation($conversation4);
        $manager->persist($msg4_1);

        $msg4_2 = new Message();
        $msg4_2->setContenu('Bonjour ! Le corner est entièrement modulable. Vous pouvez installer vos propres écrans, présentoirs et signalétique. Nous avons plusieurs prises électriques et une connexion internet haut débit. Souhaitez-vous organiser une visite ?');
        $msg4_2->setDateEnvoi(new \DateTime('-19 days'));
        $msg4_2->setEstLu(true);
        $msg4_2->setDateLecture(new \DateTime('-19 days'));
        $msg4_2->setTypeExpediteur('centre');
        $msg4_2->setConversation($conversation4);
        $manager->persist($msg4_2);

        // Échange 2 : Organisation visite
        $msg4_3 = new Message();
        $msg4_3->setContenu('Oui avec plaisir ! Seriez-vous disponible demain matin vers 10h ?');
        $msg4_3->setDateEnvoi(new \DateTime('-18 days'));
        $msg4_3->setEstLu(true);
        $msg4_3->setDateLecture(new \DateTime('-18 days'));
        $msg4_3->setTypeExpediteur('locataire');
        $msg4_3->setConversation($conversation4);
        $manager->persist($msg4_3);

        $msg4_4 = new Message();
        $msg4_4->setContenu('Parfait ! Demain 10h. Rendez-vous à l\'accueil, demandez Thomas de l\'équipe location.');
        $msg4_4->setDateEnvoi(new \DateTime('-18 days'));
        $msg4_4->setEstLu(true);
        $msg4_4->setDateLecture(new \DateTime('-18 days'));
        $msg4_4->setTypeExpediteur('centre');
        $msg4_4->setConversation($conversation4);
        $manager->persist($msg4_4);

        // Échange 3 : Après la visite
        $msg4_5 = new Message();
        $msg4_5->setContenu('Merci pour la visite ! L\'emplacement est parfait. Nous souhaitons réserver pour la période indiquée. Comment procède-t-on ?');
        $msg4_5->setDateEnvoi(new \DateTime('-17 days'));
        $msg4_5->setEstLu(true);
        $msg4_5->setDateLecture(new \DateTime('-17 days'));
        $msg4_5->setTypeExpediteur('locataire');
        $msg4_5->setConversation($conversation4);
        $manager->persist($msg4_5);

        $msg4_6 = new Message();
        $msg4_6->setContenu('Excellent ! Je vous envoie le lien de réservation par email. Une fois votre demande validée (sous 48h), vous recevrez le contrat et pourrez procéder au paiement en ligne.');
        $msg4_6->setDateEnvoi(new \DateTime('-17 days'));
        $msg4_6->setEstLu(true);
        $msg4_6->setDateLecture(new \DateTime('-17 days'));
        $msg4_6->setTypeExpediteur('centre');
        $msg4_6->setConversation($conversation4);
        $manager->persist($msg4_6);

        // Échange 4 : Installation
        $msg4_7 = new Message();
        $msg4_7->setContenu('Bonjour, notre réservation démarre bientôt. Pouvons-nous accéder au corner 2 jours avant pour l\'installation du matériel ?');
        $msg4_7->setDateEnvoi(new \DateTime('-8 days'));
        $msg4_7->setEstLu(true);
        $msg4_7->setDateLecture(new \DateTime('-8 days'));
        $msg4_7->setTypeExpediteur('locataire');
        $msg4_7->setConversation($conversation4);
        $manager->persist($msg4_7);

        $msg4_8 = new Message();
        $msg4_8->setContenu('Oui bien sûr ! Vous pouvez accéder à partir d\'après-demain. Prévenez-nous juste la veille pour que la sécurité soit informée.');
        $msg4_8->setDateEnvoi(new \DateTime('-7 days'));
        $msg4_8->setEstLu(true);
        $msg4_8->setDateLecture(new \DateTime('-7 days'));
        $msg4_8->setTypeExpediteur('centre');
        $msg4_8->setConversation($conversation4);
        $manager->persist($msg4_8);

        // Échange 5 : Pendant l'événement
        $msg4_9 = new Message();
        $msg4_9->setContenu('L\'installation s\'est très bien passée ! Par contre, nous aurions besoin d\'une prise supplémentaire pour nos écrans de démonstration. Est-ce possible ?');
        $msg4_9->setDateEnvoi(new \DateTime('-3 days'));
        $msg4_9->setEstLu(true);
        $msg4_9->setDateLecture(new \DateTime('-3 days'));
        $msg4_9->setTypeExpediteur('locataire');
        $msg4_9->setConversation($conversation4);
        $manager->persist($msg4_9);

        $msg4_10 = new Message();
        $msg4_10->setContenu('Pas de souci ! Je demande à la maintenance de passer installer une multiprise supplémentaire cet après-midi. Vous serez opérationnels demain matin.');
        $msg4_10->setDateEnvoi(new \DateTime('-3 days'));
        $msg4_10->setEstLu(true);
        $msg4_10->setDateLecture(new \DateTime('-3 days'));
        $msg4_10->setTypeExpediteur('centre');
        $msg4_10->setConversation($conversation4);
        $manager->persist($msg4_10);

        // Échange 6 : Retour positif
        $msg4_11 = new Message();
        $msg4_11->setContenu('Merci beaucoup ! L\'événement se passe très bien, nous avons beaucoup de visiteurs. Excellente collaboration !');
        $msg4_11->setDateEnvoi(new \DateTime('-2 days'));
        $msg4_11->setEstLu(true);
        $msg4_11->setDateLecture(new \DateTime('-2 days'));
        $msg4_11->setTypeExpediteur('locataire');
        $msg4_11->setConversation($conversation4);
        $manager->persist($msg4_11);

        // ⭐ MESSAGE NON LU RÉCENT du centre
        $msg4_12 = new Message();
        $msg4_12->setContenu('Ravi de l\'apprendre ! 🎉 N\'hésitez pas si vous avez besoin de quoi que ce soit. Au fait, nous organisons un marché de Noël en décembre, seriez-vous intéressés pour un autre stand ?');
        $msg4_12->setDateEnvoi(new \DateTime('-1 hour'));
        $msg4_12->setEstLu(false);
        $msg4_12->setTypeExpediteur('centre');
        $msg4_12->setConversation($conversation4);
        $manager->persist($msg4_12);

        // ==========================================
        // CONVERSATION 5 - Conversation archivée (pour tester le filtre)
        // Entre Sophie Martin et Les Quatre Temps
        // ==========================================
        $conversation5 = new Conversation();
        $conversation5->setSujet('Demande de renseignements - Espace animation');
        $conversation5->setDateCreation(new \DateTime('-30 days'));
        $conversation5->setDernierMessageDate(new \DateTime('-28 days'));
        $conversation5->setEstArchivee(true);
        $conversation5->setLocataire($locataire1);
        $conversation5->setCentreCommercial($centre2);
        $manager->persist($conversation5);

        $msg5_1 = new Message();
        $msg5_1->setContenu('Bonjour, je m\'intéresse à vos espaces animation. Quel est le tarif pour une semaine ?');
        $msg5_1->setDateEnvoi(new \DateTime('-30 days'));
        $msg5_1->setEstLu(true);
        $msg5_1->setDateLecture(new \DateTime('-30 days'));
        $msg5_1->setTypeExpediteur('locataire');
        $msg5_1->setConversation($conversation5);
        $manager->persist($msg5_1);

        $msg5_2 = new Message();
        $msg5_2->setContenu('Bonjour Sophie ! Nos espaces animation varient entre 350€ et 450€ par jour selon l\'emplacement. Pour 7 jours, nous proposons un tarif dégressif. Je peux vous proposer une remise de 15%.');
        $msg5_2->setDateEnvoi(new \DateTime('-29 days'));
        $msg5_2->setEstLu(true);
        $msg5_2->setDateLecture(new \DateTime('-29 days'));
        $msg5_2->setTypeExpediteur('centre');
        $msg5_2->setConversation($conversation5);
        $manager->persist($msg5_2);

        $msg5_3 = new Message();
        $msg5_3->setContenu('Merci pour votre réponse. Le budget est un peu élevé pour mon projet actuel. Je reviendrai vers vous plus tard !');
        $msg5_3->setDateEnvoi(new \DateTime('-28 days'));
        $msg5_3->setEstLu(true);
        $msg5_3->setDateLecture(new \DateTime('-28 days'));
        $msg5_3->setTypeExpediteur('locataire');
        $msg5_3->setConversation($conversation5);
        $manager->persist($msg5_3);

        // ==========================================
        // CONVERSATION 6 - Conversation très récente (ce matin)
        // Entre Sophie Martin et Belle Épine - Question sur nouveau projet
        // ==========================================
        $conversation6 = new Conversation();
        $conversation6->setSujet('Nouveau projet - Collection printemps');
        $conversation6->setDateCreation(new \DateTime('-3 hours'));
        $conversation6->setDernierMessageDate(new \DateTime('-30 minutes'));
        $conversation6->setEstArchivee(false);
        $conversation6->setLocataire($locataire1);
        $conversation6->setCentreCommercial($centre1);
        $manager->persist($conversation6);

        $msg6_1 = new Message();
        $msg6_1->setContenu('Bonjour ! Je prépare une nouvelle collection printemps et j\'aimerais réserver à nouveau le stand central. Est-il disponible fin mars ?');
        $msg6_1->setDateEnvoi(new \DateTime('-3 hours'));
        $msg6_1->setEstLu(true);
        $msg6_1->setDateLecture(new \DateTime('-2 hours'));
        $msg6_1->setTypeExpediteur('locataire');
        $msg6_1->setConversation($conversation6);
        $manager->persist($msg6_1);

        // ⭐ DEUX MESSAGES NON LUS du centre
        $msg6_2 = new Message();
        $msg6_2->setContenu('Bonjour Sophie ! Ravie de vous revoir 😊 Le stand central est effectivement disponible fin mars. Quelles dates exactes vous intéressent ?');
        $msg6_2->setDateEnvoi(new \DateTime('-1 hour'));
        $msg6_2->setEstLu(false);
        $msg6_2->setTypeExpediteur('centre');
        $msg6_2->setConversation($conversation6);
        $manager->persist($msg6_2);

        $msg6_3 = new Message();
        $msg6_3->setContenu('PS : Comme vous êtes une cliente fidèle, je peux vous proposer une réduction de 10% sur le tarif journalier pour cette nouvelle réservation ! 🎁');
        $msg6_3->setDateEnvoi(new \DateTime('-30 minutes'));
        $msg6_3->setEstLu(false);
        $msg6_3->setTypeExpediteur('centre');
        $msg6_3->setConversation($conversation6);
        $manager->persist($msg6_3);

        // ========================================
        // 12. CRÉER LES PARAMÈTRES SYSTÈME
        // ========================================
        
        $parametre1 = new Parametre();
        $parametre1->setNomParametre('taux_commission');
        $parametre1->setValeur('10');
        $parametre1->setDescription('Taux de commission en pourcentage appliqué sur chaque location');
        $parametre1->setDateModification(new \DateTime('-90 days'));
        $manager->persist($parametre1);

        $parametre2 = new Parametre();
        $parametre2->setNomParametre('delai_annulation_gratuite');
        $parametre2->setValeur('7');
        $parametre2->setDescription('Nombre de jours avant le début de la location pour annulation gratuite');
        $parametre2->setDateModification(new \DateTime('-90 days'));
        $manager->persist($parametre2);

        $parametre3 = new Parametre();
        $parametre3->setNomParametre('delai_validation_reservation');
        $parametre3->setValeur('48');
        $parametre3->setDescription('Délai en heures pour que le centre valide une demande de réservation');
        $parametre3->setDateModification(new \DateTime('-90 days'));
        $manager->persist($parametre3);

        $parametre4 = new Parametre();
        $parametre4->setNomParametre('email_contact');
        $parametre4->setValeur('support@mallplace.fr');
        $parametre4->setDescription('Email de contact pour le support technique');
        $parametre4->setDateModification(new \DateTime('-90 days'));
        $manager->persist($parametre4);

        $parametre5 = new Parametre();
        $parametre5->setNomParametre('duree_affichage_avis');
        $parametre5->setValeur('365');
        $parametre5->setDescription('Durée en jours pendant laquelle les avis restent visibles');
        $parametre5->setDateModification(new \DateTime('-90 days'));
        $manager->persist($parametre5);

        // ========================================
        // SAUVEGARDER TOUTES LES DONNÉES
        // ========================================
        $manager->flush();
    }
}