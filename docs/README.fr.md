# Ubac Booking

Une extension de prise de rendez-vous légère et autonome pour WordPress. Développée par [Dahu-Concept](https://dahu-concept.fr).

## Fonctionnalités

- Widget de réservation en deux temps (calendrier et créneaux, puis formulaire de contact), avec des transitions dans l'esprit de Calendly
- S'insère n'importe où, par le code court `[ubac_booking]` ou par le bloc Gutenberg natif **Ubac Booking**
- Disponibilités gérées depuis l'administration WordPress : jours ouverts de la semaine, créneaux horaires, et blocages ponctuels
- Courriels de confirmation automatiques au visiteur et à l'administration du site, par `wp_mail()` — compatible avec n'importe quelle extension SMTP
- Le visiteur peut déplacer ou annuler son rendez-vous par un lien sécurisé reçu par courriel, sans avoir à créer de compte
- L'habillage du widget passe par l'éditeur de CSS additionnel de WordPress (Apparence > Personnaliser > CSS additionnel)
- Aucun appel à une API extérieure, aucun pistage, aucune dépendance à un service tiers

## Structure

```
ubac-booking/
├── ubac-booking.php              # Point d'entrée de l'extension
├── uninstall.php                 # Nettoyage à la suppression
├── includes/
│   ├── class-ubac-booking-activator.php   # Activation : tables SQL, options par défaut
│   ├── class-ubac-booking-db.php          # Tout l'accès aux données
│   └── class-ubac-booking-mailer.php      # Envoi des courriels
├── admin/
│   ├── class-ubac-booking-admin.php       # Écrans de wp-admin
│   └── views/                             # Gabarits des écrans d'administration
├── public/
│   ├── class-ubac-booking-public.php      # Code court, AJAX, page de gestion
│   └── views/                             # Gabarits du widget et de la page de gestion
└── assets/
    ├── css/                               # Styles public et administration
    └── js/                                # Logique JavaScript du widget
```

## Installation

1. Télécharger le zip de l'extension
2. WordPress > Extensions > Ajouter > Téléverser une extension
3. Activer l'extension
4. Régler disponibilités et réglages sous **Ubac Booking** dans wp-admin
5. Insérer le code court `[ubac_booking]` sur une page, ou utiliser le bloc Gutenberg

## Socle technique

- PHP / WordPress (tables SQL dédiées, créées par `dbDelta`)
- JavaScript natif, sans dépendance côté public
- CSS à propriétés personnalisées pour l'habillage

## Licence

MIT — voir [LICENSE](LICENSE).
