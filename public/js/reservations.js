/**
 * GESTION DES RÉSERVATIONS - Version corrigée
 */

let offsetReservations = 12;
let totalReservations = 0;
let filtreActif = 'toutes';  // AJOUTÉ : Variable pour le filtre

function initialiserReservations(total, filtre) {
    totalReservations = total;
    filtreActif = filtre || 'toutes';  // CORRIGÉ : Stocke le filtre
    console.log('✅ Réservations initialisées. Total:', totalReservations, 'Filtre:', filtreActif);
}

function chargerPlusReservations() {  // CORRIGÉ : Sans accent, avec "s"
    console.log('=== CHARGEMENT RÉSERVATIONS ===');
    console.log('Offset:', offsetReservations, 'Filtre:', filtreActif);
    
    const btn = document.getElementById('btn-load-more');
    if (!btn) {
        console.error('Bouton btn-load-more introuvable !');
        return;
    }
    
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Chargement...';
    
    const baseUrl = btn.getAttribute('data-load-url');
    
    if (!baseUrl) {
        console.error('URL de chargement non définie !');
        alert('Erreur de configuration');
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }
    
    // CORRIGÉ : Ajoute le paramètre filtre
    fetch(`${baseUrl}?offset=${offsetReservations}&filtre=${filtreActif}`)
        .then(response => {
            console.log('📥 Réponse reçue. Status:', response.status);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📦 Données:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
            if (!data.html) {
                throw new Error('Aucun HTML dans la réponse');
            }
            
            // CORRIGÉ : Utilise le bon ID
            const loadMoreContainer = document.getElementById('load-more-container-reservations');
            if (!loadMoreContainer) {
                console.error('Conteneur load-more-container-reservations introuvable !');
                throw new Error('Structure HTML invalide');
            }
            
            console.log('✅ Insertion du HTML...');

            // Créer un conteneur temporaire
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html.trim();
            
            console.log('📊', tempDiv.children.length, 'nouvelle(s) réservation(s)');
            
            // Insérer tous les éléments avant le bouton
            while (tempDiv.firstChild) {
                loadMoreContainer.parentNode.insertBefore(tempDiv.firstChild, loadMoreContainer);
            }
            
            // Mettre à jour l'offset
            offsetReservations += 12;
            console.log('📈 Nouvel offset:', offsetReservations);
            
            // CORRIGÉ : Utilise le bon ID
            const affichesSpan = document.getElementById('reservations-affiches');
            if (affichesSpan) {
                const nouveauNombre = Math.min(offsetReservations, totalReservations);
                affichesSpan.textContent = nouveauNombre;
                console.log('✅ Compteur mis à jour:', nouveauNombre);
            }
            
            // Vérifier s'il reste des réservations
            if (!data.hasMore || offsetReservations >= totalReservations) {
                console.log('✅ Toutes les réservations sont affichées');
                loadMoreContainer.style.display = 'none';
            } else {
                console.log('⏳ Il reste des réservations à charger');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
            
            console.log('=== FIN CHARGEMENT (SUCCÈS) ===');
        })
        .catch(error => {
            console.error('❌ ERREUR:', error);
            alert('Erreur: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function retirerDesReservations(emplacementId, button) {
    
    if (!confirm('Voulez-vous vraiment annuler cette réservation ?')) {
        return;
    }
    
    const card = button.closest('.reservation-card');
    if (!card) {
        console.error('Card introuvable !');
        return;
    }
    
    card.style.opacity = '0.5';
    button.disabled = true;
    button.textContent = 'Annulation...';
    
    const deleteUrl = button.getAttribute('data-delete-url');
    
    if (!deleteUrl) {
        console.error('URL d\'annulation non définie !');
        alert('Erreur de configuration');
        restaurerBouton(card, button);
        return;
    }
    
    fetch(deleteUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        console.log('📥 Réponse annulation. Status:', response.status);
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('📦 Données:', data);
        
        if (data.success) {
            card.style.transition = 'all 0.3s ease';
            card.style.transform = 'translateX(-100%)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                totalReservations--;  // CORRIGÉ : Bonne variable
                
                if (document.querySelectorAll('.reservation-card').length === 0) {
                    console.log('Plus de réservations, rechargement...');
                    location.reload();
                } else {
                    const affichesSpan = document.getElementById('reservations-affiches');
                    if (affichesSpan) {
                        const nouveauNombre = Math.min(offsetReservations - 1, totalReservations);
                        affichesSpan.textContent = nouveauNombre;
                    }
                }
            }, 300);
        } else {
            throw new Error(data.message || 'Erreur lors de l\'annulation');
        }
    })
    .catch(error => {
        console.error('❌ Erreur annulation:', error);
        alert('Erreur: ' + error.message);
        restaurerBouton(card, button);
    });
}

function restaurerBouton(card, button) {
    card.style.opacity = '1';
    button.disabled = false;
    button.textContent = 'Annuler';  // CORRIGÉ : Texte adapté
}

function afficherMessageErreur(message) {
    alert(message);
}

console.log('✅ reservations.js chargé avec succès');