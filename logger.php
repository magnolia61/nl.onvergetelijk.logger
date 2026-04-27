<?php

/**
 * =======================================================================================
 * FUNCTIE-INDEX: logger.php
 * =======================================================================================
 *   logger_activity_create()                              Maakt een Errorlog-activiteit aan
 *                                                         op basis van een binnenkomend logger-object.
 *   logger_civicrm_postSave_civicrm_system_log()         Hook op civicrm_system_log: stuurt errors
 *                                                         door naar logger_activity_create().
 *   logger_civicrm_postSave_civirule_civiruleslogger_log() Hook op civirules log-tabel: stuurt errors
 *                                                         door naar logger_activity_create().
 *   logger_civicrm_config()                               Implements hook_civicrm_config().
 *   logger_civicrm_install()                              Implements hook_civicrm_install().
 *   logger_civicrm_enable()                               Implements hook_civicrm_enable().
 * =======================================================================================
 */

require_once 'logger.civix.php';

use CRM_Logger_ExtensionUtil as E;

/**
 * =======================================================================================
 * COLOFON: logger_activity_create
 * =======================================================================================
 * @description     Maakt een Errorlog-activiteit aan op basis van een logger-object
 * (afkomstig uit civicrm_system_log of civirules_logger_log). Koppelt de activiteit aan
 * het bijbehorende contact via de DITJAAR-velden.
 * @param object $logger  DAO-object met id, contact_id, type, level, message, context, etc.
 * =======================================================================================
 */
function logger_activity_create(object $logger): void {

    $extdebug       = 0;
    $apidebug       = FALSE;
    $today_datetime = date("Y-m-d H:i:s");

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - 1.0 START CREATE ACTIVITY",          "[$logger->level]");
    wachthond($extdebug, 2, "########################################################################");

    $logger_contactid = $logger->contact_id ?? NULL;
    $logger_id        = $logger->id         ?? NULL;
    $logger_type      = $logger->type       ?? NULL;
    $logger_level     = $logger->level      ?? NULL;
    $logger_message   = $logger->message    ?? NULL;
    $logger_context   = $logger->context    ?? NULL;
    $logger_timestamp = $logger->timestamp  ?? NULL;
    $logger_ruleid    = $logger->rule_id    ?? NULL;
    $logger_hostname  = $logger->hostname   ?? NULL;

    wachthond($extdebug, 3, 'logger_contactid', $logger_contactid);
    wachthond($extdebug, 3, 'logger_id',        $logger_id);
    wachthond($extdebug, 3, 'logger_type',      $logger_type);
    wachthond($extdebug, 3, 'logger_level',     $logger_level);
    wachthond($extdebug, 3, 'logger_ruleid',    $logger_ruleid);
    wachthond($extdebug, 3, 'logger_message',   $logger_message);

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - 2.0 CONTACT INFO OPHALEN",          "[$logger_contactid]");
    wachthond($extdebug, 2, "########################################################################");

    $actdisplayname  = NULL;
    $actcontact_cid  = NULL;
    $actcontact_pid  = NULL;
    $actcontact_eid  = NULL;
    $actkampkort     = NULL;
    $actkamprol      = NULL;
    $actkampfunctie  = NULL;
    $actkampjaar     = NULL;
    $acteventstart   = NULL;
    $acteventeinde   = NULL;

    if ((int)$logger_contactid > 0) {

        $params_contact = [
            'checkPermissions' => FALSE,
            'debug'            => $apidebug,
            'limit'            => 1,
            'select'           => [
                'id',
                'display_name',
                'DITJAAR.DITJAAR_cid',
                'DITJAAR.DITJAAR_pid',
                'DITJAAR.DITJAAR_eid',
                'DITJAAR.DITJAAR_rol',
                'DITJAAR.DITJAAR_functie',
                'DITJAAR.DITJAAR_kampkort',
                'DITJAAR.DITJAAR_kampjaar',
                'DITJAAR.DITJAAR_event_start',
                'DITJAAR.DITJAAR_event_end',
            ],
            'where' => [
                ['id', '=', $logger_contactid],
            ],
        ];

        wachthond($extdebug, 7, 'params_contact', $params_contact);
        $result_contact = civicrm_api4('Contact', 'get', $params_contact);
        wachthond($extdebug, 9, 'result_contact', $result_contact);

        $row = $result_contact[0] ?? [];

        $actdisplayname = $row['display_name']              ?? NULL;
        $actcontact_cid = $row['DITJAAR.DITJAAR_cid']       ?? NULL;
        $actcontact_pid = $row['DITJAAR.DITJAAR_pid']       ?? NULL;
        $actcontact_eid = $row['DITJAAR.DITJAAR_eid']       ?? NULL;
        $actkampkort    = $row['DITJAAR.DITJAAR_kampkort']  ?? NULL;
        $actkamprol     = $row['DITJAAR.DITJAAR_rol']       ?? NULL;
        $actkampfunctie = $row['DITJAAR.DITJAAR_functie']   ?? NULL;
        $actkampjaar    = $row['DITJAAR.DITJAAR_kampjaar']  ?? NULL;

        $raw_start  = $row['DITJAAR.DITJAAR_event_start'] ?? NULL;
        $raw_einde  = $row['DITJAAR.DITJAAR_event_end']   ?? NULL;

        // Alleen omzetten als er een geldige datum aanwezig is
        if ($raw_start) {
            $acteventstart = date('Y-m-d H:i:s', strtotime($raw_start));
        }
        if ($raw_einde) {
            $acteventeinde = date('Y-m-d H:i:s', strtotime($raw_einde));
        }
    }

    $actkampkort_low = $actkampkort ? preg_replace('/[^ \w-]/', '', strtolower(trim($actkampkort))) : NULL;
    $actkampkort_cap = $actkampkort ? preg_replace('/[^ \w-]/', '', strtoupper(trim($actkampkort))) : NULL;

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - 3.0 CREATE ERRORLOG ACTIVITY",       "[$logger_level]");
    wachthond($extdebug, 2, "########################################################################");

    // Prioriteit op basis van log-level; onbekende levels krijgen 'Normaal'
    $prioriteit_map = [
        'emergency' => 'Urgent',
        'alert'     => 'Urgent',
        'critical'  => 'Urgent',
        'error'     => 'Urgent',
        'warning'   => 'Normaal',
        'notice'    => 'Normaal',
        'info'      => 'Laag',
        'debug'     => 'Laag',
    ];
    $actprioriteit = $prioriteit_map[$logger_level] ?? 'Normaal';

    $params_activity_create = [
        'checkPermissions' => FALSE,
        'debug'            => $apidebug,
        'values'           => [
            'source_contact_id'         => 1,
            'target_contact_id'         => $actcontact_cid,
            'activity_type_id:name'     => 'Errorlog',
            'activity_date_time'        => $today_datetime,
            'priority_id:name'          => $actprioriteit,
            'status_id:name'            => 'Scheduled',

            'subject'                   => 'LOGGER ' . $logger_level . ' ' . $logger_type . ' mbt rule_id: ' . $logger_ruleid,
            'details'                   => $logger_context,
            'location'                  => $logger_type,

            'ACT_LOG.logtype'           => $logger_type,
            'ACT_LOG.message'           => $logger_message,
            'ACT_LOG.context'           => $logger_context,
            'ACT_LOG.timestamp'         => $logger_timestamp,
            'ACT_LOG.level'             => $logger_level,
            'ACT_LOG.logid'             => $logger_id,
            'ACT_LOG.ruleid'            => $logger_ruleid,

            'ACT_ALG.actcontact_naam'   => $actdisplayname,
            'ACT_ALG.actcontact_cid'    => $actcontact_cid,
            'ACT_ALG.actcontact_pid'    => $actcontact_pid,
            'ACT_ALG.actcontact_eid'    => $actcontact_eid,
            'ACT_ALG.kampnaam'          => $actkampkort_cap,
            'ACT_ALG.kampkort'          => $actkampkort_low,
            'ACT_ALG.kampfunctie'       => $actkampfunctie,
            'ACT_ALG.kampstart'         => $acteventstart,
            'ACT_ALG.kampeinde'         => $acteventeinde,
            'ACT_ALG.kampjaar'          => $actkampjaar,
            'ACT_ALG.modified'          => $today_datetime,
            'ACT_ALG.prioriteit:label'  => $actprioriteit,
        ],
    ];

    wachthond($extdebug, 7, 'params_activity_create', $params_activity_create);
    try {
        $result_activity_create = civicrm_api4('Activity', 'create', $params_activity_create);
        wachthond($extdebug, 9, 'result_activity_create', $result_activity_create);
    } catch (\Exception $e) {
        wachthond(1, 1, "LOGGER ACTIVITY CREATE ERROR: " . $e->getMessage());
    }

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - EINDE CREATE ACTIVITY",              "[$logger_level]");
    wachthond($extdebug, 2, "########################################################################");
}

/**
 * =======================================================================================
 * COLOFON: logger_civicrm_postSave_civicrm_system_log
 * =======================================================================================
 * @description     Hook op civicrm_system_log. Stuurt error-niveau logs door naar
 * logger_activity_create() zodat ze als Errorlog-activiteit zichtbaar worden in CiviCRM.
 * =======================================================================================
 */
function logger_civicrm_postSave_civicrm_system_log($dao): void {

    $extdebug = 0;

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - POSTSAVE SYSTEMLOG",                 "[$dao->level]");
    wachthond($extdebug, 2, "########################################################################");

    $logger       = $dao;
    $logger->type = 'systemlog';

    wachthond($extdebug, 3, 'logger_contactid', $logger->contact_id ?? NULL);
    wachthond($extdebug, 3, 'logger_level',     $logger->level      ?? NULL);
    wachthond($extdebug, 3, 'logger_id',        $logger->id         ?? NULL);

    if (($logger->level ?? '') === 'error') {
        logger_activity_create($logger);
    }
}

/**
 * =======================================================================================
 * COLOFON: logger_civicrm_postSave_civirule_civiruleslogger_log
 * =======================================================================================
 * @description     Hook op de CiviRules logger-tabel. Stuurt error-niveau logs door naar
 * logger_activity_create() zodat ze als Errorlog-activiteit zichtbaar worden in CiviCRM.
 * =======================================================================================
 */
function logger_civicrm_postSave_civirule_civiruleslogger_log($dao): void {

    $extdebug = 0;

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - POSTSAVE CIVIRULES",                 "[$dao->level]");
    wachthond($extdebug, 2, "########################################################################");

    $logger       = $dao;
    $logger->type = 'civirules';

    wachthond($extdebug, 3, 'logger_contactid', $logger->contact_id ?? NULL);
    wachthond($extdebug, 3, 'logger_level',     $logger->level      ?? NULL);
    wachthond($extdebug, 3, 'logger_id',        $logger->id         ?? NULL);
    wachthond($extdebug, 3, 'logger_ruleid',    $logger->rule_id    ?? NULL);

    if (($logger->level ?? '') === 'error') {
        logger_activity_create($logger);
    }

    wachthond($extdebug, 2, "########################################################################");
    wachthond($extdebug, 1, "### LOGGER - EINDE POSTSAVE CIVIRULES",           "[$dao->level]");
    wachthond($extdebug, 2, "########################################################################");
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function logger_civicrm_config(&$config): void {
    _logger_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function logger_civicrm_install(): void {
    _logger_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function logger_civicrm_enable(): void {
    _logger_civix_civicrm_enable();
}
