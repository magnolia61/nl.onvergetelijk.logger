<?php

require_once 'logger.civix.php';
// phpcs:disable
use CRM_Logger_ExtensionUtil as E;
// phpcs:enable


function logger_activity_create($logger) {

    $extdebug           = 1;
    $apidebug           = 1;
    $today_datetime     = date("Y-m-d H:i:s");

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### START LOGGER ### CREATE ACTIVITY",                 "[$logger->level]");
    wachthond($extdebug,2, "########################################################################");

    $logger_contactid   = $logger->contact_id;
    $logger_id          = $logger->id;
    $logger_type        = $logger->type;
    $logger_level       = $logger->level;
    $logger_message     = $logger->message;
    $logger_context     = $logger->context;
    $logger_timestamp   = $logger->timestamp;
    $logger_ruleid      = $logger->rule_id;
    $logger_hostname    = $logger->hostname;

    wachthond($extdebug,4, "logger",            $logger);

    wachthond($extdebug,3, "logger_contactid",  $logger->contact_id);
    wachthond($extdebug,3, "logger_id",         $logger->id);
    wachthond($extdebug,3, "logger_timestamp",  $logger->timestamp);
    wachthond($extdebug,3, "logger_level",      $logger->level);
    wachthond($extdebug,3, "logger_ruleid",     $logger->ruleid);
    wachthond($extdebug,3, "logger_hostname",   $logger->hostname);
    wachthond($extdebug,3, "logger_message",    $logger->message);
    wachthond($extdebug,3, "logger_context",    $logger->context);

    if ($logger_contactid > 0) {

        $params_contact = [
            'checkPermissions' => FALSE,
            'limit' => 1,
            'select' => [
                'id',
                'contact_id',
                'first_name',
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

        wachthond($extdebug,7, 'params_contactinfo',        $params_contact);
        $result_contact = civicrm_api4('Contact', 'get',    $params_contact);
        wachthond($extdebug,9, 'result_contactinfo',        $result_contact);
    }

    $actdisplayname     = $result_contact[0]['display_name']                ?? NULL;
//  $actcontact_cid     = $result_contact[0]['contact_id']                  ?? NULL;
    $actcontact_cid     = $result_contact[0]['DITJAAR.DITJAAR_cid']         ?? NULL;
    $actcontact_pid     = $result_contact[0]['DITJAAR.DITJAAR_pid']         ?? NULL;
    $actcontact_eid     = $result_contact[0]['DITJAAR.DITJAAR_eid']         ?? NULL;
    $actkampkort        = $result_contact[0]['DITJAAR.DITJAAR_kampkort']    ?? NULL;
    $actkamprol         = $result_contact[0]['DITJAAR.DITJAAR_rol']         ?? NULL;
    $actkampfunctie     = $result_contact[0]['DITJAAR.DITJAAR_functie']     ?? NULL;
    $actkampjaar        = $result_contact[0]['DITJAAR.DITJAAR_kampjaar']    ?? NULL;
    $acteventstart      = $result_contact[0]['DITJAAR.DITJAAR_event_start'] ?? NULL;
    $acteventeinde      = $result_contact[0]['DITJAAR.DITJAAR_event_end']   ?? NULL;
    $acteventstart      = date('Y-m-d H:i:s', strtotime($acteventstart))    ?? NULL;
    $acteventeinde      = date('Y-m-d H:i:s', strtotime($acteventeinde))    ?? NULL;
    $actkampkort_low    = preg_replace('/[^ \w-]/','',strtolower(trim($actkampkort))) ?? NULL;
    $actkampkort_cap    = preg_replace('/[^ \w-]/','',strtoupper(trim($actkampkort))) ?? NULL;

    ### CREATE ACTIVITY

    if ($logger_level == 'error') {
        $actprioriteit = 'Urgent';
    }
    if ($logger_level == 'alert') {
        $actprioriteit = 'Normaal';
    }
    if ($logger_level == 'debug') {
        $actprioriteit = 'Laag';
    }

    $params_activity_errorlog_create = [
        'checkPermissions' => FALSE,
        'debug' => $apidebug,
        'values' => [
            'source_contact_id'         => 1,
            'target_contact_id'         => $actcontact_cid,
            'activity_type_id:name'     => 'Errorlog',
            'activity_date_time'        => $today_datetime,
            'priority_id:name'          => $actprioriteit,
            'status_id:name'            => 'Scheduled',

            'subject'                   => 'LOGGER '. $logger_level . $logger_type. ' mbt rule_id: '. $logger_ruleid,
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
//          'ACT_ALG.afgerond'          => $ditevent_part_errorlog,
        ],
    ];
    wachthond($extdebug,7, 'params_activity_errorlog_create',               $params_activity_errorlog_create);
    $result_activity_errorlog_create = civicrm_api4('Activity','create',    $params_activity_errorlog_create);
    wachthond($extdebug,2, "params_activity_errorlog_create",   "EXECUTED");
    wachthond($extdebug,9, 'result_activity_errorlog_create RESULT',        $result_activity_errorlog_create);

}

function logger_civicrm_postSave_civicrm_system_log($dao) {

    $extdebug           = 1;
    $apidebug           = 1;

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### START LOGGER ### POSTSAVE LOG SYSTEMLOG",             "[$dao->level]");
    wachthond($extdebug,2, "########################################################################");

    $logger             = $dao;
    $logger->type       = "systemlog";

    wachthond($extdebug,4, "$logger->type logger",           $logger);
    wachthond($extdebug,2, "$logger->type logger_contactid", $logger->contact_id);
    wachthond($extdebug,2, "$logger->type logger_id",        $logger->id);
    wachthond($extdebug,3, "$logger->type logger_timestamp", $logger->timestamp);
    wachthond($extdebug,2, "$logger->type logger_level",     $logger->level);
    wachthond($extdebug,3, "$logger->type logger_message",   $logger->message);
    wachthond($extdebug,3, "$logger->type logger_context",   $logger->context);
    wachthond($extdebug,3, "$logger->type logger_ruleid",    $logger->rule_id);
    wachthond($extdebug,2, "$logger->type logger_hostname",  $logger->hostname);
    wachthond($extdebug,2, "$logger->type logger_table",     $logger->__table);  

    if ($logger->level == 'error') {
        $logger_activity_create_result = logger_activity_create($logger);
    }
/*
    // $contact_id = $dao->id;

    $systemlog_errors = civicrm_api3('SystemLog', 'get', [
        'debug'       => 1,
        'sequential'  => 1,
        'return'      => ["id", "message", "context", "level", "timestamp", "contact_id", "hostname"],
        'timestamp'   => ['>' => "-1 hour"],
    ]);

    wachthond($extdebug,3, "########################################################################");
    wachthond($extdebug,3, "### EINDE EXTLOG ### POSTSAVE LOG SYSTEMLOG",             "[$dao->level]");
    wachthond($extdebug,3, "########################################################################");
*/

}

function logger_civicrm_postSave_civirule_civiruleslogger_log($dao) {

    $extdebug           = 1;
    $apidebug           = 1;

    wachthond($extdebug,2, "########################################################################");
    wachthond($extdebug,2, "### START LOGGER ### POSTSAVE LOG CIVIRULES",             "[$dao->level]");
    wachthond($extdebug,2, "########################################################################");

    $logger             = $dao;
    $logger->type       = "civirules";

    wachthond($extdebug,4, "$logger->type logger",           $logger);
    wachthond($extdebug,2, "$logger->type logger_contactid", $logger->contact_id);
    wachthond($extdebug,2, "$logger->type logger_id",        $logger->id);
    wachthond($extdebug,3, "$logger->type logger_timestamp", $logger->timestamp);
    wachthond($extdebug,2, "$logger->type logger_level",     $logger->level);
    wachthond($extdebug,3, "$logger->type logger_message",   $logger->message);
    wachthond($extdebug,3, "$logger->type logger_context",   $logger->context);
    wachthond($extdebug,2, "$logger->type logger_ruleid",    $logger->rule_id);
    wachthond($extdebug,3, "$logger->type logger_hostname",  $logger->hostname);
    wachthond($extdebug,2, "$logger->type logger_table",     $logger->__table);

    if ($logger->level == 'error') {

        $logger_activity_create_result = logger_activity_create($logger);

        //$logger_array = (array) $logger;
        //$logger_activity_create_result = logger_activity_create($logger_array);
//      wachthond($extdebug,2, "### CREATE LOGGER ACTIVITY",             "[$logger_array]");

    }


/*
  // send the email
  $emailSubject   = "NEW LOG mbt $logger_level ruleid $civirules_log_id voor $civirules_log_contactid";
  $emailBody      = sprintf("NEW LOG mbt $civirules_log_level ruleid $civirules_log_id voor $civirules_log_contactid %d\n", $civirules_log);
  $emailRecipient = 'richard.van.oosterhout@gmail.com';

  mail( $emailRecipient, $emailSubject, $emailBody );
*/

/*

  $result = civicrm_api3('MessageTemplate', 'send', [
    'debug'           => 1,
    'id'              => 509,
    'from'            => "Stichting Onvergetelijke Zomerkampen",
    'contact_id'      => 1,
    'to_email'        => "richard@onvergeteljk.nl",
    'to_name'         => "Webteam Onvergetelijk",
//  'template_params' => $civirules_log_message,
  ]);
*/
  
  wachthond($extdebug,2, "########################################################################");
  wachthond($extdebug,2, "### EINDE LOGGER ### POSTSAVE LOG CIVIRULES",             "[$dao->level]");
  wachthond($extdebug,2, "########################################################################");

}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function logger_civicrm_config(&$config) {
  _logger_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function logger_civicrm_install() {
  _logger_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function logger_civicrm_enable() {
  _logger_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function logger_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */