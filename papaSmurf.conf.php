<?php

/*
 *debug
 *This must be set to false for production. in order to prevent
 *sensitve information leakage
 */
$debug = true;

/*
 *MySQL variables
 *These should be configured to match your MySql setup
 *additionally the STDUser should only have SELECT, INSERT
 *and DELET Privleges (you know the whole least privlege princible
 */

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'smurfVillage';
$db_std_user = 'BrainySmurf';
$db_std_pass = 'poppycock';
$db_adm_user = 'PapaSmurf';
$db_adm_pass = 'smurfalicious';

/*
 *phpass variables
 *this configure the security of your hashing algorithum
 *there should be no need to change them
 */
$hash_cost_log2 = 8; //base-2 logarithum of the iteration count used for password streching
$hash_portable = False; //do be need the hashes to be portable to older servers (should be FALSE)

/*
 * Session Variables
 * There should be no reason to change these
 */
$session_name = 'smurfaroo';
$https = true; //set to false if not using ssl
$httpOnly = true; //stops javascript accessing session info


$maxLoginAttempts = 10;
$admin_user = 'gargamel';
$admin_pass = '$2a$08$oKi47IpLvWntMoFEfDAcDO2ZDaX2Y8U/.A.g10mJ5w.pNWIfmm9Ny'; //this can be generated using createPassword.php