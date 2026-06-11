# nl.onvergetelijk.logger

## Functionele beschrijving

De `logger`-extensie bewaakt de systeemgezondheid van het OZK-CiviCRM door foutmeldingen automatisch om te zetten naar traceerbare activiteiten. Wanneer CiviCRM of de CiviRules-engine een foutmelding op niveau `warning` of hoger wegschrijft naar de systeemlog, maakt `logger` direct een activiteit van het type "Errorlog" aan in CiviCRM.

Fouten verdwijnen zo niet in een logbestand, maar worden zichtbaar als activiteit in CiviCRM. Beheerders kunnen fouten terugvinden en afhandelen via de normale CiviCRM-interface, zonder logbestanden te hoeven raadplegen.

## Afhankelijkheden

- `nl.onvergetelijk.base` (voor `wachthond`)
- CiviRules (optioneel: voor de CiviRules-loggerkoppeling)

---

## Technische documentatie

### Kernfuncties

- `logger_activity_create(object $logger)` — maakt een "Errorlog"-activiteit aan op basis van een logger-object. Haalt het bijbehorende contact op via APIv4 en slaat de activiteit op via APIv4.
- `logger_civicrm_postSave_civicrm_system_log($dao)` — luistert op de postSave-hook van `civicrm_system_log`; bij niveaus `emergency`, `alert`, `critical`, `error`, `warning` of `notice` wordt `logger_activity_create` aangeroepen.
- `logger_civicrm_postSave_civirule_civiruleslogger_log($dao)` — zelfde werking maar voor de CiviRules-logtabel.

### Hooks geïmplementeerd
- `civicrm_postSave_civicrm_system_log`
- `civicrm_postSave_civirule_civiruleslogger_log`
- `civicrm_config`, `civicrm_install`, `civicrm_enable`

### Activiteitstype
Aangemaakt activiteitstype: **Errorlog**. Onderwerp bevat het log-level, type en rule_id; de body de volledige context.

---

*Beheerd door Stichting Onvergetelijke Zomerkampen.*
