// AgriCore — Notifications automatiques
// S'enregistre une seule fois, puis fonctionne automatiquement

(function() {
  if (!('serviceWorker' in navigator) || !('Notification' in window)) return;

  // Enregistrer le Service Worker
  navigator.serviceWorker.register('/sw.js').then(function(reg) {
    console.log('✅ AgriCore SW enregistré');
    window._agriSW = reg;
  }).catch(function(e) {
    console.warn('SW non enregistré:', e);
  });

  // Demander permission si pas encore accordée — AUTOMATIQUEMENT
  if (Notification.permission === 'default') {
    Notification.requestPermission().then(function(p) {
      if (p === 'granted') {
        showLocalNotif('✅ AgriCore', 'Alertes automatiques activées sur votre PC Windows !', 'info');
      }
    });
  }

  // Fonction globale pour envoyer une notification locale immédiate
  window.agriNotif = function(titre, message, niveau) {
    if (Notification.permission !== 'granted') {
      Notification.requestPermission().then(function(p) {
        if (p === 'granted') showLocalNotif(titre, message, niveau);
      });
      return;
    }
    showLocalNotif(titre, message, niveau);
  };

  function showLocalNotif(titre, message, niveau) {
    if (navigator.serviceWorker.controller) {
      navigator.serviceWorker.ready.then(function(reg) {
        reg.showNotification(titre, {
          body: message,
          icon: '/favicon.ico',
          requireInteraction: niveau === 'critique',
          tag: titre + Date.now(),
          vibrate: niveau === 'critique' ? [300, 100, 300] : [100],
        });
      });
    } else {
      new Notification(titre, {
        body: message,
        requireInteraction: niveau === 'critique',
      });
    }
  }

  // Vérifier les alertes en session au chargement de chaque page
  window.addEventListener('load', function() {
    var alertes = window.__agriAlertes;
    if (alertes && alertes.length > 0) {
      alertes.forEach(function(a, i) {
        setTimeout(function() {
          window.agriNotif(a.titre, a.message, a.niveau);
        }, i * 800);
      });
    }
  });
})();
