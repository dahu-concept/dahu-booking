# RESUME_TECHNIQUE — Dahu Booking

Plugin de prise de rendez-vous autonome pour WordPress. Développé par
Hugo Vial-Jaime / Dahu-Concept, Gap (05), France.

Contexte de déploiement initial : annad.fr (kit d'électrification vélo).
Le plugin est générique et utilisable sur tout site WordPress.

---

## Architecture

- **Shortcode `[dahu_booking]`** — widget front-end en deux étapes (calendrier + formulaire)
- **Page de gestion** — URL signée avec token pour modifier / annuler un RDV depuis l'e-mail
- **Bloc Gutenberg** — enveloppe autour du même shortcode (`blocks/booking/`)
- **Admin** — 4 écrans : Réservations, Disponibilités, Réglages, (éditeur CSS supprimé v1.0.1)

Pas de dépendance externe : pas de Stripe, pas d'API tiers. Les e-mails
partent via `wp_mail()` (compatible avec tout plugin SMTP).

---

## Base de données

Deux tables créées à l'activation :

```
{prefix}dahu_bookings
    id, booking_date, booking_time, client_name, client_email,
    client_message, status (confirmed/cancelled/rescheduled),
    token (UUID v4), created_at

{prefix}dahu_booking_slots
    id, day_of_week (1=lun … 7=dim), time_slot (HH:MM), is_active
```

Supprimées par `uninstall.php` avec toutes les options `dahu_booking_*`.

---

## Flux de réservation

1. JS charge les créneaux disponibles via AJAX (`dahu_get_available_times`)
   → `Dahu_Booking_DB::get_available_times_for_date()` — exclut les créneaux
   déjà pris et les jours fermés selon les réglages.
2. Soumission du formulaire (`dahu_create_booking`) — validation côté serveur :
   date, heure, nom, e-mail. Rate-limiting par IP via transient (20 s).
3. `Dahu_Booking_DB::create_booking()` — insère le RDV, renvoie `false` si le
   créneau vient d'être pris (race condition).
4. `Dahu_Booking_Mailer::send_booking_emails()` — confirmation client + notif admin.

---

## E-mails

Classe `Dahu_Booking_Mailer` — tous les sujets et corps sont i18n
(`__()` / `sprintf()`). Dates formatées via `wp_date(get_option('date_format'))` :
locale-aware, respecte le réglage WordPress.

Quatre e-mails : confirmation client, notif admin, recalage, annulation.
Chacun inclut notif admin symétrique.

---

## i18n

Text Domain : `dahu-booking`. Domaine déclaré dans l'en-tête et dans
`Domain Path: /languages`.

Toutes les chaînes d'interface sont passées dans `__()`, `esc_html__()`,
`esc_attr__()` ou `esc_html_e()` / `esc_attr_e()`. Les chaînes avec
variables utilisent `printf()` + commentaires translators.

Les tableaux JS (mois, jours, jours de semaine abrégés) sont injectés via
`wp_localize_script()` depuis PHP, jamais codés en dur dans le JS.

---

## Historique des décisions

### v1.0.0 — initial
- Éditeur CSS intégré (écrivait sur disque via `WP_Filesystem`)
- Option `dahu_booking_custom_css` stockée en BDD + sortie en front
- Chaînes d'interface en français, sans i18n
- Tableaux de dates JS en français (`MOIS_FR`, `JOURS_FR`) codés en dur

### v1.0.1 — correctifs WordPress.org (2026-10-31)
- **Éditeur CSS supprimé** : `admin/views/page-css-editor.php` effacé,
  toute la logique `WP_Filesystem` / `is_writable` retirée de la classe admin.
  Renvoi vers le Customizer natif dans l'écran Réglages.
- **Option `dahu_booking_custom_css` supprimée** : retirée de l'activateur,
  de l'admin, et de la classe publique.
- **i18n complet** : toutes les chaînes PHP et JS passées dans les fonctions
  WordPress. Arrays JS alimentés depuis `wp_localize_script()`.
- **`wp_date()`** : remplacement de `format_date_fr()` (tableaux FR codés en
  dur) par `wp_date(get_option('date_format'))` dans le mailer et les vues.
- **Bug `ajax_cancel_booking()`** : `$booking` utilisé avant d'être défini —
  ajout de `Dahu_Booking_DB::get_booking_by_token()` en tête de méthode.
- **`confirm()` JS supprimé** : interdit par les guidelines WordPress.org.
- **`uninstall.php` créé** : supprime toutes les options et les deux tables.
- **En-tête** : `Author: Hugo Vial-Jaime`, `Author URI: mailto:contact@dahu-concept.fr`,
  description en anglais, `Tested up to: 7.1`.

---

## Pièges connus

- **Race condition sur les créneaux** : `create_booking()` vérifie l'existence
  du créneau dans la même transaction. Si `false` est renvoyé, le JS affiche
  le message i18n `slotTaken` et invite l'utilisateur à choisir un autre créneau.
- **Nonce front-end** : créé à l'enqueue, valide 12 h. Le cache pleine page
  peut invalider le nonce si la page est servie depuis le cache après expiration.
  Solution : exclure la page de réservation du cache pleine page, ou utiliser
  `wp_ajax_*` avec renouvellement.
- **Calendrier vide au rendu** : si `dahuBookingData` n'est pas disponible au
  moment où le JS s'exécute (chargement asynchrone ou conflit), les tableaux
  MONTHS/DAYS/WEEKDAYS tombent sur les fallbacks anglais. Charger le script
  en `defer` (position `true` dans `wp_enqueue_script`) garantit que la
  localisation est disponible.

---

## Stack

- PHP 7.4+ (plancher garanti, pas de `str_contains` ni `match`)
- WordPress 6.2+
- Pas de WooCommerce requis
- JS vanilla (aucun framework)
- CSS sans préprocesseur
