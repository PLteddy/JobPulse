// message_script.js

document.addEventListener('DOMContentLoaded', function() {
    const conversationContainer = document.getElementById('conversation-container');
    const userSearchPanel = document.getElementById('user-search-panel');
    const searchInput = document.getElementById('user-search');
    const searchButton = document.getElementById('search-button');
    const newMessageBtn = document.getElementById('new-message-btn');
    const closeSearchPanelBtn = document.getElementById('close-search-panel');
    const searchResults = document.getElementById('search-results');
    const newMessageFormContainer = document.getElementById('new-message-form-container');
    const searchPanelTitle = document.getElementById('search-panel-title');
    
    // Délai pour la recherche instantanée
    let searchTimeout = null;
    
    // Chargement initial de la conversation si l'ID est présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const conversationId = urlParams.get('id');
    if (conversationId) {
        loadConversation(conversationId);
    }
    
    // Attacher les événements aux conversations
    attachConversationEvents();
    
    // Fonction pour attacher les événements aux conversations
    function attachConversationEvents() {
        const conversationItems = document.querySelectorAll('.conversation-item');
        conversationItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Supprimer la classe active de tous les éléments
                conversationItems.forEach(i => i.classList.remove('active'));
                
                // Ajouter la classe active à l'élément cliqué
                this.classList.add('active');
                
                // Charger la conversation
                const partnerId = this.getAttribute('data-partner-id');
                loadConversation(partnerId);
                
                // Mettre à jour l'URL sans recharger la page
                updateURL(partnerId);
            });
        });
    }
    
    // Fonction pour mettre à jour l'URL sans recharger la page
    function updateURL(partnerId) {
        const baseUrl = messageIndexUrl || '/message/';
        history.pushState(null, '', `${baseUrl}?id=${partnerId}`);
    }
    
    // Fonction pour charger une conversation
    function loadConversation(partnerId) {
        // Afficher un indicateur de chargement
        conversationContainer.innerHTML = renderLoadingIndicator('Chargement de la conversation...');
        
        // Remplacer le '0' dans l'URL par l'ID du partenaire
        const url = conversationBaseUrl.replace('0', partnerId);
        
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.text())
            .then(html => {
                conversationContainer.innerHTML = html;
                
                // Vérifier s'il y a une erreur dans la réponse
                if (html.includes('error-message')) {
                    return;
                }
                
                // Faire défiler jusqu'au bas de la conversation
                const messagesContainer = conversationContainer.querySelector('.messages-container');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
                
                // Ajouter l'événement pour envoyer le formulaire en AJAX
                attachMessageFormEvents();
            })
            .catch(error => {
                console.error('Erreur lors du chargement de la conversation:', error);
                conversationContainer.innerHTML = renderErrorMessage('Une erreur est survenue lors du chargement de la conversation.');
            });
    }
    
    // Rendre un indicateur de chargement
    function renderLoadingIndicator(message) {
        return `
            <div class="loading-indicator">
                <div class="spinner"></div>
                <p>${message}</p>
            </div>
        `;
    }
    
    // Rendre un message d'erreur
    function renderErrorMessage(message) {
        return `
            <div class="error-message">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    ${message}
                </div>
            </div>
        `;
    }
    
 // Attacher les événements au formulaire de message
// Attacher les événements au formulaire de message
function attachMessageFormEvents() {
    const messageForm = conversationContainer.querySelector('.message-form');
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const form = this; // Référence explicite au formulaire
            
            // Capturer la référence au textarea avant l'envoi
            const allTextareas = form.querySelectorAll('textarea');
            const allInputs = form.querySelectorAll('input[type="text"]');
            
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = 'Envoi...';
            }

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ajoute le nouveau message
                    const messagesContainer = conversationContainer.querySelector('.messages-container');
                    if (messagesContainer) {
                        const messageHtml = `
                            <div class="message-wrapper sent">
                                <div class="message-bubble">
                                    <p>${data.message.content}</p>
                                    <span class="message-time">${data.message.time}</span>
                                </div>
                            </div>
                        `;
                        messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }

                    // Approche plus agressive pour vider tous les champs de saisie
                    allTextareas.forEach(textarea => {
                        textarea.value = '';
                        console.log('Textarea vidé:', textarea);
                    });
                    
                    allInputs.forEach(input => {
                        input.value = '';
                        console.log('Input vidé:', input);
                    });
                    
                    // Réinitialiser le formulaire après avoir vidé les champs manuellement
                    form.reset();
                    
                    // Force le rendu des éléments de formulaire
                    setTimeout(() => {
                        // Forcer une mise à jour du DOM
                        const messageInput = form.querySelector('.message-input');
                        if (messageInput) {
                            messageInput.value = '';
                            messageInput.innerHTML = '';
                            messageInput.textContent = '';
                        }
                    }, 0);
                    
                    // Met à jour le formulaire (au cas où il y aurait des modifications)
                    const formContainer = form.closest('.message-input-container');
                    if (formContainer && data.formHtml) {
                        formContainer.innerHTML = data.formHtml;
                        attachMessageFormEvents(); // Réattache les événements
                    }
                }
                
                // Recharge la liste des conversations
                reloadConversationList();
            })
            .catch(error => {
                console.error("Erreur lors de l'envoi du message:", error);
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Envoyer';
                }
            });
        });
    }
}
    
    // Fonction pour rechercher des utilisateurs avec un délai
    function searchUsers(query) {
        // Effacer le timeout existant
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Définir un nouveau timeout pour retarder la recherche
        searchTimeout = setTimeout(() => {
            if (query.length < 2) {
                searchResults.innerHTML = '<div class="alert alert-info">Veuillez saisir au moins 2 caractères pour la recherche.</div>';
                return;
            }
            
            // Afficher un indicateur de chargement
            searchResults.innerHTML = renderLoadingIndicator('Recherche en cours...');
            
            fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    searchResults.innerHTML = html;
                    
                    // Attacher les événements aux boutons
                    attachSearchResultEvents();
                })
                .catch(error => {
                    console.error('Erreur lors de la recherche:', error);
                    searchResults.innerHTML = renderErrorMessage('Une erreur est survenue lors de la recherche.');
                });
        }, 300); // Délai de 300ms
    }
    
    // Attacher les événements aux résultats de recherche
    function attachSearchResultEvents() {
        const viewButtons = searchResults.querySelectorAll('[data-action="view-conversation"]');
        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const userId = this.getAttribute('data-user-id');
                
                // Fermer le panneau de recherche
                userSearchPanel.style.display = 'none';
                
                // Charger la conversation
                loadConversation(userId);
                
                // Mettre à jour l'URL
                updateURL(userId);
            });
        });
    }
    
// Fonction pour charger le formulaire de nouveau message
function loadNewMessageForm(userId = null) {
    // Afficher un indicateur de chargement
    newMessageFormContainer.innerHTML = renderLoadingIndicator('Chargement du formulaire...');
    newMessageFormContainer.style.display = 'block';
    
    let url = newMessageUrl;
    if (userId) {
        url += `?to_user=${userId}`;
    }
    
    fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.text())
        .then(html => {
            newMessageFormContainer.innerHTML = html;
            newMessageFormContainer.style.display = 'block';
            searchResults.style.display = 'none';
            searchPanelTitle.textContent = 'Nouveau message';
            
            // Ajouter un événement au formulaire pour l'envoyer en AJAX
            const formElement = newMessageFormContainer.querySelector('form');
            if (formElement) {
                formElement.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(this);
                    const form = this; // Référence au formulaire
                    
                    // Désactiver le bouton d'envoi pendant la requête
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = 'Envoi...';
                    }
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Ajoute le nouveau message
                            const messagesContainer = conversationContainer.querySelector('.messages-container');
                            if (messagesContainer) {
                                const messageHtml = `
                                    <div class="message-wrapper sent">
                                        <div class="message-bubble">
                                            <p>${data.message.content}</p>
                                            <span class="message-time">${data.message.time}</span>
                                        </div>
                                    </div>
                                `;
                                messagesContainer.insertAdjacentHTML('beforeend', messageHtml);
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }
        
                            // Approche plus agressive pour vider tous les champs de saisie
                            allTextareas.forEach(textarea => {
                                textarea.value = '';
                                console.log('Textarea vidé:', textarea);
                            });
                            
                            allInputs.forEach(input => {
                                input.value = '';
                                console.log('Input vidé:', input);
                            });
                            
                            // Réinitialiser le formulaire après avoir vidé les champs manuellement
                            form.reset();
                            
                        }else {
                            // Afficher les erreurs
                            console.error('Erreur lors de l\'envoi du message:', data.errors);
                            
                            // Réactiver le bouton d'envoi
                            if (submitButton) {
                                submitButton.disabled = false;
                                submitButton.innerHTML = 'Envoyer';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Erreur lors de l\'envoi du message:', error);
                        
                        // Réactiver le bouton d'envoi
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.innerHTML = 'Envoyer';
                        }
                    });
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement du formulaire:', error);
            newMessageFormContainer.innerHTML = renderErrorMessage('Une erreur est survenue lors du chargement du formulaire.');
        });
}
    
    // Fonction pour recharger la liste des conversations
    function reloadConversationList() {
        fetch(`${messageIndexUrl}?list_only=1`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const conversationList = document.querySelector('.conversation-list');
            if (conversationList) {
                conversationList.innerHTML = html;
                
                // Réattacher les événements aux nouvelles conversations
                attachConversationEvents();
            }
        })
        .catch(error => {
            console.error('Erreur lors du rechargement des conversations:', error);
        });
    }
    
    // Événement pour la recherche
    searchButton.addEventListener('click', function() {
        searchPanelTitle.textContent = 'Rechercher un utilisateur';
        userSearchPanel.style.display = 'block';
        searchResults.style.display = 'block';
        newMessageFormContainer.style.display = 'none';
        searchInput.focus();
        searchUsers(searchInput.value);
    });
    
    searchInput.addEventListener('input', function() {
        searchUsers(this.value);
    });
    
    // Événement pour le nouveau message
    newMessageBtn.addEventListener('click', function() {
        searchPanelTitle.textContent = 'Nouveau message';
        userSearchPanel.style.display = 'block';
        searchResults.style.display = 'none';
        searchInput.value = '';
        
        // Charger le formulaire de nouveau message
        loadNewMessageForm();
    });
    
    // Événement pour fermer le panneau de recherche
    closeSearchPanelBtn.addEventListener('click', function() {
        userSearchPanel.style.display = 'none';
    });
    
    // Fonction pour vérifier les nouveaux messages périodiquement
    function checkUnreadMessages() {
        fetch(unreadMessageCountUrl)
            .then(response => response.json())
            .then(data => {
                // Mettre à jour l'interface avec le nombre de messages non lus
                if (data.count > 0) {
                    document.title = `(${data.count}) Mes messages`;
                    // Vous pouvez ajouter un badge ou une notification ici
                } else {
                    document.title = 'Mes messages';
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification des messages non lus:', error);
            });
    }
    
    // Vérifier les nouveaux messages toutes les 30 secondes
    setInterval(checkUnreadMessages, 30000);
    
    // Vérifier les nouveaux messages au chargement de la page
    checkUnreadMessages();
});