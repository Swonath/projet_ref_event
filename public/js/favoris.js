/**
 * GESTION DES FAVORIS - Version 2 (Plus robuste)
 */

let offsetFavoris = 12;
let totalFavoris = 0;

function initialiserFavoris(total) {
    totalFavoris = total;
}

function chargerPlusFavoris() {
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
    
    fetch(`${baseUrl}?offset=${offsetFavoris}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            
            if (!data.success) {
                throw new Error(data.message || 'Erreur inconnue');
            }
            
            if (!data.html) {
                throw new Error('Aucun HTML dans la réponse');
            }
            
            // MÉTHODE 1 : Trouver le conteneur parent
            const loadMoreContainer = document.getElementById('load-more-container');
            if (!loadMoreContainer) {
                console.error('Conteneur load-more-container introuvable !');
                throw new Error('Structure HTML invalide');
            }
            
            // Créer un conteneur temporaire
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html.trim();
            
            // Insérer tous les éléments avant le bouton
            while (tempDiv.firstChild) {
                loadMoreContainer.parentNode.insertBefore(tempDiv.firstChild, loadMoreContainer);
            }
            
            // Mettre à jour l'offset
            offsetFavoris += 12;
            
            // Mettre à jour le compteur
            const affichesSpan = document.getElementById('favoris-affiches');
            if (affichesSpan) {
                const nouveauNombre = Math.min(offsetFavoris, totalFavoris);
                affichesSpan.textContent = nouveauNombre;
            }
            
            // Vérifier s'il reste des favoris
            if (!data.hasMore || offsetFavoris >= totalFavoris) {
                loadMoreContainer.style.display = 'none';
            } else {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('=== ERREUR ===', error);
            alert('Erreur: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}

function retirerDesFavoris(emplacementId, button) {
    
    if (!confirm('Voulez-vous vraiment retirer cet emplacement de vos favoris ?')) {
        return;
    }
    
    const card = button.closest('.reservation-card');
    if (!card) {
        console.error('Card introuvable !');
        return;
    }
    
    card.style.opacity = '0.5';
    button.disabled = true;
    button.textContent = 'Suppression...';
    
    const deleteUrl = button.getAttribute('data-delete-url');
    
    if (!deleteUrl) {
        console.error('URL de suppression non définie !');
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
        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {        
        if (data.success) {
            card.style.transition = 'all 0.3s ease';
            card.style.transform = 'translateX(-100%)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                totalFavoris--;
                
                if (document.querySelectorAll('.reservation-card').length === 0) {
                    location.reload();
                } else {
                    const affichesSpan = document.getElementById('favoris-affiches');
                    if (affichesSpan) {
                        const nouveauNombre = Math.min(offsetFavoris - 1, totalFavoris);
                        affichesSpan.textContent = nouveauNombre;
                    }
                }
            }, 300);
        } else {
            throw new Error(data.message || 'Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur suppression:', error);
        alert('Erreur: ' + error.message);
        restaurerBouton(card, button);
    });
}

function restaurerBouton(card, button) {
    card.style.opacity = '1';
    button.disabled = false;
    button.textContent = 'Retirer ❤️';
}

function afficherMessageErreur(message) {
    alert(message);
}